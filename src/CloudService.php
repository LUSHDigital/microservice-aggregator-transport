<?php
/**
 * @file
 * Contains \LushDigital\MicroserviceAggregatorTransport\CloudService.
 */

namespace LushDigital\MicroserviceAggregatorTransport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Responsible for communication with a cloud service.
 *
 * @package LushDigital\MicroserviceAggregatorTransport
 */
abstract class CloudService extends Service implements ServiceInterface, CloudServiceInterface
{
    /**
     * MyAwesomeService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Set the auth credentials.
        $this->email = config(sprintf('transport.auth.%s.email', $this->getName()));
        $this->password = config(sprintf('transport.auth.%s.password', $this->getName()));

        // Set the uri.
        $this->uri = config(sprintf('transport.services.cloud.%s.uri', $this->getName()));

        // Set the version.
        $this->version = config(sprintf('transport.services.cloud.%s.version', $this->getName()));
    }

    /**
     * Email address of an SOA service account to authenticate with.
     *
     * @var string
     */
    protected $email;

    /**
     * Password of an SOA service account to authenticate with.
     *
     * @var string
     */
    protected $password;

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocol()
    {
        return 'https';
    }

    /**
     * {@inheritdoc}
     */
    public function getApiGatewayUrl()
    {
        if (config('transport.environment') == 'staging') {
            return sprintf('%s://%s-%s.%s', $this->getProtocol(), config('transport.gateway_uri'), config('transport.environment'), config('transport.domain'));
        } else {
            return sprintf('%s://%s.%s', $this->getProtocol(), config('transport.gateway_uri'), config('transport.domain'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        // Build the url of the api gateway.
        $apiGatewayURL = $this->getApiGatewayUrl();

        // Set up the HTTP client.
        $apiGatewayClient = new Client(['base_uri' => $apiGatewayURL]);

        // Authenticate.
        try {
            $response = $apiGatewayClient->request('POST', 'login', [
                'json' => [
                    'email' => $this->email,
                    'password' => $this->password,
                ]
            ]);

            // Get the response.
            $response = json_decode((string) $response->getBody());

            return !empty($response->data->consumer) ? $response->data->consumer->tokens[0]->value : false;
        }
        catch (RequestException $e) {
            Log::error(sprintf('Could not authenticate for cloud service. Reason: %s', $e->getMessage()));
            $this->setLastException($e);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function call()
    {
        // Check we have authenticate credentials.
        if (empty($this->email) || empty($this->password)) {
            throw new \RuntimeException('Cannot authenticate. Missing credentials');
        }

        // First we need to authenticate.
        $authToken = $this->authenticate();

        // Throw an error if we did not get an auth token.
        if (empty($authToken)) {
            throw new \RuntimeException('Could not authenticate for cloud service call.');
        }

        // Build the resource uri.
        $resourceUri = sprintf('%s/%s/%s', $this->namespace, $this->uri, $this->getCurrentRequest()->getResource());

        // Perform the current request.
        try {
            // Set the headers.
            $this->currentRequest->setHeader('Authorization', sprintf('%s %s', 'Bearer', $authToken));

            // Add the version header if required.
            if (!empty($this->version)) {
                $this->currentRequest->setHeader('x-service-version', $this->version);
            }

            $response = $this->client->request($this->getCurrentRequest()->getMethod(), $resourceUri, $this->prepareRequestOptions());

            return json_decode((string) $response->getBody());
        }
        catch (RequestException $e) {
            $message = $e->getMessage();

            // Get the full text of the exception (including stack trace),
            // and replace the original message (possibly truncated),
            // with the full text of the entire response body.
            if (!empty($e->getResponse())) {
                $message = str_replace(
                    rtrim($e->getMessage()),
                    (string) $e->getResponse()->getBody(),
                    (string) $e
                );
            }

            Log::error(sprintf('An error occurred calling the service: %s', $message));
            $this->setLastException($e);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function callAsync(callable $onFulfilled = null, callable $onRejected = null)
    {
        // Check we have authenticate credentials.
        if (empty($this->email) || empty($this->password)) {
            throw new \RuntimeException('Cannot authenticate. Missing credentials');
        }

        // First we need to authenticate.
        $authToken = $this->authenticate();

        // Throw an error if we did not get an auth token.
        if (empty($authToken)) {
            throw new \RuntimeException('Could not authenticate for cloud service call.');
        }

        // Build the resource uri.
        $resourceUri = sprintf('%s/%s/%s', $this->namespace, $this->uri, $this->getCurrentRequest()->getResource());

        // Set the headers.
        $this->currentRequest->setHeader('Authorization', sprintf('%s %s', 'Bearer', $authToken));

        // Add the version header if required.
        if (!empty($this->version)) {
            $this->currentRequest->setHeader('x-service-version', $this->version);
        }

        // Create the promise.
        return $this->client->requestAsync(
            $this->getCurrentRequest()->getMethod(),
            $resourceUri,
            $this->prepareRequestOptions()
        )->then($onFulfilled, $onRejected);
    }

    /**
     * {@inheritdoc}
     */
    public function dial(Request $request)
    {
        // Make any alterations based upon the namespace.
        switch ($this->namespace) {
            case "aggregators":
                $this->uri = sprintf('%s-%s', config('transport.aggregator_prefix'), $this->uri);
                break;
        }

        // Set up the HTTP client.
        $this->client = new Client(['base_uri' => $this->getApiGatewayUrl()]);
        $this->setCurrentRequest($request);
    }
}

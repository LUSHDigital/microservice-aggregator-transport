<?php
/**
 * @file
 * Contains \LushDigital\MicroserviceAggregatorTransport\Service.
 */

namespace LushDigital\MicroserviceAggregatorTransport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Responsible for communication with a service.
 *
 * @package LushDigital\MicroserviceAggregatorTransport
 */
abstract class Service implements ServiceInterface
{
    /**
     * The VCS branch of the service.
     *
     * @var string
     */
    protected $branch;

    /**
     * HTTP client to communicate with the service.
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * The CI environment of the service.
     *
     * @var string
     */
    protected $environment;

    /**
     * The name of the service.
     *
     * @var string
     */
    protected $name;

    /**
     * The uri of the service.
     *
     * @var string
     */
    protected $uri;

    /**
     * The namespace of the service.
     *
     * @var string
     */
    protected $namespace = 'service';

    /**
     * The version of the service.
     *
     * @var int
     */
    protected $version = null;

    /**
     * The current request to perform.
     *
     * @var Request
     */
    protected $currentRequest;

    /**
     * Details of the last error that occurred.
     *
     * @var RequestException
     */
    private $lastException;

    /**
     * Service constructor.
     */
    public function __construct()
    {
        // Set up the service from config.
        $this->branch = config('transport.branch');
        $this->environment = config('transport.environment');

        // Set the uri.
        $this->uri = config(sprintf('transport.services.local.%s.uri', $this->getName()));

        // Set the version.
        $this->version = config(sprintf('transport.services.local.%s.version', $this->getName()));
    }

    /**
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @param string $branch
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param ClientInterface $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getName()
    {
        // Use the string library to get a name if one is not specified.
        if (empty($this->name)) {
            return str_replace('\\', '', Str::snake(class_basename($this)));
        }

        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return Request
     */
    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    /**
     * @param Request $currentRequest
     */
    public function setCurrentRequest($currentRequest)
    {
        $this->currentRequest = $currentRequest;
    }

    /**
     * @param RequestException $lastException
     */
    protected function setLastException($lastException)
    {
        $this->lastException = $lastException;
    }

    /**
     * @return RequestException
     */
    public function getLastException()
    {
        return $this->lastException;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocol()
    {
        return 'http';
    }

    /**
     * {@inheritdoc}
     */
    public function call()
    {
        // Perform the current request.
        try {
            $response = $this->client->request(
                $this->currentRequest->getMethod(),
                $this->currentRequest->getResource(),
                $this->prepareRequestOptions()
            );

            return json_decode((string) $response->getBody());
        }
        catch (RequestException $e) {
            Log::error(sprintf('An error occurred calling the service. Detail: %s', $e->getMessage()));
            $this->setLastException($e);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function callAsync(callable $onFulfilled = null, callable $onRejected = null)
    {
        // Create the promise.
        return $this->client->requestAsync(
            $this->currentRequest->getMethod(),
            $this->currentRequest->getResource(),
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

        // Determine the service namespace to use based on the service version.
        $serviceNamespace = $this->uri;
        if (!empty($this->version)) {
            $serviceNamespace = sprintf("%s-%d", $serviceNamespace, $this->version);
        }

        // Get the name of the service.
        $dnsName = sprintf('%s-%s-%s.%s', $this->uri, $this->branch, $this->environment, $serviceNamespace);

        // Build the URL to the requested service.
        $serviceURL = sprintf('%s://%s', $this->getProtocol(), $dnsName);

        // Set up the HTTP client.
        $this->client = new Client(['base_uri' => $serviceURL]);
        $this->currentRequest = $request;
    }

    /**
     * Prepare an array of options to use in a Guzzle request.
     *
     * @return array
     */
    protected function prepareRequestOptions()
    {
        // Start with the query.
        $options = [
            'query' => $this->getCurrentRequest()->getQuery(),
        ];

        // Add a json body if present.
        if (!empty($this->getCurrentRequest()->getBody())) {
            $options['json'] = $this->getCurrentRequest()->getBody();
        }

        // Add a multipart if present.
        if (!empty($this->getCurrentRequest()->getMultipartArray())) {
            $options['multipart'] = $this->getCurrentRequest()->getMultipartArray();
        }

        // Add headers if present.
        if (!empty($this->getCurrentRequest()->getHeaders())) {
            $options['headers'] = $this->getCurrentRequest()->getHeaders();
        }

        return $options;
    }
}
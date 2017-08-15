<?php
/**
 * @file
 * Contains \LushDigital\MicroserviceAggregatorTransport\Service.
 */

namespace LushDigital\MicroserviceAggregatorTransport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
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
     * The current request to perform.
     *
     * @var Request
     */
    private $currentRequest;

    /**
     * Details of the last error that occurred.
     *
     * @var TransferException
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

        // Set the service name based on the class name.
        $this->name = str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));

        // Set the uri.
        $this->uri = config(sprintf('transport.services.local.%s.uri', $this->name));

        // TODO: Implement versioning support.
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
     * @param TransferException $lastException
     */
    protected function setLastException($lastException)
    {
        $this->lastException = $lastException;
    }

    /**
     * @return TransferException
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
            $response = $this->client->request($this->currentRequest->getMethod(), $this->currentRequest->getResource(), [
                'json' => $this->currentRequest->getBody(),
                'query' => $this->currentRequest->getQuery(),
            ]);

            return json_decode((string) $response->getBody());
        }
        catch (TransferException $e) {
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
        return $this->client->requestAsync($this->currentRequest->getMethod(), $this->currentRequest->getResource(), [
            'json' => $this->currentRequest->getBody(),
            'query' => $this->currentRequest->getQuery(),
        ])->then($onFulfilled, $onRejected);
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

        // Get the name of the service.
        $dnsName = sprintf('%s-%s-%s.%s', $this->uri, $this->branch, $this->environment, $this->uri);

        // Build the URL to the requested service.
        $serviceURL = sprintf('%s://%s', $this->getProtocol(), $dnsName);

        // Set up the HTTP client.
        $this->client = new Client(['base_uri' => $serviceURL]);
        $this->currentRequest = $request;
    }
}
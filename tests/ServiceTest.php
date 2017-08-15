<?php
/**
 * @file
 * Contains \LushDigital\MicroserviceAggregatorTransport\Tests\ServiceTest.
 */

namespace LushDigital\MicroserviceAggregatorTransport\Tests;

use LushDigital\MicroserviceAggregatorTransport\Request;
use LushDigital\MicroserviceAggregatorTransport\Service;
use PHPUnit\Framework\TestCase;

/**
 * Test the service class.
 */
class ServiceTest extends TestCase
{
    /**
     * Example configuration.
     *
     * @var array
     */
    protected $config = [
        'transport.domain' => 'test.com',
        'transport.branch' => 'master',
        'environment' => 'testing',
        'transport.gateway_uri' => 'api-gateway',
        'transport.aggregator_prefix' => 'aggregator',
        'transport.services.local.example_service.uri' => 'example-service',
    ];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        // Set default config.
        config($this->config);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test service config.
     */
    public function testServiceConfig()
    {
        // Set up the service.
        $service = new ExampleCloudService;

        // Test the config.
        $this->assertEquals($this->config['transport.branch'], $service->getBranch());
        $this->assertEquals($this->config['transport.environment'], $service->getEnvironment());
        $this->assertEquals('example_service', $service->getName());
    }

    /**
     * Test dial.
     */
    public function testDial()
    {
        // Set up the service.
        $service = new ExampleCloudService;

        // Create the request.
        $request = new Request('example', 'POST', ['wibble' => true]);

        // Dial it up.
        $service->dial($request);

        $this->assertEquals($request, $service->getCurrentRequest());
        $this->assertEquals('https://example-service-master-testing.example-service', $service->getClient()->getConfig('base_uri'));
    }
}

/**
 * An example service.
 */
class ExampleService extends Service
{
    /**
     * Save some data.
     *
     * @param array $data
     *     Save some data.
     *
     * @return bool
     *     Did it save?
     */
    public function save(array $data)
    {
        // Create the request.
        $request = new Request('example', 'POST', $data);

        // Do the request.
        $this->dial($request);
        $response = $this->call();

        return !empty($response->data->example);
    }
}
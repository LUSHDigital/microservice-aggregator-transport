<?php
/**
 * @file
 * Contains \LushDigital\MicroserviceAggregatorTransport\Tests\CloudServiceTest.
 */

namespace LushDigital\MicroserviceAggregatorTransport\Tests;

use LushDigital\MicroserviceAggregatorTransport\CloudService;
use LushDigital\MicroserviceAggregatorTransport\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test the service class.
 */
class CloudServiceTest extends TestCase
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
        'transport.services.cloud.example_cloud_service.uri' => 'example-service',
        'transport.services.cloud.example_cloud_service.email' => 'foo',
        'transport.services.cloud.example_cloud_service.password' => 'bar',
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
        $this->assertEquals('example_cloud_service', $service->getName());
        $this->assertEquals($this->config['transport.services.cloud.example_cloud_service.email'], $service->getEmail());
        $this->assertEquals($this->config['transport.services.cloud.example_cloud_service.password'], $service->getPassword());
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
        $this->assertEquals(sprintf('%s://%s-%s.%s', 'https', $this->config['transport.gateway_uri'], $this->config['transport.environment'], $this->config['transport.domain']), $service->getApiGatewayUrl());
        $this->assertEquals($service->getApiGatewayUrl(), $service->getClient()->getConfig('base_uri'));
    }
}

/**
 * An example service.
 */
class ExampleCloudService extends CloudService
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
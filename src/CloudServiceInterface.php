<?php
/**
 * @file
 * Contains \LushDigital\MicroserviceAggregatorTransport\CloudServiceInterface.
 */

namespace LushDigital\MicroserviceAggregatorTransport;

use GuzzleHttp\Exception\TransferException;

/**
 * Functionality that all cloud service classes must implement.
 *
 * @package LushDigital\MicroserviceAggregatorTransport
 */
interface CloudServiceInterface
{
    /**
     * Authenticate against the API gateway.
     *
     * @throws TransferException
     * @return string|bool
     */
    public function authenticate();

    /**
     * Get the URL of the api gateway.
     *
     * @return string
     */
    public function getApiGatewayUrl();
}
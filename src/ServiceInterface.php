<?php
/**
 * @file
 * Contains \LushDigital\MicroserviceAggregatorTransport\ServiceInterface.
 */

namespace LushDigital\MicroserviceAggregatorTransport;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Functionality that all service transport classes must implement.
 *
 * @package LushDigital\MicroserviceAggregatorTransport
 */
interface ServiceInterface
{
    /**
     * Get the transport protocol expected for the service.
     *
     * @return mixed
     */
    public function getProtocol();

    /**
     * Create a request to a service resource.
     *
     * @param Request $request
     * @throws \RuntimeException
     */
    public function dial(Request $request);

    /**
     * Do the current service request.
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function call();

    /**
     * Do the current service request, asynchronously.
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return PromiseInterface
     */
    public function callAsync(callable $onFulfilled = null, callable $onRejected = null);
}
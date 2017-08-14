# Lush Digital - Micro Service Aggregator Transport
A set of convenience classes and interfaces for simplifying aggregation of data from multiple microservices.

The purpose of the package is to provide a reliable, testable and easy to use means of communicating with microservices
within a service oriented architecture.

## Package Contents
* Service interface
* Service abstract class
* Cloud service interface
* Cloud service abstract class
* Request class

## Installation
Install the package as normal:

```bash
$ composer require lushdigital/microservice-aggregator-transport
```

Copy the `src/config/transport.php` file into your `config` folder in the root of your app.

## Creating a Service
The first thing you need to do to utilise this package is to create a class to interact with your service.

This class will extend one of the base classes this package provides; you add your own methods for each endpoint
of the service you want to access.

### Local Service
A 'local' service is used when you can communicate with a service via some kind of local DNS. In that you do not need
to call out over the internet to access the service. For example you might be using
[Kubernetes DNS](https://kubernetes.io/docs/concepts/services-networking/dns-pod-service/).

To create a local service you need to extend the `\LushDigital\MicroserviceAggregatorTransport\Service` class:

```php
<?php
/**
 * @file
 * Contains \App\Services\MyAwesomeService.
 */

namespace App\Services;

use LushDigital\MicroserviceAggregatorTransport\Service as BaseService;
use LushDigital\MicroserviceAggregatorTransport\Request;
use App\Models\Thing;

/**
 * Transport layer for my awesome service.
 *
 * @package App\Services
 */
class MyAwesomeService extends BaseService
{
    /**
     * MyAwesomeService constructor.
     */
    public function __construct()
    {
        parent::__construct(env('SERVICE_BRANCH', 'master'), env('SERVICE_ENVIRONMENT', 'local'), config('transport.services.local.myawesomeservice'));
    }
    
    /**
     * Save a thing.
     *
     * @param Thing $thing
     *     The thing to save.
     *
     * @return array 
     */
    public function saveAThing(Thing $thing)
    {
        // Create the request.
        $request = new Request('things', 'POST', $thing->toArray());

        // Do the request.
        $this->dial($request);
        $response = $this->call();

        return !empty($response->data->things) ? $response->data->things : [];
    }
}
```
> As you can see in this service we have created a method which calls a `POST` endpoint to save a thing.

### Cloud Service
A cloud service is used when you need to communicate with a service over the internet. The assumption is that the service
is accessed via some kind of API gateway and can't be accessed directly.

Before you can create a cloud service you need to ensure the following config options are set (explicitly or via environment variables):



## Using a Service
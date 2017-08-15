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

Before you can create a cloud service you need to ensure the following config options are set (explicitly or via environment variables):

* `transport.branch` - The CI branch. For example master.
* `transport.environment` - The CI environment. For example dev or staging.

Then for each local service, you must define:

* `transport.services.local.SERVICE_NAME.uri` - The URI of the local service.

You can also optionally specify a version of a service:

* `transport.services.local.SERVICE_NAME.version` - The version of the local service.

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
        parent::__construct(env('SERVICE_BRANCH', 'master'), env('SERVICE_ENVIRONMENT', 'local'), config('transport.services.local.my_awesome_service.uri'));
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

* `transport.branch` - The CI branch. For example master.
* `transport.domain` - The top level domain of the service environment.
* `transport.gateway_uri` - The URI of the API gateway.
* `transport.environment` - The CI environment. For example dev or staging.

Then for each cloud service, you must define:

* `transport.services.cloud.SERVICE_NAME.uri` - The URI of the cloud service.
* `transport.auth.SERVICE_NAME.email` - The email address of a service account used to access the cloud service.
* `transport.auth.SERVICE_NAME.password` - The password of a service account used to access the cloud service.

You can also optionally specify a version of a service:
                                        
* `transport.services.cloud.SERVICE_NAME.version` - The version of the cloud service.

Then you can define your service:
```php
<?php
/**
 * @file
 * Contains \App\Services\MyAwesomeService.
 */

namespace App\Services;

use LushDigital\MicroserviceAggregatorTransport\CloudService;
use LushDigital\MicroserviceAggregatorTransport\Request;
use App\Models\Thing;

/**
 * Transport layer for my awesome cloud service.
 *
 * @package App\Services
 */
class MyAwesomeCloudService extends CloudService
{
    /**
     * MyAwesomeService constructor.
     */
    public function __construct()
    {
        parent::__construct(env('SERVICE_BRANCH', 'master'), env('SERVICE_ENVIRONMENT', 'local'), config('transport.services.cloud.my_awesome_service.uri'));
   
        // Set the auth credentials.
        $this->email = config('transport.auth.my_awesome_service.email');
        $this->password = config('transport.auth.my_awesome_service.password');
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
> As you can the service looks very similar to a local one. The only major difference is the base class. The base class
does all the heavy lifting of authentication and API gateway routing so you don't have to!

## Using a Service
Once you have created your service it can be used just like any other PHP class. Think of them like you would a repository
object in a database environment.

Example usage in a controller:
```php
<?php
/**
 * @file
 * Contains \App\Http\Controllers\MyAwesomeController.
 */

namespace App\Http\Controllers;

use App\Models\Thing;
use App\Services\MyAwesomeService;
use App\Services\MyAwesomeCloudService;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller as BaseController;
use LushDigital\MicroserviceAggregatorTransport\ServiceInterface;

class MyAwesomeController extends BaseController
{
    /**
     * Transport layer for my awesome service.
     *
     * @var ServiceInterface
     */
    protected $myAwesomeService;
    
    /**
     * Transport layer for my awesome cloud service.
     *
     * @var ServiceInterface
     */
    protected $myAwesomeCloudService;
    
    /**
     * MyAwesomeController constructor.
     */
    public function __construct()
    {
        $this->myAwesomeService = new MyAwesomeService();
        $this->myAwesomeCloudService = new MyAwesomeCloudService();
    }
    
    /**
     * Create a new thing.
     *
     * @param Request $request
     * @return Response
     */
    public function storeThing(Request $request)
    {
        // Validate the request.
        $this->validate($request, ['name' => 'required|string']);
        
        try {
            // Prepare a thing.
            $thing = new Thing;
            $thing->fill($request->input());
               
            // Save a thing.
            $newThing = $this->myAwesomeService->saveAThing($thing);
            
            return response()->json($newThing, 200);
        } catch (BadResponseException $e) {
            return response()->json(null, 500);
        }
    }
    
    /**
     * Create a new thing.
     *
     * @param Request $request
     * @return Response
     */
    public function storeCloudThing(Request $request)
    {
        // Validate the request.
        $this->validate($request, ['name' => 'required|string']);
        
        try {
            // Prepare a thing.
            $thing = new Thing;
            $thing->fill($request->input());
               
            // Save a thing.
            $newThing = $this->myAwesomeCloudService->saveAThing($thing);
            
            return response()->json($newThing, 200);
        } catch (BadResponseException $e) {
            return response()->json(null, 500);
        }
    }
}
```
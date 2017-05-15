<?php
namespace Module\HttpFoundation\ServiceManager;

use Module\HttpFoundation\ServiceManager\Request\BuildHttpRequestFromPhpServer;
use Poirot\Http\HttpRequest;

use Poirot\Ioc\Container\Service\aServiceContainer;


class ServiceRequest
    extends aServiceContainer
{
    /**
     * @var string Service Name
     */
    protected $name = 'HttpRequest';


    /**
     * Create Service
     *
     * @return mixed
     */
    function newService()
    {
        ## build request with php sapi attributes
        $request = new HttpRequest;
        $builder = new BuildHttpRequestFromPhpServer;
        $builder->build($request);

        return $request;
    }
}

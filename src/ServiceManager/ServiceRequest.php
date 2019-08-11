<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Http\HttpMessage\Request\BuildHttpRequestFromPhpServer;
use Poirot\Http\HttpRequest;

use Poirot\Http\Interfaces\Respec\iRequestAware;
use Poirot\Ioc\Container;
use Poirot\Ioc\Container\Interfaces\iServiceFeatureDelegate;
use Poirot\Ioc\Container\Service\aServiceContainer;


class ServiceRequest
    extends aServiceContainer
    implements iServiceFeatureDelegate
{
    const NAME = 'HttpRequest';

    /**
     * @var string Service Name
     */
    protected $name = self::NAME;


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

    /**
     * @inheritdoc
     */
    function delegate(Container $container)
    {
        # Initialize service dependencies
        $container->initializer()->addCallable(function($serviceInstance) use ($container) {
            if ($serviceInstance instanceof iRequestAware)
                $serviceInstance->setRequest( $container->get(self::NAME) );
        });
    }
}

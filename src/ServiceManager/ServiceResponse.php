<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Http\HttpMessage\Response\BuildHttpResponse;
use Poirot\Http\HttpResponse;
use Poirot\Http\HttpMessage\Response\DataParseResponsePhp;

use Poirot\Http\Interfaces\Respec\iResponseAware;
use Poirot\Ioc\Container;
use Poirot\Ioc\Container\Interfaces\iServiceFeatureDelegate;
use Poirot\Ioc\Container\Service\aServiceContainer;


class ServiceResponse
    extends aServiceContainer
    implements iServiceFeatureDelegate
{
    const NAME = 'HttpResponse';

    /** @var string Service Name */
    protected $name = self::NAME;


    /**
     * @inheritdoc
     */
    function newService()
    {
        $setting  = new DataParseResponsePhp;
        $response = new HttpResponse(
            new BuildHttpResponse( BuildHttpResponse::parseWith($setting) )
        );
        
        return $response;
    }

    /**
     * @inheritdoc
     */
    function delegate(Container $container)
    {
        # Initialize service dependencies
        $container->initializer()->addCallable(function($serviceInstance) use ($container) {
            if ($serviceInstance instanceof iResponseAware)
                $serviceInstance->setResponse( $container->get(self::NAME) );
        });
    }
}

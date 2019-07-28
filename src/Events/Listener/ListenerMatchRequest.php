<?php
namespace Module\HttpFoundation\Events\Listener;

use Poirot\Application\aSapi;
use Poirot\Events\Listener\aListener;
use Poirot\Ioc\Container\Service\ServiceInstance;
use Poirot\Psr7\HttpRequest;
use Poirot\Router\Interfaces\iRouterStack;
use Poirot\Router\RouterStack;
use Psr\Http\Message\RequestInterface;


class ListenerMatchRequest
    extends aListener
{
    const WEIGHT = -10;
    const RESULT_ROUTE_MATCH = 'route_match';


    /**
     * Match Request
     *
     * @param aSapi $sapi
     * @param iRouterStack|mixed $route_match
     *
     * @return array
     * @throws \Exception
     */
    function __invoke($sapi = null, $route_match = null)
    {
        if ( $route_match )
            ## route matched
            return null;


        $services = $sapi->services();


        ## Match Http Request Against Router
        #
        /** @var iRouterStack $router */
        /** @var HttpRequest $request */
        $router   = $services->get(iRouterStack::class);
        $request  = $services->fresh(RequestInterface::class);
        $match    = $router->match($request);


        ## Set Router Matched As a Service In Container
        #
        /** @var RouterStack $routeMatch */
        /** @see UrlService */
        $route_match = (new ServiceInstance)
            ->setName('router.match')
            ->setService($match);

        $sapi->services()->set($route_match);


        ## Pass Matched Route as a Param To Event
        #
        return [
            self::RESULT_ROUTE_MATCH => $match
        ];
    }
}

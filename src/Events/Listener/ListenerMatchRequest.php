<?php
namespace Module\HttpFoundation\Events\Listener;

use Poirot\Application\aSapi;
use Poirot\Events\Listener\aListener;
use Poirot\Ioc\Container\Service\ServiceInstance;
use Poirot\Router\Interfaces\iRouterStack;
use Poirot\Router\RouterStack;


/**
 * @see SapiHttp::_attachToEvents
 */

class ListenerMatchRequest
    extends aListener
{
    const RESULT_ROUTE_MATCH = 'route_match';


    /**
     * @param aSapi $sapi
     * @return array
     */
    function __invoke($sapi = null, $route_match = null)
    {
        if ($route_match)
            ## route matched
            return null;


        $services = $sapi->services();

        /** @var iRouterStack $router */
        $router   = $services->get('Router');
        $match    = $router->match( $services->fresh('HttpRequest-Psr') );


        /** @var RouterStack $routeMatch */
        $route_match = new ServiceInstance;
        /** @see UrlService */
        $route_match->setName('router.match');
        $route_match->setService($match);
        $sapi->services()->set($route_match);

        return array(self::RESULT_ROUTE_MATCH => $match); // pass param to event
    }
}

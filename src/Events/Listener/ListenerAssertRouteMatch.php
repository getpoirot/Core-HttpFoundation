<?php
namespace Module\HttpFoundation\Events\Listener;

use Poirot\Application\Exception\ErrorRouteNotMatch;
use Poirot\Application\aSapi;
use Poirot\Events\Listener\aListener;
use Poirot\Http\HttpRequest;
use Poirot\Router\Interfaces\iRoute;


class ListenerAssertRouteMatch
    extends aListener
{
    const WEIGHT = -900;


    /**
     * @param iRoute $route_match
     * @param aSapi  $sapi
     *
     * @return void
     */
    function __invoke($route_match = null, $sapi = null)
    {
        if ($route_match)
            // Nothing to do
            return;


        $services = $sapi->services();

        /** @var HttpRequest $request */
        $request = $services->get('HttpRequest');
        throw new ErrorRouteNotMatch(sprintf(
            'Route Not Match On %s %s'
            , $request->getMethod(), $request->getTarget()
        ));
    }
}

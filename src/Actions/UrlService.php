<?php
namespace Module\HttpFoundation\Actions;

use Poirot\Ioc\Container\Service\aServiceContainer;


class UrlService 
    extends aServiceContainer
{
    /** @var string Service Name */
    protected $name = 'url';


    /**
     * Create Service
     *
     * @return mixed
     */
    function newService()
    {
        $services = $this->services();

        $routeMatch = ($services->has('/router.match'))
            ? $services->from('/')->get('router.match')
            : false;

        $rAction = new Url(
            $services->from('/')->get('Router')
            , $services->from('/')->get('HttpRequest-Psr')
            , $routeMatch ? $routeMatch : null
        );

        return $rAction;
    }
}

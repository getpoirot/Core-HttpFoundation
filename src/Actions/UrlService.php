<?php
namespace Module\HttpFoundation\Actions;

use Module\HttpFoundation\Request\Plugin\ServerPathUrl;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Ioc\Container\Service\aServiceContainer;
use Poirot\Router\Interfaces\iRouterStack;


class UrlService 
    extends aServiceContainer
{
    /** @var string Service Name */
    protected $name = 'url';


    /**
     * @inheritdoc
     *
     * @return Url
     */
    function newService()
    {
        $services = $this->services();

        $routeMatch = ($services->has('/router.match'))
            ? $services->from('/')->get('router.match')
            : false;

        $rAction = new Url(
            $services->from('/')->get(iRouterStack::class)
            , $services->from('/')->get('HttpRequest-Psr')
            , $routeMatch ?:  null
        );

        $rAction->setServerUrlDefault(
            ServerPathUrl::_($services->from('/')->get(iHttpRequest::class))
                ->getServerUrl()
        );

        return $rAction;
    }
}

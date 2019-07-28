<?php
namespace Module\HttpFoundation\Actions;

use Module\HttpFoundation\Request\Plugin\ServerPathUrl;
use Poirot\Ioc\Container\Service\aServiceContainer;


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
            $services->from('/')->get('Router')
            , $services->from('/')->get('HttpRequest-Psr')
            , $routeMatch ??  null
        );

        $rAction->setServerUrlDefault(
            ServerPathUrl::_($services->from('/')->get('HttpRequest'))
                ->getServerUrl()
        );

        return $rAction;
    }
}

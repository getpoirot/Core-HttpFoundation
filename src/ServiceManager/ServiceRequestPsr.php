<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Http\Psr\ServerRequestBridgeInPsr;
use Poirot\Ioc\Container\Service\aServiceContainer;


class ServiceRequestPsr
    extends aServiceContainer
{
    /** @var string Service Name */
    protected $name = 'HttpRequest-Psr';


    /**
     * Create Service
     *
     * @return mixed
     */
    function newService()
    {
        $req = $this->services()->from('/')->get('HttpRequest');
        return new ServerRequestBridgeInPsr($req);
    }
}

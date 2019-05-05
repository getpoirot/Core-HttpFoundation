<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Http\Psr\ServerRequestBridgeInPsr;
use Poirot\Ioc\Container\Service\aServiceContainer;
use Psr\Http\Message\ServerRequestInterface;


class ServiceRequestPsr
    extends aServiceContainer
{
    /** @var string Service Name */
    protected $name = 'HttpRequest-Psr';


    /**
     * Create Service
     *
     * @return ServerRequestInterface
     * @throws \Exception
     */
    function newService()
    {
        $req = $this->services()->get(iHttpRequest::class);
        return new ServerRequestBridgeInPsr($req);
    }
}

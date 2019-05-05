<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Http\Interfaces\iHttpResponse;
use Poirot\Http\Psr\ResponseBridgeInPsr;
use Poirot\Ioc\Container\Service\aServiceContainer;
use Psr\Http\Message\ResponseInterface;


class ServiceResponsePsr
    extends aServiceContainer
{
    /**
     * @var string Service Name
     */
    protected $name = 'HttpResponse-Psr';


    /**
     * Create Service
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    function newService()
    {
        $res = $this->services()->get(iHttpResponse::class);
        return new ResponseBridgeInPsr($res);
    }
}

<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Http\Psr\ResponseBridgeInPsr;
use Poirot\Ioc\Container\Service\aServiceContainer;


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
     * @return mixed
     */
    function newService()
    {
        $res = $this->services()->from('/')->get('HttpResponse');
        return new ResponseBridgeInPsr($res);
    }
}

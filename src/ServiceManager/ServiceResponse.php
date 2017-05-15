<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Http\HttpMessage\Response\BuildHttpResponse;
use Poirot\Http\HttpResponse;
use Poirot\Http\HttpMessage\Response\DataParseResponsePhp;

use Poirot\Ioc\Container\Service\aServiceContainer;


class ServiceResponse
    extends aServiceContainer
{
    /** @var string Service Name */
    protected $name = 'HttpResponse';


    /**
     * Create Service
     *
     * @return mixed
     */
    function newService()
    {
        $setting  = new DataParseResponsePhp;
        $response = new HttpResponse(
            new BuildHttpResponse( BuildHttpResponse::parseWith($setting) )
        );
        
        return $response;
    }
}

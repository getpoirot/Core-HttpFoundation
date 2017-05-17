<?php
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Http\Interfaces\iHttpResponse;
use Poirot\Router\Interfaces\iRouterStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


return [
    'implementations' => [
        'HttpRequest'  => iHttpRequest::class,
        'HttpResponse' => iHttpResponse::class,
        'Router'       => iRouterStack::class,

        'HttpRequest-Psr'  => RequestInterface::class,
        'HttpResponse-Psr' => ResponseInterface::class,
    ],
    'services' => [
        \Module\HttpFoundation\ServiceManager\ServiceRequest::class,
        \Module\HttpFoundation\ServiceManager\ServiceResponse::class,
        \Module\HttpFoundation\ServiceManager\ServiceRouter::class,

        \Module\HttpFoundation\ServiceManager\ServiceRequestPsr::class,
        \Module\HttpFoundation\ServiceManager\ServiceResponsePsr::class,
    ],
];

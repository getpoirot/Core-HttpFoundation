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
        'HttpRequest'     => \Module\HttpFoundation\ServiceManager\ServiceRequest::class,
        'HttpRequest-Psr' => \Module\HttpFoundation\ServiceManager\ServiceRequestPsr::class,

        'HttpResponse'     => \Module\HttpFoundation\ServiceManager\ServiceResponse::class,
        'HttpResponse-Psr' => \Module\HttpFoundation\ServiceManager\ServiceResponsePsr::class,

        'Router' => \Module\HttpFoundation\ServiceManager\ServiceRouter::class,
    ],
];

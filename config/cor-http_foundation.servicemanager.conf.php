<?php

return [
    'implementations' => [
        'HttpRequest'  => \Poirot\Http\Interfaces\iHttpRequest::class,
        'HttpResponse' => \Poirot\Http\Interfaces\iHttpResponse::class,
        'Router'       => \Poirot\Router\Interfaces\iRouterStack::class,

        'HttpRequest-Psr'  => \Psr\Http\Message\RequestInterface::class,
        'HttpResponse-Psr' => \Psr\Http\Message\ResponseInterface::class,
    ],
    'services' => [
        \Module\HttpFoundation\ServiceManager\ServiceRequest::class,
        \Module\HttpFoundation\ServiceManager\ServiceResponse::class,
        \Module\HttpFoundation\ServiceManager\ServiceRouter::class,

        \Module\HttpFoundation\ServiceManager\ServiceRequestPsr::class,
        \Module\HttpFoundation\ServiceManager\ServiceResponsePsr::class,
    ],
];

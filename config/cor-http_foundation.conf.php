<?php
use Module\HttpFoundation\Router\PreparatorHandleBaseUrl;
use Module\HttpFoundation\ServiceManager\ServiceRouter;

return [
    \Module\Foundation\Services\PathService::CONF => [
        'paths' => [
            // According to route name 'www-assets' to serve statics files
            // @see cor-http_foundation.routes
            'www-assets' => "\$baseUrl/p/assets/",
        ],
        'variables' => [
            'serverUrl' => function() { return \Module\HttpFoundation\getServerUrl(); },
            'basePath'  => function() { return \Module\HttpFoundation\getBasePath(); },
            'baseUrl'   => function() { return \Module\HttpFoundation\getBaseUrl(); },
        ],
    ],

    ServiceRouter::CONF => [
        // ( ! ) note: Change Config Of Router In Specific Case That You Aware Of It!!
        //             may corrupt routing behaviour

        // router stack name; this name will prefixed to route names
        // exp. main/home
        'route_name' => 'main',
        'preparator' => new \Poirot\Ioc\instance(
            PreparatorHandleBaseUrl::class
        ),
        'params' => [
            // default router params merge with matched route
        ],
    ],
];

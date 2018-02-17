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
        // Instance will instantiated while Merge Config Loaded !!
        'preparator' => new \Poirot\Ioc\instance(
            // TODO when uploaded file size exceeds the server allowed size; exception rise from within this
            //      Error While Instancing Merged Config; because of instance command
            PreparatorHandleBaseUrl::class
        ),
        'params' => [
            // default router params merge with matched route
        ],
    ],
];

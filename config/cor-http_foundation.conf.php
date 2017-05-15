<?php
return [
    \Module\HttpFoundation\ServiceManager\ServiceRouter::CONF_KEY
    => [
        // ( ! ) note: Change Config Of Router In Specific Case That You Aware Of It!!
        //             may corrupt routing behaviour

        // router stack name; this name will prefixed to route names
        // exp. main/home
        'route_name' => 'main',
        'preparator' => new \Poirot\Ioc\instance(
            \Module\HttpFoundation\Router\PreparatorHandleBaseUrl::class
        ),
        'params' => [
            // default router params merge with matched route
        ],
    ],
];

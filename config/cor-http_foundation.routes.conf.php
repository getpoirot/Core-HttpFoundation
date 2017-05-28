<?php
use Module\HttpFoundation\Events\Listener\ListenerDispatch;


return [
    'home'  => [
        'route'    => 'RouteSegment',
        ## 'allow_override' => true, ## default is true
        'options' => [
            'criteria'    => '/',
            'match_whole' => true,
        ],
        'params'  => [
            ListenerDispatch::ACTIONS => function() { return []; },
        ],
    ],
    'www-assets' => [
        'route' => 'RouteMethodSegment',
        'options' => [
            'method'   => 'GET',
            'criteria' => '/p/assets/:file~.+~',
            'match_whole' => false,
        ],
        'params' => [
            ListenerDispatch::ACTIONS => \Poirot\Ioc\newInitIns( new \Poirot\Ioc\instance(
                '/module/httpfoundation/actions/FileServeAction'
                , [ 'baseDir' => PT_DIR_ROOT.'/www' ]
            ) ),
        ],
    ],
];
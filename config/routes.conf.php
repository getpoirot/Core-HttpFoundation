<?php
use Module\HttpFoundation\Events\Listener\ListenerDispatch;
use Module\HttpFoundation\Events\Listener\ListenerDispatchResult;


return [
    'home'  => [
        'route'    => 'RouteSegment',
        ## 'allow_override' => true, ## default is true
        'options' => [
            'criteria'    => '/',
            'match_whole' => true,
        ],
        'params'  => [
            ListenerDispatch::ACTIONS => function() {
                return [
                    ListenerDispatchResult::RESULT_DISPATCH => [
                        'message' => 'Welcome!'
                    ],
                ];
            },
            /*
            RenderRouterStrategy::ConfRouteParam => [
                'strategy' => 'json',
            ],
            */
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
                \Module\HttpFoundation\Actions\FileServeAction::class
                , [ 'baseDir' => PT_DIR_ROOT.'/www' ]
            ) ),
        ],
    ],
];

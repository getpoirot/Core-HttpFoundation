<?php
namespace Module\HttpFoundation;

use Module\HttpFoundation\Events\Listener\ListenerDispatch;
use Module\HttpFoundation\Events\Listener\ListenerFinish;
use Module\HttpFoundation\Events\Listener\ListenerMatchRequest;
use Poirot\Application\Interfaces\iApplication;
use Poirot\Application\Interfaces\Sapi\iSapiModule;
use Poirot\Application\aSapi;
use Poirot\Application\Interfaces\Sapi;
use Poirot\Application\ModuleManager\Interfaces\iModuleManager;
use Poirot\Application\Sapi\Event\EventHeapOfSapi;
use Poirot\Application\Sapi\Module\ContainerForFeatureActions;
use Poirot\Application\SapiHttp;

use Poirot\Http\Interfaces\Respec\iRequestAware;
use Poirot\Http\Interfaces\Respec\iResponseAware;
use Poirot\Ioc\Container;
use Poirot\Ioc\Container\BuildContainer;

use Poirot\Loader\Autoloader\LoaderAutoloadAggregate;
use Poirot\Loader\Autoloader\LoaderAutoloadNamespace;
use Poirot\Loader\Interfaces\iLoaderAutoload;

use Poirot\Router\BuildRouterStack;
use Poirot\Router\Interfaces\iRouterStack;

use Poirot\Std\Interfaces\Struct\iDataEntity;


class Module implements iSapiModule
    , Sapi\Module\Feature\iFeatureModuleInitSapi
    , Sapi\Module\Feature\iFeatureModuleAutoload
    , Sapi\Module\Feature\iFeatureModuleInitModuleManager
    , Sapi\Module\Feature\iFeatureModuleInitServices
    , Sapi\Module\Feature\iFeatureModuleInitSapiEvents
    , Sapi\Module\Feature\iFeatureModuleNestActions
    , Sapi\Module\Feature\iFeatureOnPostLoadModulesGrabServices
    , Sapi\Module\Feature\iFeatureModuleMergeConfig
{
    /**
     * Init Module Against Application
     *
     * - determine sapi server, cli or http
     *
     * priority: 1000 A
     *
     * @param iApplication|aSapi $sapi Application Instance
     *
     * @return false|null False mean not setup with other module features (skip module)
     * @throws \Exception
     */
    function initialize($sapi)
    {
        if ( \Poirot\isCommandLine( $sapi->getSapiName() ) )
            // Sapi Is Not HTTP. SKIP Module Load!!
            return false;
    }

    /**
     * Register class autoload on Autoload
     *
     * priority: 1000 B
     *
     * @param LoaderAutoloadAggregate $baseAutoloader
     *
     * @return iLoaderAutoload|array|\Traversable|void
     */
    function initAutoload(LoaderAutoloadAggregate $baseAutoloader)
    {
        #$nameSpaceLoader = \Poirot\Loader\Autoloader\LoaderAutoloadNamespace::class;
        $nameSpaceLoader = 'Poirot\Loader\Autoloader\LoaderAutoloadNamespace';
        /** @var LoaderAutoloadNamespace $nameSpaceLoader */
        $nameSpaceLoader = $baseAutoloader->loader($nameSpaceLoader);
        $nameSpaceLoader->addResource(__NAMESPACE__, __DIR__);

        require_once __DIR__.'/_ioc-facade.php';
    }

    /**
     * Initialize Module Manager
     *
     * priority: 1000 C
     *
     * @param iModuleManager $moduleManager
     *
     * @return void
     */
    function initModuleManager(iModuleManager $moduleManager)
    {
        // ( ! ) ORDER IS MANDATORY

        if (! $moduleManager->hasLoaded('Foundation') )
            // Module Is Required.
            $moduleManager->loadModule('Foundation');

    }

    /**
     * Register config key/value
     *
     * priority: 1000 D
     *
     * - you may return an array or Traversable
     *   that would be merge with config current data
     *
     * @param iDataEntity $config
     *
     * @return array|\Traversable
     */
    function initConfig(iDataEntity $config)
    {
        return \Poirot\Config\load(__DIR__ . '/../../config/cor-http_foundation');
    }

    /**
     * Build Service Container
     *
     * priority: 1000 X
     *
     * - register services
     * - define aliases
     * - add initializers
     * - ...
     *
     * @param Container $services
     *
     * @return array|\Traversable|void Container Builder Config
     */
    function initServiceManager(Container $services)
    {
        # Initialize service dependencies
        $services->initializer()->addCallable(function($serviceInstance) use ($services) {
            if ($serviceInstance instanceof iRequestAware)
                $serviceInstance->setRequest( $services->get('HttpRequest') );

            if ($serviceInstance instanceof iResponseAware)
                $serviceInstance->setResponse( $services->get('HttpResponse') );

            if (method_exists($serviceInstance, 'setRouter'))
                $serviceInstance->setRouter( $services->get('Router') );
        });

        return \Poirot\Config\load(__DIR__ . '/../../config/cor-http_foundation.servicemanager');
    }

    /**
     * Attach Listeners To Application Events
     * @see ApplicationEvents
     *
     * priority: Just Before Dispatch Request When All Modules Loaded
     *           Completely
     *
     * @param EventHeapOfSapi $events
     *
     * @return void
     */
    function initSapiEvents(EventHeapOfSapi $events)
    {
        // EVENT: Sapi Route Match .......................................................

        # match request then followed by dispatch
        $events->on(
            EventHeapOfSapi::EVENT_APP_MATCH_REQUEST
            , new ListenerMatchRequest
            , -10
        );


        // EVENT: Dispatch Matched Route .................................................
        //        Default CoR Action Dispatcher For Http
        $events->on(
            EventHeapOfSapi::EVENT_APP_DISPATCH
            , new Events\Listener\ListenerAssertRouteMatch
            , -999
        );

        # dispatch matched route
        $events->on(
            EventHeapOfSapi::EVENT_APP_DISPATCH
            , new Events\Listener\ListenerDispatch
            , -1000
        );


        // EVENT: Finish Request To Response .............................................

        $events->on(
            EventHeapOfSapi::EVENT_APP_FINISH
            , new ListenerFinish
            , -1000
        );
    }

    /**
     * Get Action Services
     *
     * priority: after GrabRegisteredServices
     *
     * - return Array used to Build ModuleActionsContainer
     *
     * @return array|ContainerForFeatureActions|BuildContainer|\Traversable
     */
    function getActions()
    {
        return new BuildContainerActionOfModule;
    }

    /**
     * Resolve to service with name
     *
     * - each argument represent requested service by registered name
     *   if service not available default argument value remains
     * - "services" as argument will retrieve services container itself.
     *
     * ! after all modules loaded
     *
     * @param iRouterStack                   $router
     *
     * @internal param null $services service names must have default value
     */
    function resolveRegisteredServices($router = null)
    {
        # Register Routes:
        $this->_setupHttpRouter($router);
    }


    // ...

    /**
     * Setup Http Stack Router
     *
     * @param iRouterStack $router
     *
     * @return void
     */
    protected function _setupHttpRouter(iRouterStack $router)
    {
        $buildRoute = new BuildRouterStack();
        $buildRoute->setRoutes([
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
        ]);
        
        $buildRoute->build($router);
    }
}

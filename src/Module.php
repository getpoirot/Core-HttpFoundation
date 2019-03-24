<?php
namespace Module\HttpFoundation
{
    use Module\HttpFoundation\Events\Listener\ListenerAssertRouteMatch;
    use Module\HttpFoundation\Events\Listener\ListenerDispatch;
    use Module\HttpFoundation\Events\Listener\ListenerDispatchResult;
    use Module\HttpFoundation\Events\Listener\ListenerFinish;
    use Module\HttpFoundation\Events\Listener\ListenerMatchRequest;
    use Poirot\Application\Interfaces\Sapi\iSapiModule;
    use Poirot\Application\Interfaces\Sapi;
    use Poirot\Application\ModuleManager\Interfaces\iModuleManager;
    use Poirot\Application\Sapi\Event\EventHeapOfSapi;

    use Poirot\Http\Interfaces\Respec\iRequestAware;
    use Poirot\Http\Interfaces\Respec\iResponseAware;
    use Poirot\Ioc\Container;

    use Poirot\Loader\Autoloader\LoaderAutoloadAggregate;
    use Poirot\Loader\Autoloader\LoaderAutoloadNamespace;

    use Poirot\Router\BuildRouterStack;
    use Poirot\Router\Interfaces\iRouterStack;

    use Poirot\Std\Interfaces\Struct\iDataEntity;


    /**
     * - Base Services:
     *   > current request
     *     HttpRequest, HttpRequest-Psr,
     *   > response
     *     HttpResponse, HttpResponse-Psr,
     *
     *   Router.
     *
     *     ! to get latest message psr always instance as fresh from container
     *
     *   add container initializer(s) for http message aware and router aware.
     *
     * - Dispatch Request Matching Within Defined Routes.
     * - Flush Response To Client.
     *
     * - Actions Helpers:
     *   Url reverse lookup and assemble of router to make url.
     *   FlashMessage provide message flash with ability to pass object(s) as message.
     *   FileServer flush and serve file requested.
     *   HtmlHeadTitle
     *   HtmlScript
     *   HtmlLink
     *
     * - Provide variables for cor-foundation Path service.
     *   serverUrl, basePath, baseUrl
     *
     *   @see cor-http_foundation.conf.php
     *
     *
     * - Provide functions such as mime-type detection, etc...
     *
     *   @see _functions.php
     *
     *
     * - With defined route name "www-assets" as fileServe all static file
     *   from within PT_DIR_ROOT/www are accessible.
     *
     *   also define a static path "www-assets" point to this url.
     *
     *   @see cor-http_foundation.routes.conf.php
     */
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
         * @inheritdoc
         */
        function initialize($sapi)
        {
            if ( \Poirot\isCommandLine( $sapi->getSapiName() ) )
                // Sapi Is Not HTTP. SKIP Module Load!!
                return false;
        }

        /**
         * @inheritdoc
         */
        function initAutoload(LoaderAutoloadAggregate $baseAutoloader)
        {
            /** @var LoaderAutoloadNamespace $nameSpaceLoader */
            $nameSpaceLoader = $baseAutoloader->loader(LoaderAutoloadNamespace::class);
            $nameSpaceLoader->addResource(__NAMESPACE__, __DIR__);
        }

        /**
         * @inheritdoc
         */
        function initModuleManager(iModuleManager $moduleManager)
        {
            // Module Is Required.
            if (! $moduleManager->hasLoaded('Foundation') )
                $moduleManager->loadModule('Foundation');

        }

        /**
         * @inheritdoc
         */
        function initConfig(iDataEntity $config)
        {
            return \Poirot\Config\load(__DIR__ . '/../config/cor-http_foundation');
        }

        /**
         * @inheritdoc
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

            return \Poirot\Config\load(__DIR__ . '/../config/cor-http_foundation.servicemanager');
        }

        /**
         * @inheritdoc
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
                , ListenerAssertRouteMatch::WEIGHT
            );

            # dispatch matched route
            $events->on(
                EventHeapOfSapi::EVENT_APP_DISPATCH
                , new Events\Listener\ListenerDispatch
                , ListenerDispatch::WEIGHT
            );

            $events->on(
                EventHeapOfSapi::EVENT_APP_DISPATCH
                , new Events\Listener\ListenerDispatchResult
                , ListenerDispatchResult::WEIGHT
            );


            // EVENT: Finish Request To Response .............................................

            $events->on(
                EventHeapOfSapi::EVENT_APP_FINISH
                , new ListenerFinish
                , -1000
            );
        }

        /**
         * @inheritdoc
         */
        function getActions()
        {
            return new BuildContainerActionOfModule;
        }

        /**
         * @inheritdoc
         *
         * @param iRouterStack $router
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
            $routes = include __DIR__ . '/../config/cor-http_foundation.routes.conf.php';
            $buildRoute = new BuildRouterStack();
            $buildRoute->setRoutes($routes);

            $buildRoute->build($router);
        }
    }
}

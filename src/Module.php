<?php
namespace Module\HttpFoundation
{

    use Module\Foundation\Services\PathService\PathAction;
    use Module\HttpFoundation\Events\Listener\ListenerAssertRouteMatch;
    use Module\HttpFoundation\Events\Listener\ListenerDispatch;
    use Module\HttpFoundation\Events\Listener\ListenerDispatchResult;
    use Module\HttpFoundation\Events\Listener\ListenerFinish;
    use Module\HttpFoundation\Events\Listener\ListenerMatchRequest;
    use Module\HttpFoundation\Router\PreparatorHandleBaseUrl;
    use Poirot\Application\Interfaces\Sapi\iSapiModule;
    use Poirot\Application\Interfaces\Sapi;
    use Poirot\Application\ModuleManager\Interfaces\iModuleManager;
    use Poirot\Application\Sapi\Event\EventHeapOfSapi;

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
     *
     *
     * - Configuration:
     *   module is configurable by override "cor-http_foundation"
     *
     */
    class Module implements iSapiModule
        , Sapi\Module\Feature\iFeatureModuleInitSapi
        , Sapi\Module\Feature\iFeatureModuleAutoload
        , Sapi\Module\Feature\iFeatureModuleInitModuleManager
        , Sapi\Module\Feature\iFeatureModuleMergeConfig
        , Sapi\Module\Feature\iFeatureModuleInitServices
        , Sapi\Module\Feature\iFeatureModuleInitSapiEvents
        , Sapi\Module\Feature\iFeatureModuleNestActions
        , Sapi\Module\Feature\iFeatureOnPostLoadModulesGrabServices
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
            return include __DIR__ . '/../config/cor-http_foundation.servicemanager.conf.php';
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
         * @param PathAction   $path   @IoC /module/foundation/services/Path
         */
        function resolveRegisteredServices(
            $router = null
            , $path = null
        ) {
            ## Register Routes:
            #
            $this->_setupHttpRouter($router);


            ## Register Paths and Variables:
            #
            if ($path)
            {
                // According to route name 'www-assets' to serve statics files
                // @see cor-http_foundation.routes
                $path->setPath('www-assets', "\$baseUrl/p/assets/");
                $path->setVariables([
                    'serverUrl' => function() { return \Module\HttpFoundation\getServerUrl(); },
                    'basePath'  => function() { return \Module\HttpFoundation\getBasePath(); },
                    'baseUrl'   => function() { return \Module\HttpFoundation\getBaseUrl(); },
                ]);
            }


            // Add BaseURL Strip From URI's
            // TODO when uploaded file size exceeds the server allowed size; exception rise from within this
            //      Error While Instancing Merged Config; because of instance command
            $router->setPreparator(new PreparatorHandleBaseUrl($path));
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

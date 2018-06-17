<?php
namespace Module\HttpFoundation\Events\Listener;

use Poirot\Application\aSapi;
use Poirot\Events\Listener\aListener;
use Poirot\Ioc\Container;
use Poirot\Ioc\instance;
use Poirot\Router\Interfaces\iRoute;
use Poirot\Router\Interfaces\iRouterStack;
use Poirot\Std\Interfaces\Struct\iDataEntity;
use Poirot\Std\InvokableResponder;
use Poirot\Std\Type\StdTravers;


/**
 *
 * 'routes' => [
 *    'route_name' => [
 *       'route' => 'RouteSegment',
 *          .. route options
 *       ],
 *
 *       When Route Match With Params From Route:
 *
 *       (1) not contains "action" key:
 *       'params'  => [
 *           'name'   => "payam",
 *           'family' => "naderi",
 *        .. all sent as result
 *
 *
 *       (2) contains "action" key:
 *           (2_1)
 *           'params'  => [
 *               'action' => [
 *                    ** callable,
 *                    callable($result_from_previous_callable_as_argument),
 *               .. result chain between callable
 *           (2_2)
 *           'params'  => [
 *               'action' => callable
 *
 *           ** callable: invokable, callable, or registered service like "/module/application/action/view_page"
 *           ** callable: can get main registered services as argument function($request, $services)
 *                        "request" is registered service
 *
 *           (2_3)
 *           Array (
 *             ['/module/oauth2/actions/AssertAuthToken'] => 'token'
 *             [1] => Array (
 *               [0] => /module/foundation/actions/ParseRequestData
 *               [1] => /module/oauth2/actions/Register
 *               ...
 *
 *
 * ListenerDispatch::CONF => [
 *    'main/auth/signin' => [
 *       'params' => [
 *          ListenerDispatch::ACTIONS => [
 *            10 => function($result = null) {
 *               kd($result);
 *            }
 *          ],
 *        ],
 *   ],
 * ],
 */
class ListenerDispatch
    extends aListener
{
    const CONF   = 'route-actions';
    const WEIGHT = -1000;

    const ACTIONS = 'action';
    const RESULT_DISPATCH = 'result';


    /** @var Container */
    protected $sc;

    /** @var Container */
    protected $_t__services;


    /**
     * @param null   $result
     * @param iRoute $route_match
     * @param aSapi  $sapi
     *
     * @return array|void
     */
    function __invoke($result = null, $route_match = null, $sapi = null)
    {
        $this->_t__services = $services = $sapi->services();

        if ($result !== null)
            // Do Nothing; Result Generated
            return;

        if (! $route_match instanceof iRoute )
            ## do nothing, unknown route match
            return null;

        
        ## Event
        #  : before run action
        #
        $params = $this->_beforeActionsExecuted($route_match);

        if (! isset($params[self::ACTIONS]) )
            ## params as result to renderer..
            return $params;


        $result = &$params;
        $action = $params[self::ACTIONS];
        unset( $params[self::ACTIONS] ); // other route params as argument for actions

        $invokable = $this->_resolveActionInvokable($action, $params);

        $result = call_user_func($invokable);
        return $result;
    }


    // ..

    /**
     * Invoke Callable Action
     *
     * @param callable $action
     * @param array    $params
     * @param null     $identifer Custom execution identifier result
     *
     * @return callable
     */
    protected function _resolveActionInvokable(/*callable*/$action, $params, $identifer = null)
    {
        if (! is_callable($action) ) {
            if (is_string($action))
                $action = $this->_resolveStringToCallable($action, $params);
            elseif (is_array($action)) {
                /**
                 * Array (
                 *   ['/module/oauth2/actions/AssertAuthToken'] => 'token'
                 *   [1] => Array (
                 *      [0] => /module/foundation/actions/ParseRequestData
                 *      [1] => /module/oauth2/actions/Register
                 *   ...
                 */
                // Action Chains And Result Collector
                $invokable = new InvokableResponder(function () use ($params) { return $params; });
                if ($identifer)
                    // Set Specific Identifier RESULT When Action executed and returned.
                    $invokable->setIdentifier($identifer);

                foreach($action as $actIndex => $act) {
                    if (! is_int($actIndex) ) {
                        // ['/module/oauth2/actions/AssertAuthToken'] => 'token'
                        $identifer = $act;
                        $act       = $this->_resolveActionInvokable($actIndex, $params, $identifer);
                    }
                    else
                        $act = $this->_resolveActionInvokable($act, $params);

                    $invokable = $invokable->thenWith($act, null, $identifer);
                }

                $action = $invokable;
            }
        }

        if (! is_callable($action) )
            throw new \RuntimeException(sprintf(
                'Action Must Be Callable; given: (%s).', \Poirot\Std\flatten($action)
            ));


        ## get required services from module::initServicesWhenModulesLoaded
        $requiredParams = array();
        $reflectParams = \Poirot\Std\Invokable\reflectCallable($action)->getParameters();
        foreach($reflectParams as $reflectionParam)
            // ['router', ...]
            $requiredParams[] = $reflectionParam->getName();

        // ['router' => iHRouter] attain service object from name
        $availableArgs = $this->_attainRequestedServicesFromContainer(
            $this->_t__services
            , $requiredParams
        );

        // route params is on higher priority that services if given
        $availableArgs = array_merge($availableArgs, $params);

        try {
            $reflection       = \Poirot\Std\Invokable\reflectCallable($action);
            $matchedArguments = \Poirot\Std\Invokable\resolveArgsForReflection($reflection, $availableArgs);
        } catch (\Exception $e ) {
            throw new \RuntimeException(sprintf(
                'The Arguments (%s) cant resolved neither with params or available arguments for action (%s).'
                , implode(', ', $reflectParams), get_class($action)
            ));
        }

        if (array_intersect_key($matchedArguments, $availableArgs) === $matchedArguments) {
            ## invoke method with resolved arguments
            ## all arguments is resolved from ioc container and given parameters
            return $action = function() use ($action, $matchedArguments) {
                return call_user_func_array($action, $matchedArguments);
            };
        }
        ## else:
        ## It has arguments that must resolve from previous action chains and default params
        ## give current given options to action and make runtime function with arguments that not resolved

        // build function arguments "$identifier = null, $flag = false"
        if ($matchedArguments === null)
            $matchedArguments = $requiredParams;

        $args = []; $replacement = '';
        $d = array_diff_key($matchedArguments, $availableArgs);
        foreach ($d as $k => $v) {
            $v    = var_export($v ,true);
            $args[$k] = "\${$k} = {$v}";
            // Add TypeHint So Let Resolver To Resolve By TypeHint
            /** @var \ReflectionParameter $rp */
            foreach ($reflectParams as $i => $rp) {
                $name = $rp->getName();
                if ($name !== $k)
                    continue;

                $typeHint = null;
                if (method_exists($rp, 'getType')) {
                    // PHP7
                    $typeHint = $rp->getType();
                    $args[$name] = $typeHint.' '.$args[$name];
                }

                unset($reflectParams[$i]);
            }

            // build argument replacement "$matchedArguments['identifier'] = $identifier;"
            $replacement .= "\$matchedArguments['{$k}'] = \${$k};";
        }



        $args = implode(', ', $args);
        $evalFunc = "return function({$args}) use (\$action, \$matchedArguments) {
            $replacement
            return call_user_func_array(\$action, \$matchedArguments);
        };";

        $action = eval($evalFunc);
        return $action;
    }

    /**
     * @param Container $services
     * @param array     $requiredServices
     * @return array
     */
    protected function _attainRequestedServicesFromContainer($services, $requiredServices)
    {
        $params = array();
        foreach($requiredServices as $serviceName) {
            if ($serviceName == 'services')
                ## container self as "services" name
                $service = $services;
            else {
                if (!$services->has($serviceName))
                    continue;

                $service = $services->get($serviceName);
            }

            $params[$serviceName] = $service;
        }

        return $params;
    }

    /**
     * Resolve to aResponder
     *
     * - action name from nested containers:
     *   '/module/application/action/view_page'
     *   from module->application.action, get view_page action
     *
     * @param string    $aResponder
     *
     * @return callable
     */
    protected function _resolveStringToCallable($aResponder, $params)
    {
        /** @see ListenerInitNestedContainer */
        $services   = $this->_t__services;

        if ( class_exists($aResponder) )
            return $aResponder = \Poirot\Ioc\newInitIns(new instance($aResponder, $params), $services);


        ## get action from service container
        #
        try {
            $aResponder = $services->get( $aResponder, $params );
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Dispatcher cant resolve to (%s).', $aResponder), 500, $e
            );
        }

        return $aResponder;
    }


    /**
     * Gatter Actions Executable
     *
     * @param iRouterStack $route_match
     *
     * @return array
     */
    private function _beforeActionsExecuted(iRouterStack $route_match)
    {
        ## Retrieve Params From Route
        #
        $params = $route_match->params();
        if ( $params->count() )
            $params = StdTravers::of($params)->toArray();


        ## Params From Merged Config
        #
        $routeName = $route_match->getName();
        if ( $conf = $this->_getConf($routeName, 'params') ) {
            $params = \Poirot\Router\mergeRecursive($conf, $params);
        }


        return $params;
    }

    /**
     * Get Config Values
     *
     * Argument can passed and map to config if exists [$key][$_][$__] ..
     *
     * @param $key
     * @param null $_
     *
     * @return mixed|null
     * @throws \Exception
     */
    protected function _getConf($key = null, $_ = null)
    {
        // retrieve and cache config
        $services = $this->_t__services;

        /** @var aSapi $config */
        $config = $services->get('/sapi');
        $config = $config->config();
        /** @var iDataEntity $config */
        $config = $config->get( self::CONF, [] );
        foreach (func_get_args() as $key) {
            if (! isset($config[$key]) )
                return null;

            $config = $config[$key];
        }

        return $config;
    }
}

<?php
namespace Module\HttpFoundation\Events\Listener;

use Poirot\Application\aSapi;
use Poirot\Events\Listener\aListener;
use Poirot\Ioc\Container;
use Poirot\Router\Interfaces\iRoute;
use Poirot\Std\InvokableResponder;


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
 * @see SapiHttp::_attachToEvents
 */
class ListenerDispatch
    extends aListener
{
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

        if (! $route_match instanceof iRoute )
            ## do nothing, unknown route match
            return null;

        
        # setup action responders:
        $params = \Poirot\Std\cast($route_match->params())->toArray();
        if (! isset($params[self::ACTIONS]) )
            ## params as result to renderer..
            return [ self::RESULT_DISPATCH => $params ];


        $result  = &$params;
        $action  = $params[self::ACTIONS];
        unset( $params[self::ACTIONS] ); // other route params as argument for actions

        $invokable = $this->_resolveActionInvokable($action, $params);

        $result = call_user_func($invokable);

        /// With Chains Invokable we can define usable result
        //- return array(
        //-   ListenerDispatch::RESULT_DISPATCH => $r
        //- );
        if ( is_array($result) && isset($result[self::RESULT_DISPATCH]) )
            $result = $result[self::RESULT_DISPATCH];

        // $result that will resolve to SAPI events
        return [ self::RESULT_DISPATCH => $result ];
    }


    // ..

    /**
     * Invoke Callable Action
     *
     * @param callable $action
     * @param array    $params
     *
     * @return callable
     * @throws \Exception
     */
    protected function _resolveActionInvokable(/*callable*/$action, $params)
    {
        if (! is_callable($action) ) {
            if (is_string($action))
                $action = $this->_getActionFromServices($action, $params);
            elseif (is_array($action)) {
                /**
                 * Array (
                 *   [0] => /module/oauth2/actions/AssertAuthToken
                 *   [1] => Array (
                 *      [0] => /module/foundation/actions/ParseRequestData
                 *      [1] => /module/oauth2/actions/Register
                 *   ...
                 */
                // Action Chains And Result Collector
                $invokable = new InvokableResponder(function () use ($params) { return $params; });
                foreach($action as $act) {
                    $act = $this->_resolveActionInvokable($act, $params);
                    $invokable = $invokable->thenWith($act);
                }

                $action = $invokable;
            }
        }

        if (!is_callable($action))
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
                'The Arguments (%s) cant resolved neither with params or available arguments for action reflection.'
                , implode(', ', $reflectParams)
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

                $typeHint = $rp->getType();
                $args[$name] = $typeHint.' '.$args[$name];
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
    protected function _getActionFromServices($aResponder, $params)
    {
        ## get action from service container
        /** @see ListenerInitNestedContainer */
        $services   = $this->_t__services;
        try {
            $aResponder = $services->get( $aResponder, array('options' => $params) );
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Dispatcher cant resolve to (%s).', $aResponder), 500, $e
            );
        }

        return $aResponder;
    }
}

<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Application\aSapi;
use Poirot\Ioc\Container\Service\aServiceContainer;

use Poirot\Ioc\instance;
use Poirot\Router\Interfaces\iRouterStack;
use Poirot\Router\Interfaces\RouterStack\iPreparatorRequest;
use Poirot\Router\RouterStack;
use Poirot\Std\Struct\DataEntity;


class ServiceRouter
    extends aServiceContainer
{
    const ROUTE_NAME = 'main';
    const CONF   = 'router_stack';


    /** @var string Service Name */
    protected $name = 'Router';
    
    /** @var string */
    protected $routeName;
    /** @var iPreparatorRequest */
    protected $routePreparator;


    /**
     * Create Service
     *
     * @return iRouterStack
     */
    function newService()
    {
        $routerStack    = new RouterStack( $this->getRouteName() );
        if ($preparator = $this->getPreparator())
            $routerStack->setPreparator($preparator);

        if ($defaultParams = $this->_getConfig('params'))
            // set global router params
            $routerStack->params()->import($defaultParams);

        return $routerStack;
    }


    // options:
    
    function getRouteName()
    {
        if ($this->routeName)
            return $this->routeName;
        
        
        $routeName = $this->_getConfig('route_name');
        if ($routeName === false)
            throw new \Exception('Router Service Need Main Route Name; Nothing Given as Config Params.');
        
        return $this->routeName = $routeName;
    }
    
    function setRouteName($name)
    {
        $this->routeName = (string) $name;
        return $this;
    }

    /**
     * Set Router Preparator
     * 
     * @param iPreparatorRequest|instance $instance
     * 
     * @return $this
     */
    function setPreparator($instance)
    {
        $this->routePreparator = $instance;
        return $this;
    }

    /**
     * Get Route Preparator If Has
     * 
     * @return null|iPreparatorRequest
     * @throws \Exception
     */
    function getPreparator()
    {
        if ($this->routePreparator !== null && !$this->routePreparator instanceof iPreparatorRequest)
            throw new \Exception(sprintf(
                'Route Preparator Must Instance Of iPreparatorRequest Achieved: (%s).'
                , \Poirot\Std\flatten($this->routePreparator)
            ));
     
        if ($this->routePreparator === null) {
            // get from config if has
            $preparator = $this->_getConfig('preparator');
            if ($preparator) {
                $this->setPreparator($preparator);
                return $this->getPreparator();
            }

            return null;
        }
        
        return $this->routePreparator;
    }

    protected function _getConfig($key = null)
    {
        # Setup By Configs:
        $services = $this->services();

        /** @var aSapi $config */
        $config   = $services->get('/sapi');
        $config   = $config->config();
        /** @var DataEntity $config */
        $config   = $config->get(self::CONF, array());

        
        if ($key !== null)
            return isset($config[$key]) ? $config[$key] : false;
        
        return $config;
    }
}

<?php
namespace Module\HttpFoundation\ServiceManager;

use Poirot\Ioc\Container;
use Poirot\Ioc\Container\Interfaces\iServiceFeatureDelegate;
use Poirot\Ioc\Container\Service\aServiceContainer;

use Poirot\Ioc\instance;
use Poirot\Router\Interfaces\iRouterStack;
use Poirot\Router\Interfaces\RouterStack\iPreparatorRequest;
use Poirot\Router\RouterStack;


class ServiceRouter
    extends aServiceContainer
    implements iServiceFeatureDelegate
{
    const ROUTE_NAME = 'main';
    const CONF   = 'router_stack';


    /** @var string Service Name */
    protected $name = 'Router';
    
    /** @var string */
    protected $routeName;
    /** @var iPreparatorRequest[] */
    protected $routePreparator;


    /**
     * Create Service
     *
     * @return iRouterStack
     * @throws \Exception
     */
    function newService()
    {
        $routerStack    = new RouterStack( $this->getRouteName() );
        if ($preparator = $this->getPreparator())
            $routerStack->setPreparators($preparator);

        if ($defaultParams = \Poirot\config(\Module\HttpFoundation\Module::class, self::CONF, 'params'))
            // set global router params
            $routerStack->params()->import($defaultParams);

        return $routerStack;
    }

    /**
     * @inheritdoc
     */
    function delegate(Container $container)
    {
        # Initialize service dependencies
        $container->initializer()->addCallable(function($serviceInstance) use ($container) {
            if (method_exists($serviceInstance, 'setRouter'))
                $serviceInstance->setRouter( $container->get('Router') );
        });
    }


    // options:
    
    function getRouteName()
    {
        if ($this->routeName)
            return $this->routeName;
        
        
        $routeName = \Poirot\config(\Module\HttpFoundation\Module::class, self::CONF, 'route_name');
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
     * @return null|iPreparatorRequest[]
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
            $preparator = \Poirot\config(\Module\HttpFoundation\Module::class, self::CONF, 'preparator');
            if ($preparator) {
                $this->setPreparator($preparator);
                return $this->getPreparator();
            }

            return null;
        }
        
        return $this->routePreparator;
    }
}

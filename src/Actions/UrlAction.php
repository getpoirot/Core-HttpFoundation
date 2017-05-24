<?php
namespace Module\HttpFoundation\Actions;

use Poirot\Router\Interfaces\iRoute;
use Poirot\Router\Interfaces\iRouterStack;
use Poirot\Router\RouterStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

// TODO Ability to generate Absolute URL(server prefixed); http://server/path/to/res
// TODO pass query params merge with url

class UrlAction 
{
    /** @var RouterStack */
    protected $router;
    /** @var RequestInterface */
    protected $request;
    /** @var iRoute */
    protected $routeMatch;

    protected $_c__lastInvokedRouter;


    /**
     * UrlAction constructor.
     *
     * @param iRouterStack     $router
     * @param RequestInterface $request
     * @param iRoute           $matchedRoute
     */
    function __construct(iRouterStack $router, RequestInterface $request, iRoute $matchedRoute = null)
    {
        $this->router     = $router;
        $this->request    = $request;
        $this->routeMatch = $matchedRoute;
    }

    /**
     * Generates an url given the name of a route
     *
     * - if given route name cause to resolve to currently route match,
     *   the route object has matched parameters from route injected.
     *
     *
     * @param null|string  $routeName              If not given use current matched route name
     * @param array        $params                 Route Assemble Params
     * @param bool         $preserveCurrentRequest Use current request query params?!!
     *
     * @return mixed
     * @throws \Exception
     */
    function __invoke($routeName = null, $params = array(), $preserveCurrentRequest = false)
    {
        if ($this->router === null )
            throw new \RuntimeException('No RouteStackInterface instance provided');

        if ($routeName === null)
            ## using matched route
            $router = $this->_getMatchedRoute();
        else
            $router = $this->router->explore($routeName);

        if ($router === false)
            throw new \Exception(sprintf(
                'Cant explore to router (%s).'
                , ($routeName === null) ? 'MatchedRoute' : $routeName
            ));

        $router = clone $router;
        // TODO now disabled until routes clone params going well
        // @see aRoute __clone

        $this->_c__lastInvokedRouter = array($router, $params, $preserveCurrentRequest);
        return $this;
    }

    /**
     * Assemble Route as URI
     *
     * @return UriInterface
     */
    function uri()
    {
        // TODO using internal cache
        $router = $this->_c__lastInvokedRouter[0];
        /** @var iRouterStack $router */
        if ($params = $this->_c__lastInvokedRouter[1])
            $uri = $router->assemble($params);
        else
            $uri = $router->assemble();

        if ($preserve = $this->_c__lastInvokedRouter[2]) {
            $request = $this->request;
            $request = $request->getRequestTarget();
            if ($query = parse_url($request, PHP_URL_QUERY))
                $uri = \Poirot\Psr7\modifyUri($uri, array(
                    'query' => $query
                ));
        }

        $path   = implode('/', array_map(
            function ($p) { return rawurlencode($p); }
            , explode('/', $uri->getPath())
        ));


        $uri = $uri->withPath($path);
        $uri = $uri->withQuery(urlencode($uri->getQuery()));
        return $uri;
    }

    /**
     * Attain Route Match
     * @return iRoute
     */
    function _getMatchedRoute()
    {
        if ($this->routeMatch)
            return $this->routeMatch;

        $router            = $this->router;
        $this->routeMatch = $router->match( $this->request );
        return $this->routeMatch;
    }

    function __toString()
    {
        try {
            $return = (string) $this->uri();
        } catch (\Exception $e)
        {
            $return = $e->getMessage();
        }

        return $return;
    }
}
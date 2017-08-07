<?php
namespace Module\HttpFoundation\Actions;

use Poirot\Router\Interfaces\iRoute;
use Poirot\Router\Interfaces\iRouterStack;
use Poirot\Router\RouterStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/*
\Module\HttpFoundation\Actions::url(
    null
    , []
    , Url::DEFAULT_INSTRUCT|Url::APPEND_CURRENT_REQUEST_QUERY|Url::WITH_GIVEN_QUERY_PARAMS
    , ['query_params' => ['sort' => 'PerPrice']]
);

\Module\HttpFoundation\Actions::url('main/apanaj.admin/users/manage', ['page' => null])
// or
\Module\HttpFoundation\Actions::url('main/apanaj.admin/users/manage', [], Url::DEFAULT_INSTRUCT & ~Url::MERGE_CURRENT_ROUTE_PARAMS)
*/

// TODO BASE_URL
class Url
{
    const INSTRUCT_NOTHING             =      0b1;
    const MERGE_CURRENT_ROUTE_PARAMS   =     0b10;
    const APPEND_CURRENT_REQUEST_QUERY =    0b100;
    const ENCODE_URL                   =   0b1000;
    const WITH_GIVEN_QUERY_PARAMS      =  0b10000;
    const ABSOLUTE_URL                 = 0b100000;

    const DEFAULT_INSTRUCT = self::MERGE_CURRENT_ROUTE_PARAMS | self::ENCODE_URL;


    /** @var RouterStack */
    protected $router;
    /** @var RequestInterface */
    protected $request;
    /** @var iRoute */
    protected $routeMatch;

    protected $_c__lastInvokedRouter = [];


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
     * @param null|string $routeName      If not given use current matched route name
     * @param array       $params         Route Assemble Params
     * @param int         $instruct       URL Instruction Constants Binary
     *                                    MERGE_CURRENT_ROUTE_PARAMS|APPEND_CURRENT_REQUEST_QUERY|ENCODE_URL
     *                                    | WITH_GIVEN_QUERY_PARAMS
     *
     * @param array      $instructOptions ['query_params' => '', ..]
     *
     * @return $this
     * @throws \Exception
     */
    function __invoke(
        $routeName = null
        , $params = array()
        , $instruct = self::DEFAULT_INSTRUCT
        , array $instructOptions = array())
    {
        if ($this->router === null )
            throw new \RuntimeException('No RouteStackInterface instance provided');

        if (! is_array($params))
            throw new \InvalidArgumentException(sprintf(
                'Params must be array; given: (%s).'
                , gettype($params)
            ));


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

        $this->_c__lastInvokedRouter = [ $router, $params, $instruct, $instructOptions ];
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

        list( $router, $params, $instruct, $options ) = $this->_c__lastInvokedRouter;


        # Check For Preserving Current Request Route Params
        #
        $routeMatch = $this->_getMatchedRoute();
        if ( $routeMatch && is_array($params)
            && ($instruct & self::MERGE_CURRENT_ROUTE_PARAMS) === self::MERGE_CURRENT_ROUTE_PARAMS
        ) {
            $currParams = $routeMatch->params();
            $params     = array_merge(iterator_to_array($currParams), $params);
        }

        /** @var iRouterStack $router */
        if ($params)
            $uri = $router->assemble($params);
        else
            $uri = $router->assemble();


        # On Preserve Current Request We Also Use Query
        #
        if ( ($instruct & self::APPEND_CURRENT_REQUEST_QUERY) === self::APPEND_CURRENT_REQUEST_QUERY ) {
            $request = $this->request;
            $request = $request->getRequestTarget();
            if ($query = parse_url($request, PHP_URL_QUERY))
                $uri = \Poirot\Psr7\modifyUri($uri, array(
                    'query' => $query
                ));
        }


        # Merge With Given Query Params
        #
        if ( ($instruct & self::WITH_GIVEN_QUERY_PARAMS) === self::WITH_GIVEN_QUERY_PARAMS ) {
            if (isset($options['query_params'])) {
                $uri = \Poirot\Psr7\modifyUri($uri, array(
                    'query' => $options['query_params']
                ));
            }
        }


        # Url Encode Path
        #
        if ( ($instruct & self::ENCODE_URL) === self::ENCODE_URL ) {
            $path   = implode('/', array_map(
                function ($p) { return rawurlencode($p); }
                , explode('/', $uri->getPath())
            ));

            $uri = $uri->withPath($path);
            // $uri = $uri->withQuery(urlencode($uri->getQuery()));
        }


        # Absolute URL
        if ( ($instruct & self::ABSOLUTE_URL) === self::ABSOLUTE_URL ) {
            if (! $uri->getHost() ) {
                $serverUrl = parse_url( \Module\HttpFoundation\getServerUrl() );
                $uri = $uri->withScheme($serverUrl['scheme'])
                    ->withHost($serverUrl['host'])
                    ->withPort(@$serverUrl['port']);
            }
        } else {
            // Clear Uri Scheme:\\Host:Port\
            $uri = $uri->withScheme('')->withHost('')->withPort('');
        }

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
        } catch (\Throwable $e)
        {
            $return = $e->getMessage();
        } catch (\Exception $e)
        {
            $return = $e->getMessage();
        }


        return $return;
    }
}
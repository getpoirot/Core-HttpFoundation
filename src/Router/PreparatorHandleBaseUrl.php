<?php
namespace Module\HttpFoundation\Router;

use Module\Foundation\Services\PathService\PathAction;
use Poirot\Router\Interfaces\RouterStack\iPreparatorRequest;
use Poirot\Router\RouterStack\StripPrefix;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;


class PreparatorHandleBaseUrl
    extends StripPrefix
    implements iPreparatorRequest
{
    protected $baseUrl;


    /**
     * StripPrefix constructor.
     *
     * @param PathAction $path @IoC /module/Foundation/services/Path
     */
    function __construct(PathAction $path)
    {
        try {
            $baseUrl = $path->assemble('$baseUrl');
        } catch (\Exception $e) {
            $baseUrl = null;
        }

        if ($baseUrl && $baseUrl !== '/' ) {
            $this->baseUrl = $baseUrl;
            parent::__construct($baseUrl);
        }
    }


    /**
     * Prepare Request Object Before Match Route Against Request Object
     *
     * @param RequestInterface $request
     * @param \Closure         $nextCallableChain Closure callable to next same method from chain
     *
     * @return RequestInterface Clone
     */
    function beforeMatchRequest(RequestInterface $request, $nextCallableChain = null)
    {
        if ($this->baseUrl === null)
            // let chain continue execution or just return result
            return ($nextCallableChain) ? $nextCallableChain($request) : $request;


        return parent::beforeMatchRequest($request);
    }

    /**
     * Prepare Assembled URI
     *
     * @param UriInterface $uri
     * @param \Closure     $nextCallableChain Closure callable to next same method from chain
     *
     * @return UriInterface
     */
    function afterAssembleUri(UriInterface $uri, $nextCallableChain = null)
    {
        if ($this->baseUrl === null)
            // let chain continue execution or just return result
            return ($nextCallableChain) ? $nextCallableChain($uri) : $uri;


        return parent::afterAssembleUri($uri);
    }
}

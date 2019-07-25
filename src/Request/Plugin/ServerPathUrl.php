<?php
namespace Module\HttpFoundation\Request\Plugin;

use function Poirot\Http\Header\renderHeaderValue;

use Poirot\Http\HttpMessage\Request\Plugin\aPluginRequest;
use Poirot\Http\HttpMessage\Request\Plugin\PhpServer;
use Poirot\Http\Interfaces\iHttpRequest;


class ServerPathUrl
    extends aPluginRequest
{
    function getServerUrl()
    {
        // TODO move to Poirot Skeleton

        /** @var iHttpRequest $request */
        $request = $this->getMessageObject();
        if (getenv('PT_SERVER_URL'))
            // From Environment Variable
            $serverUrl = getenv('PT_SERVER_URL');
        elseif (defined('PT_SERVER_URL'))
            $serverUrl =  constant('PT_SERVER_URL');
        else
            $serverUrl  = $request->getProtocol().'://'.$request->getHost();


        // TODO Validate Server-Url Constant

        return rtrim($serverUrl, '/');
    }

    function getBaseUrl()
    {
        /** @var iHttpRequest $request */
        $request = $this->getMessageObject();
        if ($request->headers()->has('X-Poirot-Base-Url')) {
            // Retrieve Base Url From Server Proxy Passed By Header
            $fromProxy = renderHeaderValue($request, 'X-Poirot-Base-Url');
        }

        // TODO move to Poirot Skeleton
        if ( isset($fromProxy) )
            $basePath = ($fromProxy == 'no-value') ? '/' : $fromProxy;
        elseif (getenv('PT_BASEURL'))
            // From Environment Variable
            $basePath = getenv('PT_BASEURL');
        elseif (defined('PT_BASEURL'))
            $basePath =  constant('PT_BASEURL');
        else
            $basePath = PhpServer::_($request)->getBaseUrl();


        return rtrim($basePath, '/');
    }

    function getBasePath()
    {
        /** @var iHttpRequest $request */
        $request = $this->getMessageObject();
        $basePath = PhpServer::_($request)->getBasePath();
        return rtrim($basePath, '/');
    }
}

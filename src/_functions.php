<?php
namespace Module\HttpFoundation
{

    use Poirot\Http\HttpMessage\Request\Plugin\PhpServer;
    use Poirot\Http\Interfaces\iHeader;
    use Poirot\Http\Interfaces\iHttpRequest;


    function getServerUrl()
    {
        /** @var iHttpRequest $request */
        $request = \IOC::httpRequest();

        $server  = $request->getProtocol().'://'.$request->getHost();
        return rtrim($server, '/');
    }

    function getBasePath()
    {
        /** @var iHttpRequest $request */
        $request  = \IOC::httpRequest();
        $basePath = PhpServer::_($request)->getBasePath();
        return rtrim($basePath, '/');
    }

    function getBaseUrl()
    {
        /** @var iHttpRequest $request */
        $request  = \IOC::httpRequest();
        if ($request->headers()->has('X-Poirot-Base-Url')) {
            // Retrieve Base Url From Server Proxy Passed By Header
            $fromProxy = '';
            /** @var iHeader $h */
            foreach ($request->headers()->get('X-Poirot-Base-Url') as $h)
                $fromProxy .= $h->renderValueLine();
        }
        if (isset($fromProxy)) {
            $basePath = ($fromProxy == 'no-value') ? '/' : $fromProxy;
        } elseif (getenv('PT_BASEURL')) {
            // From Environment Variable
            $basePath = getenv('PT_BASEURL');
        } else {
            $basePath = PhpServer::_($request)->getBaseUrl();
        }
        return rtrim($basePath, '/');
    }
}

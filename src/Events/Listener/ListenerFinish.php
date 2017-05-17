<?php
namespace Module\HttpFoundation\Events\Listener;

use Poirot\Application\aSapi;
use Poirot\Events\Listener\aListener;
use Poirot\Http\HttpMessage\Response\Plugin\PhpServer;
use Poirot\Http\Interfaces\iHttpResponse;
use Poirot\View\Interfaces\iViewModel;
use Psr\Http\Message\StreamInterface;


class ListenerFinish
    extends aListener
{
    /**
     * @param iHttpResponse|iViewModel|string|StreamInterface $result
     * @param aSapi $sapi
     * @return mixed|void
     */
    function __invoke($result = null, $sapi = null)
    {
        $response = $result;

        if ($result instanceof StreamInterface) {
            $response = $sapi->services()->get('HttpResponse');
            $response->setBody( $result );
        } elseif ($result instanceof iViewModel) {
            $response = $sapi->services()->get('HttpResponse');
            $response->setBody( $result->render() );
        } elseif (\Poirot\Std\isStringify($result)) {
            $response = $sapi->services()->get('HttpResponse');
            $response->setBody( (string) $result );
        }

        if (! $response instanceof iHttpResponse )
            throw new \RuntimeException(sprintf(
                'Make Response Object From (%s) Is Unknown.'
                , \Poirot\Std\flatten($result)
            ));


        $this->_sendResponse($response);
    }


    /**
     * Send Response
     *
     * @param iHttpResponse $response
     */
    function _sendResponse(iHttpResponse $response)
    {
        PhpServer::_($response)->send();
    }
}

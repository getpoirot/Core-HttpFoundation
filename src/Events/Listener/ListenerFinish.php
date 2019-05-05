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
     *
     * @return mixed|void
     * @throws \Exception
     */
    function __invoke($result = null, $sapi = null)
    {
        $response = $result;

        if (! $response instanceof iHttpResponse )
        {
            $response = $sapi->services()->get(iHttpResponse::class);

            if ($result instanceof StreamInterface) {
                $response->setBody( $result );
            } elseif ($result instanceof iViewModel) {
                $bodyStr = $result->render();
                $response->setBody( $bodyStr );
            } elseif (\Poirot\Std\isStringify($result)) {
                $response->setBody( (string) $result );
            } else {
                throw new \RuntimeException(sprintf(
                    'Make Response Object From (%s) Is Unknown.'
                    , \Poirot\Std\flatten($result)
                ));
            }
        }


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

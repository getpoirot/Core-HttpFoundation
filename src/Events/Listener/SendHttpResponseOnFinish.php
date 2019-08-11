<?php
namespace Module\HttpFoundation\Events\Listener;

use Psr\Http\Message\StreamInterface;

use Poirot\Application\aSapi;
use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\HttpMessage\Request\Plugin\MethodType;
use Poirot\Http\HttpMessage\Response\Plugin\Status;
use Poirot\Http\HttpMessage\Response\Plugin\PhpServer;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Http\Interfaces\iHttpResponse;
use Poirot\Std\Type\StdString;
use Poirot\View\Interfaces\iViewModel;


class SendHttpResponseOnFinish
{
    /** @var aSapi */
    protected $sapi;


    /**
     * Create And Send Http Response Based On Dispatching Result
     *
     * @param iHttpResponse|iViewModel|string|StreamInterface $result
     * @param aSapi $sapi
     *
     * @return mixed|void
     * @throws \Exception
     */
    function __invoke($result = null, $sapi = null)
    {
        $this->sapi = $sapi;

        $response = $result;
        if (! $response instanceof iHttpResponse ) {
            $response = $this->_response();

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

        $response = $this->_assertResponse($response, $this->_request());
        $this->_sendResponse($response);
    }


    // ..

    /**
     * Assert Response Object and Prepare Before Send To Client
     *
     * @param iHttpResponse $response
     * @param iHttpRequest  $request
     *
     * @return iHttpResponse
     * @throws \Exception
     */
    protected function _assertResponse(iHttpResponse $response, iHttpRequest $request)
    {
        ## Check for empty response content
        #
        if (Status::_($response)->isInformational() || Status::_($response)->isEmpty()) {
            $response->setBody(null);
            $response->headers()->del('Content-Type');
            $response->headers()->del('Content-Length');

        } else {
            // Fix Content-Length
            if ($response->headers()->has('Transfer-Encoding'))
                $response->headers()->del('Content-Length');

            // cf. RFC2616 14.13
            if ( MethodType::_($request)->isHead() ) {
                $response->setBody(null);
                if ($length = $response->headers()->get('Content-Length'))
                    $response->headers()->insert(FactoryHttpHeader::of(['Content-Length', $length]));
            }
        }

        if ('1.0' == $response->getVersion()) {
            if ($response->headers()->has('Cache-Control')
                && StdString::of( $response->headers()->get('Cache-Control') )
                    ->isStartWith('no-cache')
            ) {
                $response->headers()
                    ->insert(FactoryHttpHeader::of(['pragma' => 'no-cache']))
                    ->insert(FactoryHttpHeader::of(['expires' => -1]))
                ;
            }
        }

        // @see http://support.microsoft.com/kb/323308
        // Implement it for IE6 if needed.

        return $response;
    }

    /**
     * Send Response
     *
     * @param iHttpResponse $response
     * @throws \Exception
     */
    protected function _sendResponse(iHttpResponse $response)
    {
        PhpServer::_($response)->send();
    }

    /**
     * Http Response Object
     *
     * @return iHttpResponse
     * @throws \Exception
     */
    protected function _response()
    {
        if (! $this->sapi->services()->has(iHttpResponse::class) )
            throw new \Exception('Http Response Service Not Found Within Sapi Service Locator.');

        return $this->sapi->services()
            ->get(iHttpResponse::class);
    }

    /**
     * Http Request Object
     *
     * @return iHttpRequest
     * @throws \Exception
     */
    protected function _request()
    {
        if (! $this->sapi->services()->has(iHttpRequest::class) )
            throw new \Exception('Http Request Service Not Found Within Sapi Service Locator.');

        return $this->sapi->services()
            ->get(iHttpRequest::class);
    }
}

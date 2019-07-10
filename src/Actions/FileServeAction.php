<?php
namespace Module\HttpFoundation\Actions;

use Module\HttpFoundation\Events\Listener\ListenerDispatch;
use Poirot\Http\HttpMessage\Response\BuildHttpResponse;
use Poirot\Http\HttpResponse;
use Poirot\Stream\ResourceStream;
use Poirot\Stream\Streamable;


class FileServeAction
{
    protected $baseDir;


    /**
     * FileServeAction constructor.
     * @param string $baseDir
     */
    function __construct($baseDir)
    {
        $this->baseDir = (string) $baseDir;
    }


    /**
     * @param string $file
     * @return array
     */
    function __invoke($file)
    {
        $filePath = rtrim($this->baseDir, '/').'/'.$file;
        if (! is_readable($filePath) || substr($filePath, -3) == 'php')
            // not throw not found exception
            return [ ListenerDispatch::RESULT_DISPATCH => $this->_makeResponse(404) ];


        return [ ListenerDispatch::RESULT_DISPATCH => $this->_makeFileServeResponse($filePath) ];
    }


    // ..

    private function _makeFileServeResponse($filePath)
    {
        if (false == $res = fopen($filePath, 'rb'))
            // Error while reading file
            return $this->_makeResponse(500);


        $body    = new Streamable( new ResourceStream($res) );

        $headers = [];
        $headers['Content-Length'] = $body->getSize();
        $headers['Content-Transfer-Encoding'] = 'binary';
        $headers['Content-Type']   = \Module\HttpFoundation\getMimeTypeOfFile($filePath);

        return $this->_makeResponse(200, $body, $headers);
    }

    private function _makeResponse($statusCode = 200, $body = null, array $headers = null)
    {
        $builderOptions = [ 'status_code' => $statusCode, ];
        ($body === null)    ?: $builderOptions['body'] = $body;
        ($headers === null) ?: $builderOptions['headers'] = $headers;

        $builder  = new BuildHttpResponse($builderOptions);
        $response = new HttpResponse($builder);

        return $response;
    }
}

<?php
namespace Module\HttpFoundation\ServiceManager\Request;

use Poirot\Http\HttpMessage\Request\Plugin\PhpServer;
use Poirot\Http\HttpMessage\Request\StreamBodyMultiPart;
use Poirot\Http\Interfaces\iHttpRequest;

use Poirot\Stream\ResourceStream;
use Poirot\Stream\Streamable;


class BuildHttpRequestFromPhpServer
{
    /** @var PhpServer */
    protected $server;

    protected $host;
    protected $uri;
    protected $headers;
    protected $body;
    protected $version;

    
    /**
     * Build Http Request 
     * 
     * @param iHttpRequest $request Request Instance to build
     */
    function build(iHttpRequest $request)
    {
        $request->setProtocol( $this->getProtocol() );
        $request->setMethod( $this->getMethod() );
        $request->setHost( $this->getHost() );
        $request->setVersion( $this->getVersion() );
        $request->setTarget( $this->getTarget() );
        $request->setHeaders( $this->getHeaders() );
        $request->setBody( $this->getBody() );
    }
    
    
    // Options: 

    function getProtocol()
    {
        ( !empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) )
            ? $protocol = "https"
            : $protocol = "http";

        return $protocol;
    }

    /**
     * Get Request Method
     * @see HttpRequest::setMethod
     * 
     * @return string
     */
    function getMethod()
    {
        $method = 'GET';
        if (isset($_SERVER['HTTP_METHOD']))
            $method = $_SERVER['HTTP_METHOD'];
        elseif (isset($_SERVER['REQUEST_METHOD']))
            $method = $_SERVER['REQUEST_METHOD'];

        return $method;
    }
    
    /**
     * Get Host
     * @see HttpRequest::setHost
     * 
     * @return string
     */
    function getHost()
    {
        $host = null;
        if (isset($_SERVER['HTTP_HOST']))
            ## from request headers
            $host = $_SERVER['HTTP_HOST'];
        elseif (isset($_SERVER['SERVER_NAME']))
            $host = $_SERVER['SERVER_NAME']. (
                    ( isset($_SERVER['SERVER_PORT']) ) ? ':'.$_SERVER['SERVER_PORT'] : ''
                );
        elseif (isset($_SERVER['SERVER_ADDR']))
            $host = $_SERVER['SERVER_ADDR']. (
                    ( isset($_SERVER['SERVER_ADDR']) ) ? ':'.$_SERVER['SERVER_PORT'] : ''
                );

        if (preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host))
            ## Misinterpreted IPv6-Address
            $host = '[' . $host . ']';

        return $host;
    }

    /**
     * @see HttpRequest::setVersion
     * @return mixed
     */
    function getVersion()
    {
        $version = null;
        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            $isMatch = preg_match('(\d.\d+)', $_SERVER['SERVER_PROTOCOL'], $matches);
            (!$isMatch) ?: $version = $matches[0];
        }

        return $version;
    }

    /**
     * Get Request Uri
     * @see HttpRequest::setTarget
     * 
     * @return string
     */
    function getTarget()
    {
        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        $iisUrlRewritten = (isset($_SERVER['IIS_WasUrlRewritten'])) ? $_SERVER['IIS_WasUrlRewritten'] : null;
        $unencodedUrl    = (isset($_SERVER['UNENCODED_URL']))       ? $_SERVER['UNENCODED_URL']       : null;
        if ('1' == $iisUrlRewritten && $unencodedUrl)
            return $unencodedUrl;

        // ..

        $requestUri = $_SERVER['REQUEST_URI'];

        // Check this first so IIS will catch.
        $httpXRewriteUrl = (isset($_SERVER['HTTP_X_REWRITE_URL'])) ? $_SERVER['HTTP_X_REWRITE_URL'] : null;
        if ($httpXRewriteUrl !== null)
            $requestUri = $httpXRewriteUrl;

        // Check for IIS 7.0 or later with ISAPI_Rewrite
        $httpXOriginalUrl = (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) ? $_SERVER['HTTP_X_ORIGINAL_URL'] : null;
        if ($httpXOriginalUrl !== null)
            $requestUri = $httpXOriginalUrl;

        if ($requestUri !== null)
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);


        $origPathInfo = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : '';
        if (empty($origPathInfo))
            return '/';

        return $origPathInfo;
    }

    /**
     * // TODO Authorization Header only can retrieved from apache_request_headers when send Bearer ...
     * Get Headers
     * @see HttpRequest::setHeaders
     * 
     * @return array
     */
    function getHeaders()
    {
        $headers = array();
        foreach($_SERVER as $key => $val) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
                ## host header represent separately on request object
                // if ($name === 'Host') continue;

                $headers[$name] = $val;
            } elseif(in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
                ## specific headers that not always present
                $name = strtr($key, '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
                $headers[$name] = $val;
            }
        }

        // ++-- Authorization
        if (isset($_SERVER['PHP_AUTH_PW']) && isset($_SERVER['PHP_AUTH_USER'])) {
            if (!isset($headers['Authorization'])) {
                /*
                 * note: either can use .htaccess configuration
                 * 
                 * ## FIX Missing Authorization Request Header
                 * RewriteCond %{HTTP:Authorization} ^(.*)
                 * RewriteRule .* - [e=HTTP_AUTHORIZATION:%1] 
                 */
                if (function_exists('apache_request_headers')) {
                    $apacheHeaders = apache_request_headers();
                    if (isset($apacheHeaders['Authorization']))
                        $headers['Authorization'] = $apacheHeaders['Authorization'];
                }
            }
        }

        // ++-- Cookie
        $cookie = http_build_query($_COOKIE, '', '; ');
        (empty($cookie)) ?: $headers['Cookie'] = $cookie;

        ksort($headers);
        return $headers;
    }

    /**
     * Get Body
     * @see HttpRequest::setBody
     * 
     * @return mixed
     */
    function getBody()
    {
        $headers = $this->getHeaders();
        
        if (
            $this->getMethod() == 'POST'
            && isset($headers['Content-Type'])
            && strpos($headers['Content-Type'], 'multipart') !== false
        ) {
            if ( intval($_SERVER['CONTENT_LENGTH']) > 0 && count($_POST) === 0 )
                throw new \RuntimeException(
                    'PHP discarded POST data because of request exceeding post_max_size.'
                );

            // it`s multipart POST form data
            ## input raw body not represent in php when method is POST/multipart
            #- usually when sending files or send form data in multipart
            
            // note: this will improve performance when parse request data
            //       we use $_FILES and get rid of heavy tasking parse multipart data
            
            $boundary = $headers['Content-Type'];
            preg_match('/boundary=(?P<boundary>.*)/', $boundary, $matches);
            $boundary = $matches['boundary'];

            $rawData = $_POST;

            if ( isset($_FILES) ) {
                # Convert to UploadedFileInterface
                foreach ($_FILES as $formDataName => $fileSpec)
                    $rawData[$formDataName] = \Poirot\Http\Psr\makeUploadedFileFromSpec($fileSpec);

            }

            $stream = new StreamBodyMultiPart($rawData, $boundary);
        }
        else
        {
            $stream = new Streamable\SUpstream(new ResourceStream(
                fopen('php://memory', 'r+')
            ));
        }
        
        $stream->rewind(); // ensure we are at start body
        return $stream;
    }
}

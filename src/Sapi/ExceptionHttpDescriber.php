<?php
namespace Module\HttpFoundation\Sapi;

use Poirot\Application\Sapi\aExceptionDescriber;
use Poirot\View\ViewModel\RendererPhp;


class ExceptionHttpDescriber
    extends aExceptionDescriber
{
    /**
     * Render string or html, json, etc..
     *
     * @return string
     */
    function render(): string
    {
        if (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/json') {
            ob_get_clean();
            \Poirot\Http\Response\httpResponseCode(500);
            header('Content-Type: application/json');

            return json_encode($this->describe());
        }


        if (ob_get_level())
            ## clean output buffer, display just error page
            ob_end_clean();

        try {
            return $this->toHtml();

        } catch(\Exception $ve) {
            ## throw exception if can't render template
            return sprintf(
                'Error While Rendering Exception Into HTML!!! (%s)'
                , $this->getException()->getMessage()
            );
        }
    }

    /**
     * Print Exception Object Error Page
     *
     * @return string
     * @throws \Throwable
     */
    function toHtml()
    {
        $e = $this->getException();

        try {
            $renderer = new RendererPhp;
            return $renderer->capture(
                __DIR__ . '/../.error.page.php'
                , ['exception' => $e]
            );

        } catch(\Exception $ve) {
            ## throw exception if can't render template
            throw $e;
        }
    }
}

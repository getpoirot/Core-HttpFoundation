<?php
namespace Module\HttpFoundation
{
    use Poirot\Http\MimeResolver;


    /**
     * Get Mime Type From File
     *
     * @param string $file
     *
     * @return string
     */
    function getMimeTypeOfFile($file, $fallback = true)
    {
        if ( function_exists('finfo_open') ) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimetype = finfo_file($finfo, $file);
            finfo_close($finfo);
        }  else {
            $mimetype = mime_content_type($file);
        }

        if ($fallback) {
            if ( null !== $solved = getMimeTypeFromFilename($file) )
                ($mimetype === $solved) ?: $mimetype = $solved;

            if (empty($mimetype)) $mimetype = 'application/octet-stream';
        }

        return $mimetype;
    }

    /**
     * Get Mime Type From File Extension
     *
     * @param string $file
     *
     * @return string|null
     */
    function getMimeTypeFromFilename($file)
    {
        return MimeResolver::getMimeType($file);
    }
}

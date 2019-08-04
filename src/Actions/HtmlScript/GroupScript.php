<?php
namespace Module\HttpFoundation\Actions\HtmlScript;

use Module\HttpFoundation\Actions\HtmlScript;


class GroupScript
    extends HtmlScript
{
    /** @var HtmlScript */
    protected $htmlScript;
    /** @var string */
    protected $pathPrefix;
    /** @var int|null */
    protected $offsetIota;


    /**
     * GroupScript
     *
     * @param HtmlScript $htmlScript Wrap html script object
     */
    function __construct(HtmlScript $htmlScript)
    {
        $this->htmlScript = $htmlScript;
    }


    /**
     * Set path prefix to assets
     *
     * @param string $pathPrefix
     *
     * @return self
     */
    function withPathPrefix(string $pathPrefix)
    {
        $new = clone $this;
        $new->pathPrefix = rtrim($pathPrefix, '/');
        return $new;
    }

    /**
     * Set Offset Iota
     *
     * @param int $offset
     *
     * @return self
     */
    function withOffsetIota(int $offset)
    {
        $new = clone $this;
        $new->offsetIota = $offset;
        return $new;
    }

    // Override HtmlScript

    /**
     * @inheritdoc
     */
    function attachFile(string $src, ?int $offset = null, array $attributes = [], $type = 'text/javascript')
    {
        if ($this->pathPrefix && false === strpos($src, 'http'))
            $src = $this->pathPrefix . '/' . trim($src, '/');

        if (null === $offset && null !== $this->offsetIota)
            $offset = $this->offsetIota++;

        $this->htmlScript->attachFile($src, $offset, $attributes, $type);
        return $this;
    }

    /**
     * @inheritdoc
     */
    function attachScript(string $script, ?int $offset = null, array $attributes = [], $type = 'text/javascript')
    {
        if (null === $offset && null !== $this->offsetIota)
            $offset = $this->offsetIota++;

        $this->htmlScript->attachScript($script, $offset, $attributes, $type);
        return $this;
    }

    /**
     * @inheritdoc
     */
    function capture()
    {
        $this->htmlScript->capture();
    }

    /**
     * @inheritdoc
     */
    function captureDone($offset = null, array $attributes = [], $type = 'text/javascript')
    {
        if (null === $offset && null !== $this->offsetIota)
            $offset = $this->offsetIota++;

        $this->htmlScript->captureDone($offset, $attributes, $type);
        return $this;
    }

    /**
     * Render Attached Scripts
     *
     * @return string
     */
    function __toString()
    {
        return $this->htmlScript->__toString();
    }
}

<?php
namespace Module\HttpFoundation\Actions\HtmlLink;

use Module\HttpFoundation\Actions\HtmlLink;


class GroupLink
    extends HtmlLink
{
    /** @var HtmlLink */
    protected $htmlLink;
    /** @var string */
    protected $pathPrefix;
    /** @var int|null */
    protected $offsetIota;


    /**
     * GroupLink
     *
     * @param HtmlLink $htmlLink Wrap html link object
     */
    function __construct(HtmlLink $htmlLink)
    {
        $this->htmlLink = $htmlLink;
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
    function attachFile(string $href, ?int $offset = null, array $attributes = [], $rel = 'stylesheet')
    {
        if ($this->pathPrefix && false === strpos($href, 'http'))
            $href = $this->pathPrefix . '/' . trim($href, '/');

        if (null === $offset && null !== $this->offsetIota)
            $offset = $this->offsetIota++;

        $this->htmlLink->attachFile($href, $offset, $attributes, $rel);
        return $this;
    }

    /**
     * Render Attached Scripts
     *
     * @return string
     */
    function __toString()
    {
        return $this->htmlLink->__toString();
    }
}

<?php
namespace Module\HttpFoundation\Actions;

use Poirot\Std\Struct\CollectionPriority;
use Poirot\Std\Struct\Queue\ReversePriorityQueue;


class HtmlLink
{
    /** the link is inserted in the head section. */
    protected $links;

    /**
     * Allowed attributes
     * @var string[]
     */
    protected $itemKeys = [
        'charset',
        'href',
        'hreflang',
        'id',
        'media',
        'rel',
        'rev',
        'sizes',
        'type',
        'title',
        'extras',
        'itemprop',
        'crossorigin'
    ];

    /**
     * Flag whether to automatically escape output, must also be
     * enforced in the child class if __toString/toString is overridden
     *
     * @var bool
     */
    protected $autoEscape = true;


    /**
     * HtmlLink constructor.
     */
    function __construct()
    {
        $this->links = new ReversePriorityQueue;
    }

    /**
     * Invoke HtmlLink
     *
     * @return $this
     */
    function __invoke($_ = null)
    {
        return $this;
    }


    /**
     * Attach Script File
     *
     * @param string    $href       Http Url To File
     * @param int|null  $offset     Script Priority Offset
     * @param array|int $attributes Attributes Or Priority Offset
     * @param string    $rel        stylesheet
     *
     * @return $this
     */
    function attachFile($href, $offset = null, array $attributes = array(), $rel = 'stylesheet')
    {
        $attributes = array_merge(['type' => 'text/css'], $attributes);

        $item = [
            'rel'  => $rel,
            'href' => $href,
        ];

        $item = array_merge($attributes, $item);

        $this->_insertLinkStr( $this->_itemToString($item), $offset );
        return $this;
    }

    /**
     * Is the link specified a duplicate?
     *
     * - look in all sections
     *
     * @param string $scrStr
     *
     * @return bool
     */
    function hasAttached($scrStr)
    {
        foreach (clone $this->links as $item) {
            $pattern = '/href=(["\'])(.*?)\1/';
            if (preg_match($pattern, $item, $matches) >= 0)
                if (substr_count($scrStr, $matches[2]) > 0)
                    return true;
        }

        return false;
    }


    // ..

    /**
     * Render Attached Links
     *
     * @return string
     */
    function __toString()
    {
        if (! $this->links )
            return '';


        $array = [];
        foreach (clone $this->links as $element)
            $array[] = $element;


        return implode("\r\n", $array);
    }

    /**
     * Add Script To List
     *
     * @param string  $scrStr
     * @param int     $offset
     */
    protected function _insertLinkStr($scrStr, $offset = null)
    {
        if ($this->hasAttached($scrStr))
            return;


        $this->_insertIntoPos($this->links, $scrStr, $offset);
    }


    /**
     * @param CollectionPriority $queue
     * @param $element
     * @param $offset
     * @throws \Exception
     */
    protected function _insertIntoPos($queue, $element, $offset)
    {
        if ($offset === null)
            // Append element to scripts at the end.
            $offset = count($queue);

        if (! is_int($offset) || $offset < 0)
            throw new \Exception(sprintf('Invalid Offset Given (%s).', \Poirot\Std\flatten($offset)));


        $queue->insert($element, $offset);
    }

    /**
     * Create HTML link element from data item
     *
     * @param  array $item
     *
     * @return string
     */
    protected function _itemToString(array $item)
    {
        $attributes = $item;
        $link       = '<link';
        foreach ($this->itemKeys as $itemKey) {
            if (isset($attributes[$itemKey])) {
                if (is_array($attributes[$itemKey])) {
                    foreach ($attributes[$itemKey] as $key => $value) {
                        $link .= sprintf(' %s="%s"', $key, ($this->autoEscape) ? addslashes($value) : $value);
                    }
                } else {
                    $link .= sprintf(
                        ' %s="%s"',
                        $itemKey,
                        ($this->autoEscape) ? addslashes($attributes[$itemKey]) : $attributes[$itemKey]
                    );
                }
            }
        }

        $link .= ' />';

        if (($link == '<link />') || ($link == '<link>')) {
            return '';
        }
        if (isset($attributes['conditionalStylesheet'])
            && !empty($attributes['conditionalStylesheet'])
            && is_string($attributes['conditionalStylesheet'])
        ) {
            // inner wrap with comment end and start if !IE
            if (str_replace(' ', '', $attributes['conditionalStylesheet']) === '!IE') {
                $link = '<!-->' . $link . '<!--';
            }
            $link = '<!--[if ' . $attributes['conditionalStylesheet'] . ']>' . $link . '<![endif]-->';
        }

        return $link;
    }
}

<?php
namespace Module\HttpFoundation\Actions;


class HtmlLink
{
    /** the link is inserted in the head section. */
    protected $links = array();

    /**
     * Allowed attributes
     * @var string[]
     */
    protected $itemKeys = array(
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
        'itemprop'
    );

    /**
     * Flag whether to automatically escape output, must also be
     * enforced in the child class if __toString/toString is overridden
     *
     * @var bool
     */
    protected $autoEscape = true;


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

        $item = array(
            'rel'  => $rel,
            'href' => $href, );

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
        foreach ($this->links as $item) {
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
        return implode( PHP_EOL, $this->links );
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


        ($offset !== null) ?: $offset = count($this->links);

        $this->_insertIntoPosArray($this->links, $scrStr, $offset);
    }

    // TODO separate as std array method
    protected function _insertIntoPosArray(&$array, $element, $offset)
    {
        if ($offset == 0)
            return array_unshift($array, $element);

        if ( $offset + 1 >= count($array) )
            return array_push($array, $element);


        // [1, 2, x, 4, 5, 6] ---> before [1, 2], after [4, 5, 6]
        $beforeOffsetPart = array_slice($array, 0, $offset);
        $afterOffsetPart  = array_slice($array, $offset);
        # insert element in offset
        $beforeOffsetPart = $beforeOffsetPart + array($offset => $element);
        # glue them back
        $array = array_merge($beforeOffsetPart , $afterOffsetPart);
        arsort($array);
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

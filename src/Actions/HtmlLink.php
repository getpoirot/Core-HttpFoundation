<?php
namespace Module\HttpFoundation\Actions;

use Module\HttpFoundation\Actions\HtmlLink\GroupLink;
use Poirot\Std\Struct\Queue\ReversePriorityQueue;

/*
\Module\HttpFoundation\Actions::htmlLink()
    ->attachFile(
        \Module\Foundation\Actions::path( Module::ASSETS, ['file' => 'css/style.min.css?v='.$ver] )
        , null
        , [
            'crossorigin' => 'anonymous'
        ]
    )
    ->attachFile( \Module\Foundation\Actions::path(Module::ASSETS, ['file' => 'fonts/fontawesome/css/fontawesome-all.min.css']))
;
*/

class HtmlLink
{
    /** @var ReversePriorityQueue */
    protected $linksQueue;
    /** @var bool */
    protected $isFlushed = false;

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
     * HtmlLink as a Callable
     *
     * @return $this
     */
    function __invoke()
    {
        return $this;
    }


    /**
     * Attach Script File
     *
     * @param string $href          Http Url To File
     * @param int|null              $offset Script Priority Offset
     * @param array|int $attributes Attributes html
     * @param string $rel           stylesheet
     *
     * @return $this
     * @throws \Exception
     */
    function attachFile(string $href, ?int $offset = null, array $attributes = [], $rel = 'stylesheet')
    {
        if ($this->isFlushed)
            throw new \RuntimeException(sprintf(
                'HtmlLinks are already flushed while attaching (%s).'
                    , $href
            ));


        $attributes   = array_merge(['type' => 'text/css'], $attributes);
        $linkDataItem = array_merge($attributes, [
            'rel'  => $rel,
            'href' => $href,
        ]);

        if (! $this->_hasAttached($linkDataItem) )
            $this->_insertAttachedFile($linkDataItem, $offset);

        return $this;
    }

    /**
     * Start Grouping Scripts
     *
     * @return GroupLink
     */
    function group()
    {
        return new GroupLink($this);
    }

    /**
     * Render Attached Links
     *
     * @return string
     */
    function __toString()
    {
        if ($this->isFlushed)
            trigger_error('HtmlLinks are currently flushed you could`t send output twice.', E_USER_ERROR);


        $r = '';
        if ( $this->_getLinksQueue()->isEmpty() )
            return $r;


        foreach ($this->_getLinksQueue() as $element)
            $r .= $this->_itemToString($element) . PHP_EOL;

        $this->isFlushed = true;
        return $r;
    }

    // ..

    /**
     * @param $element
     * @param $offset
     * @throws \Exception
     */
    protected function _insertAttachedFile($element, ?int $offset = null)
    {
        if ($offset === null)
            // Append element to scripts at the end.
            $offset = $this->_getLinksQueue()->count();


        $this->_getLinksQueue()->insert($element, $offset);
    }

    /**
     * Is the link specified a duplicate?
     *
     * @param array $linkDataItem
     *
     * @return bool
     */
    protected function _hasAttached(array $linkDataItem)
    {
        foreach (clone $this->_getLinksQueue() as $item) {
            if ( 0 < substr_count($linkDataItem['href'], $item['href']) )
                return true;
        }

        return false;
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
        foreach ($this->itemKeys as $itemKey)
        {
            if (! isset($attributes[$itemKey]) )
                continue;

            if ( is_array($attributes[$itemKey]) ) {
                foreach ($attributes[$itemKey] as $key => $value)
                    $link .= sprintf(' %s="%s"', $key, ($this->autoEscape) ? addslashes($value) : $value);
            } else {
                $link .= sprintf(
                    ' %s="%s"',
                    $itemKey,
                    ($this->autoEscape) ? addslashes($attributes[$itemKey]) : $attributes[$itemKey]
                );
            }
        }

        $link .= ' />';

        if (($link == '<link />') || ($link == '<link>'))
            return '';

        if (isset($attributes['conditionalStylesheet'])
            && !empty($attributes['conditionalStylesheet'])
            && is_string($attributes['conditionalStylesheet'])
        ) {
            // inner wrap with comment end and start if !IE
            if (str_replace(' ', '', $attributes['conditionalStylesheet']) === '!IE')
                $link = '<!-->' . $link . '<!--';

            $link = '<!--[if ' . $attributes['conditionalStylesheet'] . ']>' . $link . '<![endif]-->';
        }

        return $link;
    }

    /**
     * Attached Link Files Queue
     *
     * @return ReversePriorityQueue
     */
    protected function _getLinksQueue()
    {
        if (! $this->linksQueue )
            $this->linksQueue = new ReversePriorityQueue;

        return $this->linksQueue;
    }
}

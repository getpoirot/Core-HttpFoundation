<?php
namespace Module\HttpFoundation\Actions;

use Module\HttpFoundation\Actions\HtmlScript\GroupScript;
use Poirot\Std\Struct\Queue\ReversePriorityQueue;


class HtmlScript
{
    const INLINE_SCRIPT = 'inline';
    const HEAD_SCRIPT   = 'head';


    protected $isFlushed;
    /** @var string Current Script Section */
    protected $_currSection;
    /** @var array Attached Scripts */
    protected $scripts = [
        # 'section' => [],
    ];

    /**
     * Flag whether to automatically escape output, must also be
     * enforced in the child class if __toString/toString is overridden
     * @var bool
     */
    protected $autoEscape = true;

    /**
     * Are arbitrary attributes allowed?
     * @var bool
     */
    protected $_arbitraryAttributes = false;

    /**
     * Optional allowed attributes for script tag
     * @var array
     */
    protected $optionalAttributes = [
        'charset',
        'crossorigin',
        'defer',
        'language',
        'src',
    ];



    /**
     * Invoke HtmlScript
     *
     * @param string $section
     *
     * @return $this
     */
    function __invoke($section = self::INLINE_SCRIPT)
    {
        $this->_currSection = (string) $section;
        return $this;
    }

    /**
     * Attach Script File
     *
     * $attributtes:
     * [
     *   'type' => 'text/javascript',
     *
     * ]
     *
     * @param string    $src        Http Url To File
     * @param int       $offset     Script Priority Offset
     * @param array|int $attributes Attributes
     * @param string    $type       Text/Javascript
     *
     * @return $this
     */
    function attachFile(string $src, ?int $offset = null, array $attributes = [], $type = 'text/javascript')
    {
        if ( isset($this->isFlushed[$this->_currSection]) )
            throw new \RuntimeException(sprintf(
                'HtmlScript are already flushed while attaching (%s).'
                , $src
            ));


        if ( isset($attributes['type']) ) {
            $type = $attributes['type'];
            unset($attributes['type']);
        }

        $scriptDataItem = [
            "type"       => $type,
            "attributes" => array_merge( $attributes, ["src" => (string) $src])
        ];

        if (! $this->_hasAttached($scriptDataItem) )
            $this->_insertAttachedFile($scriptDataItem, $offset);

        return $this;
    }

    /**
     * Attach Script Content
     *
     * @param string    $script     Script Content
     * @param int|null  $offset     Script Priority Offset
     * @param array|int $attributes Attributes Or Priority Offset
     * @param string    $type       Text/Javascript
     *
     * @return $this
     */
    function attachScript(string $script, ?int $offset = null, array $attributes = [], $type = 'text/javascript')
    {
        if ( isset($this->isFlushed[$this->_currSection]) )
            throw new \RuntimeException(sprintf(
                'HtmlScript are already flushed while attaching (%s).'
                , $script
            ));


        if ( isset($attributes['type']) ) {
            $type = $attributes['type'];
            unset($attributes['type']);
        }

        $scriptDataItem = [
            'source'     => $script,
            'type'       => $type,
            'attributes' => $attributes
        ];

        if (! $this->_hasAttached($scriptDataItem) )
            $this->_insertAttachedFile($scriptDataItem, $offset);

        return $this;
    }

    /**
     * Start Capture text script
     *
     */
    function capture()
    {
        ob_start();
    }

    /**
     * Attach Captured Script Content
     *
     * @param array|int $attributes Attributes Or Priority Offset
     * @param int|null  $offset   Script Priority Offset
     * @param string    $type       Text/Javascript
     *
     * @return $this
     */
    function captureDone($offset = null, array $attributes = [], $type = 'text/javascript')
    {
        $script = ob_get_clean();
        $this->attachScript($script, $offset, $attributes, $type);
        return $this;
    }

    /**
     * Start Grouping Scripts
     *
     * @return GroupScript
     */
    function group()
    {
        return new GroupScript($this);
    }

    /**
     * Render Attached Scripts
     *
     * @return string
     */
    function __toString()
    {
        if ( isset($this->isFlushed[$this->_currSection]) )
            trigger_error('HtmlScripts are currently flushed you could`t send output twice.', E_USER_ERROR);

        $r = '';
        if (!isset($this->scripts[$this->_currSection]) || empty($this->scripts[$this->_currSection]))
            return $r;

        foreach (clone $this->scripts[$this->_currSection] as $element)
            $r .= $this->_itemToString($element) . PHP_EOL;

        return $r;
    }

    // ..

    /**
     * Is the script specified a duplicate?
     *
     * - look in all sections
     *
     * @param array $scriptDataItem
     *
     * @return bool
     */
    protected function _hasAttached(array $scriptDataItem)
    {
        $duplicate = false;
        foreach($this->scripts as $section) {
            foreach (clone $section as $item) {
                if ( isset($scriptDataItem['source']) && isset($item['source']))
                    $duplicate |= $item['source'] == $scriptDataItem['source'];
                elseif (isset($scriptDataItem['attributes']['src']))
                    $duplicate |= @$item['attributes']['src'] == $scriptDataItem['attributes']['src'];

                if ($duplicate)
                    break;
            }
        }

        return $duplicate;
    }

    /**
     * Add Script To List
     *
     * @param array  $scriptDataItem
     * @param int    $offset
     */
    protected function _insertAttachedFile(array $scriptDataItem, $offset = null)
    {
        $currSection = $this->_currSection;

        if (! isset($this->scripts[$currSection]) )
            $this->scripts[$currSection] = new ReversePriorityQueue;


        $queue = $this->scripts[$currSection];
        if ($offset === null)
            // Append element to scripts at the end.
            $offset = count($queue);

        $queue->insert($scriptDataItem, $offset);
    }

    /**
     * Convert Script Array Representation To String
     *
     * @param array        $item Script Array Representation
     * @param $indent
     * @param $escapeStart
     * @param $escapeEnd
     *
     * @return string
     */
    protected function _itemToString(array $item, $indent = '', $escapeStart = '', $escapeEnd = '')
    {
        $item = (object) $item;

        $attrString = '';
        if (! empty($item->attributes) ) {
            foreach ($item->attributes as $key => $value) {
                if ((!$this->_isArbitraryAttributesAllowed() && !in_array($key, $this->optionalAttributes))
                    || in_array($key, array('conditional', 'noescape'))
                )
                    continue;

                if ('defer' == $key)
                    $value = 'defer';

                $attrString .= sprintf(' %s="%s"', $key, ($this->autoEscape) ? addslashes($value) : $value);
            }
        }

        $addScriptEscape = !(isset($item->attributes['noescape'])
            && filter_var($item->attributes['noescape'], FILTER_VALIDATE_BOOLEAN));

        $type = ($this->autoEscape) ? addslashes($item->type) : $item->type;
        $html = '<script type="' . $type . '"' . $attrString . '>';

        if (! empty($item->source) ) {
            $html .= PHP_EOL;

            if ($addScriptEscape)
                $html .= $indent . '    ' . $escapeStart . PHP_EOL;

            $html .= $indent . '    ' . $item->source;

            if ($addScriptEscape)
                $html .= PHP_EOL . $indent . '    ' . $escapeEnd;

            $html .= PHP_EOL . $indent;
        }

        $html .= '</script>';

        if (isset($item->attributes['conditional'])
            && !empty($item->attributes['conditional'])
            && is_string($item->attributes['conditional'])
        ) {
            // inner wrap with comment end and start if !IE
            if (str_replace(' ', '', $item->attributes['conditional']) === '!IE')
                $html = '<!-->' . $html . '<!--';

            $html = $indent . '<!--[if ' . $item->attributes['conditional'] . ']>' . $html . '<![endif]-->';
        } else
            $html = $indent . $html;

        return $html;
    }

    /**
     * !! Override code
     *
     * Are arbitrary attributes allowed?
     *
     * @return bool
     */
    protected function _isArbitraryAttributesAllowed()
    {
        return $this->_arbitraryAttributes;
    }
}

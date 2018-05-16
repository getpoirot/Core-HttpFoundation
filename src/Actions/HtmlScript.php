<?php
namespace Module\HttpFoundation\Actions;

use Poirot\Std\Struct\CollectionPriority;
use Poirot\Std\Struct\Queue\ReversePriorityQueue;


class HtmlScript
{
    const INLINE_SCRIPT = 'inline';
    const HEAD_SCRIPT   = 'head';


    /** @var string Current Script Section */
    protected $_currSection;
    /** @var array Attached Scripts */
    protected $scripts = [
        // 'section' => [],
    ];

    /**
     * Flag whether to automatically escape output, must also be
     * enforced in the child class if __toString/toString is overridden
     *
     * @var bool
     */
    protected $autoEscape = true;

    /**
     * Are arbitrary attributes allowed?
     *
     * @var bool
     */
    protected $_arbitraryAttributes = false;

    /**
     * Optional allowed attributes for script tag
     *
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
     * @param string    $src         Http Url To File
     * @param array|int $attributes  Attributes
     * @param int|null  $priority    Script Priority Offset
     * @param string    $type        Text/Javascript
     *
     * @return $this
     */
    function attachFile($src, $priority = null, array $attributes = [], $type = 'text/javascript')
    {
        if ( isset($attributes['type']) ) {
            $type = $attributes['type'];
            unset($attributes['type']);
        }

        $item = [
            "type"       => $type,
            "attributes" => array_merge( $attributes, ["src" => (string) $src])
        ];

        $this->_insertScriptStr($this->_itemToString($item), $priority);
        return $this;
    }

    /**
     * Attach Script Content
     *
     * @param string    $script     Script Content
     * @param array|int $attributes Attributes Or Priority Offset
     * @param int|null  $priority   Script Priority Offset
     * @param string    $type       Text/Javascript
     *
     * @return $this
     */
    function attachScript($script, $priority = null, array $attributes = [], $type = 'text/javascript')
    {
        if ( isset($attributes['type']) ) {
            $type = $attributes['type'];
            unset($attributes['type']);
        }

        $item = [
            "source"     => (string) $script,
            "type"       => $type,
            "attributes" => $attributes
        ];

        $this->_insertScriptStr($this->_itemToString($item), $priority);
        return $this;
    }

    function capture()
    {
        ob_start();
    }

    /**
     * Attach Captured Script Content
     *
     * @param array|int $attributes Attributes Or Priority Offset
     * @param int|null  $priority   Script Priority Offset
     * @param string    $type       Text/Javascript
     *
     * @return $this
     */
    function captureDone($priority = null, array $attributes = [], $type = 'text/javascript')
    {
        $script = ob_get_clean();
        $this->attachScript($script, $priority, $attributes, $type);
        return $this;
    }

    /**
     * Render Attached Scripts
     *
     * @return string
     */
    function __toString()
    {
        $scripts = (isset($this->scripts[$this->_currSection]))
            ? $this->scripts[$this->_currSection]
            : array();

        $array = [];
        if ( !empty($scripts) ) {
            foreach (clone $scripts as $element)
                $array[] = $element;
        }

        return implode("\r\n", $array);
    }

    /**
     * Is the script specified a duplicate?
     *
     * - look in all sections
     *
     * @param string $scrStr
     *
     * @return bool
     */
    function hasAttached($scrStr)
    {
        $duplicate = false;
        foreach($this->scripts as $section) {
            foreach (clone $section as $item) {
                $duplicate |= $item === $scrStr;

                if ($duplicate)
                    break;
            }
        }

        return $duplicate;
    }


    // ..

    /**
     * Add Script To List
     *
     * @param string  $scrStr
     * @param int     $offset
     */
    protected function _insertScriptStr($scrStr, $offset = null)
    {
        if ( $this->hasAttached($scrStr) )
            return;


        $currSection = $this->_currSection;

        if (! array_key_exists($currSection, $this->scripts) )
            $this->scripts[$currSection] = new ReversePriorityQueue;

        $this->_insertIntoPos($this->scripts[$currSection], $scrStr, $offset);
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

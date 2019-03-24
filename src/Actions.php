<?php
namespace Module\HttpFoundation
{
    use Module\HttpFoundation\Actions\FlashMessage;
    use Module\HttpFoundation\Actions\HtmlHeadTitle;
    use Module\HttpFoundation\Actions\HtmlLink;
    use Module\HttpFoundation\Actions\HtmlScript;
    use Module\HttpFoundation\Actions\Url;

    /**
     *
     * @method static Url           url($routeName = null, $params = array(), $instruct = Url::DEFAULT_INSTRUCT, array $instructOptions = array())
     * @method static FlashMessage  flashMessage($messageNamespace = 'info')
     * @method static HtmlScript    htmlScript($section = 'inline')
     * @method static HtmlLink      htmlLink()
     * @method static HtmlHeadTitle htmlHeadTitle($title = null)
     */
    class Actions extends \IOC
    { }
}
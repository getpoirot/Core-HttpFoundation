<?php
namespace Module\HttpFoundation;

use Module\HttpFoundation\Actions\FlashMessageAction;
use Module\HttpFoundation\Actions\HtmlHeadTitle;
use Module\HttpFoundation\Actions\HtmlLinkAction;
use Module\HttpFoundation\Actions\HtmlScriptAction;
use Module\HttpFoundation\Actions\UrlService;
use Poirot\Ioc\Container\BuildContainer;


class BuildContainerActionOfModule
    extends BuildContainer
{
    protected $services = [
        'url'           => UrlService::class,
        'flashMessage'  => FlashMessageAction::class,
        # Html Tag Helpers
        'htmlHeadTitle' => HtmlHeadTitle::class,
        'htmlScript'    => HtmlScriptAction::class,
        'htmlLink'      => HtmlLinkAction::class,
    ];
}

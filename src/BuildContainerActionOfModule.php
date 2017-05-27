<?php
namespace Module\HttpFoundation;

use Module\HttpFoundation\Actions\FileServeAction;
use Module\HttpFoundation\Actions\FlashMessage;
use Module\HttpFoundation\Actions\HtmlHeadTitle;
use Module\HttpFoundation\Actions\HtmlLink;
use Module\HttpFoundation\Actions\HtmlScript;
use Module\HttpFoundation\Actions\UrlService;
use Poirot\Ioc\Container\BuildContainer;


class BuildContainerActionOfModule
    extends BuildContainer
{
    protected $services = [
        'url'             => UrlService::class,
        'flashMessage'    => FlashMessage::class,
        # Html Tag Helpers
        'htmlHeadTitle'   => HtmlHeadTitle::class,
        'htmlScript'      => HtmlScript::class,
        'htmlLink'        => HtmlLink::class,

        'fileServeAction' => FileServeAction::class,
    ];
}

<?php
//region Globals
global $o_actions;
//endregion
//region Includes and Requires
require_once("wrk/functions/wrk.package.php");
include_once(__DIR__.'/wrk/enchancers/listelementwithdescription.php');
include_once(__DIR__.'/wrk/enchancers/usertypeiblock.php');
//endregion
//region Event handlers
AddEventHandler('iblock', 'OnIBlockPropertyBuildList', ['listElementWithDescription', 'GetIBlockPropertyDescription']);
AddEventHandler('main', 'OnUserTypeBuildList', ['UserTypeGroupElement', 'GetUserTypeDescription']);
AddEventHandler('main', 'OnUserTypeBuildList', ['UserTypeIBlock', 'GetUserTypeDescription']);
AddEventHandler("main", "OnBeforeProlog", "app_init");
//endregion
//region Interfaces
interface Placeholder {
    const BANNER = SITE_TEMPLATE_PATH.'/assets/img/placeholders/pl_banner.png';
    const CARD = SITE_TEMPLATE_PATH.'/assets/img/placeholders/pl_card.png';
    const ICON = SITE_TEMPLATE_PATH.'/assets/img/placeholders/pl_icon.png';
    const IMAGE = SITE_TEMPLATE_PATH.'/assets/img/placeholders/pl_image.png';
    const JUMBO = SITE_TEMPLATE_PATH.'/assets/img/placeholders/pl_jumbo.png';
    const LOGO = SITE_TEMPLATE_PATH.'/assets/img/placeholders/pl_logo.png';
    const SLIDE = SITE_TEMPLATE_PATH.'/assets/img/placeholders/pl_slide.png';
    const THUMB = SITE_TEMPLATE_PATH.'/assets/img/placeholders/pl_thumb.png';
}

interface Resizables {
    const ITEM = [575];
    const ITEM_THUMB = [470];
    const SLIDER_ADAPTIVE = [575, 490, BX_RESIZE_IMAGE_EXACT];
    const SLIDER_SMALLER = [992, 600];
    const SLIDER_DESKTOP = [1200, 750];
}
//endregion
//region Constants
const LINKS = [];
const SLIDER_RESIZE_PRESET = [
    'ADAPTIVE' => Resizables::SLIDER_ADAPTIVE,
    'SMALLER' => Resizables::SLIDER_SMALLER,
];
//endregion
//region Functions
/**
 * Return the provided class if the path is active
 *
 * @param string $url - url to check
 * @param string $cssClass - class to return
 * @return string
 */
function checkActiveSection($url, $cssClass = 'selected') {
    global $APPLICATION;
    return strstr($APPLICATION->GetCurDir(), $url) ? $cssClass : '';
}

/**
 * Forces 404 custom page rendering
 *
 * @param string $s_path_to_404 - path to 404 custom page
 */
function set404($s_path_to_404 = '/404.php') {
    global $APPLICATION;
    $APPLICATION->RestartBuffer();
    require $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/header.php';
    require $_SERVER['DOCUMENT_ROOT'].$s_path_to_404;
    require $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/footer.php';
    die();
}
//endregion
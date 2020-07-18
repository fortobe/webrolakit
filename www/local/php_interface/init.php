<?php
const LINKS = [];
global $o_actions;
require_once("wrk/functions/wrk.package.php");
include_once(__DIR__.'/wrk/enchancers/listelementwithdescription.php');
include_once(__DIR__.'/wrk/enchancers/usertypeiblock.php');
AddEventHandler('iblock', 'OnIBlockPropertyBuildList', ['listElementWithDescription', 'GetIBlockPropertyDescription']);
AddEventHandler('main', 'OnUserTypeBuildList', ['UserTypeIBlock', 'GetUserTypeDescription']);
AddEventHandler("main", "OnBeforeProlog", "app_init");
const RESIZE_PRESETS = [
    'items' => [
        'THUMB' => [
            'width' => 470,
            'height' => 470,
        ],
    ],
    'slider' => [
        'ADAPTIVE' => [
            'width' => 992,
            'height' => 600,
        ],
        'SMALLER' => [
            'width' => 575,
            'height' => 490,
        ],
    ],
];

function set404($s_path_to_404 = '/404.php') {
    global $APPLICATION;
    $APPLICATION->RestartBuffer();
    require $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/header.php';
    require $_SERVER['DOCUMENT_ROOT'].$s_path_to_404;
    require $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/footer.php';
    die();
}
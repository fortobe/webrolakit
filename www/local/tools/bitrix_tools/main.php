<?php
if ($USER->IsAdmin()) {
    /**
     * variables section
     */
    $action = $_GET['action'];
    $iblock_id = $_GET['iblock_id'];
    $filter = $_GET['filter'];
    $field = $_GET['field'];
    $value_source = $_GET['value_source'];
    $value = $_GET['value'];
    $push = $_GET['push'];
    $source_field = $_GET['source_field'] ?: '';
    $arResult = [
        'ACTION' => $_GET['action'].':',
        'PROCESSED' => 0,
        'SUCCESSED' => 0,
        'FAILED' => 0,
        'ERRORS' => [],
        'MESSAGE' => 'no message attached',
    ];
    $allow = (isset($_GET['value']) || isset($_GET['value_source']));
    if (!$iblock_id) $arResult['ERRORS'][] = 'provide iblock_id param!';
    if (!$allow) $arResult['ERRORS'][] = 'provide value or value source param!';
    if (!$field) $arResult['ERRORS'][] = 'provide the field!';
    if (!empty($_GET['force_uc']) && is_array($_GET['force_uc'])) {
        foreach ($_GET['force_uc'] as $item) {
            $$item = strtoupper($$item);
        }
    }
    if (!empty($_GET['force_lc']) && is_array($_GET['force_lc'])) {
        foreach ($_GET['force_lc'] as $item) {
            $$item = strtolower($$item);
        }
    }
    $arFilter = [
        'IBLOCK_ID' => $iblock_id
    ];
    if (!empty($filter)) $arFilter = array_merge($arFilter, $filter);
    /**
     * actions section
     */
    if ($_GET['debug']) ea($_GET);
    switch($action) {
        case 'sections_sort_map':
            $oList = CIBlockSection::GetMixedList(['DATE_CREATE' => 'ASC'], $arFilter);
            $oSection = new CIBlockSection();
            $oElement = new CIBlockElement();
            while($arItem = $oList->GetNext()) {
                $arResult['PROCESSED']++;
                if (!$push && $arItem['DEPTH_LEVEL'] == 1) continue;
                $arFields = ['SORT' => strtotime($arItem['DATE_CREATE'])];
                if ($arItem['SORT'] == $arFields['SORT']) continue;
                $res = $arItem['TYPE'] == 'S' ? $oSection->Update($arItem['ID'], $arFields) : $oElement->Update($arItem['ID'], $arFields);
                if ($res) {
                    $arResult['SUCCESSED']++;
                } else {
                    $arResult['FAILED']++;
                    $arResult['ERRORS'][] = 'ID - '.$arItem['ID'].': '.($arItem['TYPE'] == 'S' ? $oSection->LAST_ERROR : $oElement->LAST_ERROR);
                }
            }
            break;
        case 'sections_set_field':
            /**
             * @param $iblock_id (int)
             * @param $value|$value_source (string)
             * @param [$filter] (assoc array)
             * @param [$select] (assoc array)
             */
            if (!empty($arResult['ERRORS'])) break;
            if ($value_source) {
                $arSource = [];
                $link_field = '';
                switch ($value_source){
                    case 'users':
                        $oUsers = CUser::GetList($by = 'id', $order ='asc',[] ,['SELECT' => ['UF_*']]);
                        while ($arUser = $oUsers->GetNext()) {
                            $arSource[$arUser['ID']] = $arUser;
                        }
                        $link_field = 'CREATED_BY';
                        break;
                    case 'user':
                        $arSource = $USER->Fields;
                        $link_field = '';
                        break;
                }
                if ($_GET['view_source']) ea($arSource);
            }
            $oSection = new CIBlockSection();
            $oSections = CIBlockSection::GetList([], $arFilter, false, $select?:['UF_*']);
            while($arSection = $oSections->GetNext()) {
                if ($_GET['show_data']) {
                    ea($arSection, false);
                    continue;
                }
                if (!array_key_exists($field, $arSection)) {
                    $arResult['ERRORS'][] = 'no such field';
                    break;
                }
                if (empty($arSection[$field]) || $push) {
                    $value = $value_source ? $link_field ? $arSource[$arSection[$link_field]][$field] : $arSource[$field] : $value;
                    include '_debug_vars.php';
                    $res = $oSection->Update($arSection['ID'], [$field => $value]);
                    if ($res) {
                        $arResult['SUCCESSED']++;
                    } else {
                        $arResult['FAILED']++;
                        $arErrors[] = '#'.$arSection['ID'].': '.$oSection->LAST_ERROR;
                    }
                    $arResult['PROCESSED']++;
                }
            }
            break;
        case 'elements_set_property':
            /**
             * @param $iblock_id (int)
             * @param $value|$value_source (string)
             * @param [$source_field] (string)
             * @param [$filter] (assoc array)
             * @param [$select] (assoc array)
             */
            if (!empty($arResult['ERRORS'])) break;
            if ($value_source) {
                $arSource = [];
                $link_field = '';
                switch ($value_source){
                    case 'users':
                        $oUsers = CUser::GetList($by = 'id', $order ='asc',[] ,['SELECT' => ['UF_*']]);
                        while ($arUser = $oUsers->GetNext()) {
                            $arSource[$arUser['ID']] = $arUser;
                        }
                        $link_field = 'CREATED_BY';
                        break;
                    case 'user':
                        $arSource = $USER->Fields;
                        $link_field = '';
                        break;
                }
                if ($_GET['view_source']) ea($arSource);
            }
            $oElement = new CIBlockElement();
            $oElements = CIBlockElement::GetList([], $arFilter, false, false,$select?:['ID', 'IBLOCK_ID', 'CODE', 'CREATED_BY', 'PROPERTY_'.$field]);
            while($arElement = $oElements->GetNext()) {
                if ($_GET['show_data']) {
                    ea($arElement, false);
                    continue;
                }
                if (!array_key_exists('PROPERTY_'.strtoupper($field).'_VALUE', $arElement)) {
                    $arResult['ERRORS'][] = 'no such field: '.$field;
                    break;
                }
                if (empty($arElement['PROPERTY_'.strtoupper($field).'_VALUE']) || $push) {
                    $value = $value_source ?
                        $link_field ? $arSource[$arElement[$link_field]][$source_field?:$field] : $arSource[$source_field?:$field] : $value;
                    include '_debug_vars.php';
                    $res = $oElement->SetPropertyValueCode($arElement['ID'], $field, $value);
                    if ($res) {
                        $arResult['SUCCESSED']++;
                    } else {
                        $arResult['FAILED']++;
                        $arErrors[] = '#'.$arElement['ID'].': '.$oElement->LAST_ERROR;
                    }
                    $arResult['PROCESSED']++;
                }
            }
            break;
        default:
            $arResult['ACTION'] = 'no proper action provided';
    }
    ea($arResult, false);
} else LocalRedirect('/lk/');
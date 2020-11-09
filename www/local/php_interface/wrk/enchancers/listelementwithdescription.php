<?php

class listElementWithDescription
{
    function GetIBlockPropertyDescription()
    {
        return array(
            "PROPERTY_TYPE" => "E",
            "USER_TYPE" => "listElementWithDescription",
            "DESCRIPTION" => "Привязка к элементам с доп.описанием",
            'GetPropertyFieldHtml' => array(__CLASS__, 'GetPropertyFieldHtml'),
            "ConvertToDB" => array(__CLASS__,"ConvertToDB"),
            "ConvertFromDB" => array(__CLASS__,"ConvertFromDB"),
        );
    }

    function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $arItem = Array(
            "ID" => 0,
            "IBLOCK_ID" => 0,
            "NAME" => ""
        );
        if(intval($value["VALUE"]) > 0)
        {
            $arFilter = Array(
                "ID" => intval($value["VALUE"]),
                "IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"],
            );

            $arItem = \CIBlockElement::GetList(Array(), $arFilter, false, false, Array("ID", "IBLOCK_ID", "NAME"))->Fetch();
        }
        $html = '<input name="'.$strHTMLControlName["VALUE"].'" id="'.$strHTMLControlName["VALUE"].'" value="'.htmlspecialcharsex($value["VALUE"]).'" size="3" type="text">'.
            ' <input type="button" value="Выбрать" onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang='.LANG.'&IBLOCK_ID='.$arProperty["LINK_IBLOCK_ID"].'&n='.$strHTMLControlName["VALUE"].'\', 600, 500);">'.
            '&nbsp; Описание: <input type="text" name="'.$strHTMLControlName["DESCRIPTION"].'" value="'.htmlspecialcharsex($value["DESCRIPTION"]).'" />'.
            '<br><span id="sp_'.md5($strHTMLControlName["VALUE"]).'" >'.$arItem["NAME"].'</span>';
        return  $html;
    }

    function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        return;
    }

    function ConvertToDB($arProperty, $value)
    {
        $return = false;

        if( is_array($value) && array_key_exists("VALUE", $value) )
        {
            $return = array(
                "VALUE" => $value["VALUE"]
            );
        }

        if( is_array($value) && array_key_exists("DESCRIPTION", $value) )
            $return["DESCRIPTION"] = $value["DESCRIPTION"];

        return $return;
    }

    function ConvertFromDB($arProperty, $value)
    {
        $return = false;

        if(!is_array($value["VALUE"]))
        {
            $return = array(
                "VALUE" => $value["VALUE"]
            );
        }

        return $return;
    }
}
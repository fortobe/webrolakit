<?php
use \Bitrix\Main,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Main\UserField,
    \wrk\classes\main as WRK;

class UserTypeIBlock extends CUserTypeEnum
{

    public function GetUserTypeDescription ()
    {
        return array(
            'USER_TYPE_ID' => 'iblock',
            'CLASS_NAME' => __CLASS__,
            'DESCRIPTION' => 'Привязка к инфоблокам',
            'BASE_TYPE' => \CUserTypeManager::BASE_TYPE_INT,
        );
    }

    public function GetList($arUserField)
    {
        return \CIBlock::GetList([], [
            'ACTIVE' => 'Y',
        ]);
        }

    public function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        $oList = call_user_func_array(
            array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
            array(
                $arUserField,
            )
        );
        if(!$oList)
            return '';

        if($arUserField["SETTINGS"]["LIST_HEIGHT"] > 1)
            $size = ' size="'.$arUserField["SETTINGS"]["LIST_HEIGHT"].'"';
        else
            $size = '';

        $result = '<select name="'.$arHtmlControl["NAME"].'"'.$size.($arUserField["EDIT_IN_LIST"]!="Y"? ' disabled="disabled" ': '').'>';
        if($arUserField["MANDATORY"]!="Y")
        {
            $result .= '<option value=""'.(!$arHtmlControl["VALUE"]? ' selected': '').'>'.htmlspecialcharsbx(self::getEmptyCaption($arUserField)).'</option>';
        }
        while($arIblock = $oList->GetNext())
        {
            $result .= '<option value="'.$arIblock["ID"].'"'.($arHtmlControl["VALUE"]==$arIblock["ID"]? ' selected': '').'>'.$arIblock["NAME"].'</option>';
        }
        $result .= '</select>';
        return $result;
    }

    public function GetAdminListViewHTML($arUserField, $arHtmlControl)
    {
        static $cache = [];
        $empty_caption = '&nbsp;';
        if(!array_key_exists($arHtmlControl["VALUE"], $cache))
        {
            $rsEnum = call_user_func_array(
                array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
                array(
                    $arUserField,
                )
            );
            if(!$rsEnum)
                return $empty_caption;
            while($arEnum = $rsEnum->GetNext())
                $cache[$arEnum["ID"]] = $arEnum["NAME"];
        }
        if(!array_key_exists($arHtmlControl["VALUE"], $cache))
            $cache[$arHtmlControl["VALUE"]] = $empty_caption;
        return $cache[$arHtmlControl["VALUE"]];
    }
}
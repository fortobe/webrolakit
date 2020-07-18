<?

namespace wrk\classes;

use Bitrix\Main\DB\MysqliConnection;

\CModule::IncludeModule("search");
\CModule::IncludeModule("iblock");
\CModule::IncludeModule("highloadblock");

use Bitrix\Highloadblock as HL;

class main
{

    const PHONE_EXP = "/^(\+?[0-9]{1,3})?(\s?\(?[0-9]{2,4}\)?\s?)?([0-9]{2,4}-?\s?){1,3}$/";
    const EMAIL_EXP = "/^[a-zA-Z0-9-\._]+@([-a-z0-9]+\.)+[a-z]{2,4}$/";
    const STATUS_404 = 404;

    private static $response = array("status" => "error", "msg" => array());

    public static function auth_user()
    {
        global $USER;
        $a_response = self::$response;
        $m_result = null;           //TODO Implement this!!!
        if ($m_result === true) {
            $a_response["status"] = "success";
            $a_response["msg"] = "Вы успешно вошли на сайт!";
        } else {
            $a_response["msg"] = explode("<br>", $m_result["MESSAGE"]);
        }
        return $a_response;
    }

    public static function reg_user()
    {
        global $USER;
        $a_response = self::$response;
        //if (!preg_match(self::PHONE_EXP, $_REQUEST["phone"])) - phone validation
        if (preg_match(self::EMAIL_EXP, $_REQUEST["email"])) {
            if (strlen($_REQUEST["name"]) > 2 && substr(trim($_REQUEST["name"]), 0, 1) != " ") {
                if (strlen($_REQUEST["surname"]) > 2 && substr(trim($_REQUEST["surname"]), 0, 1) != " ") {
                    if (isset($_REQUEST['agreement'])) {
                        $m_result = $USER->Register($_REQUEST['email'], $_REQUEST['name'], $_REQUEST['surname'], $_REQUEST['password'], $_REQUEST["conf_pass"], $_REQUEST["email"]);
                        if ($m_result["TYPE"] == "OK") {
                            $a_mail_fields = array(
                                "MAILTO" => $_REQUEST["email"],
                                "LOGIN" => $_REQUEST["email"],
                                "PASSWORD" => $_REQUEST["password"],
                                "LINK" => $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=6&type=content&ID=" . $i_id . "&lang=ru&find_section_section=-1"
                            );
                            //\CEvent::Send("USER_REG", array("s1","ru", "en"), $a_mail_fields);
                            $a_response["status"] = "success";
                            $a_response["msg"] = "Вы успешно зарегистрировались";
                        } else {
                            $a_response["msg"] = explode("<br>", $m_result["MESSAGE"]);
                        }
                    } else {
                        $a_response["msg"][] = "Необходимо согласие на обработку персональных данных";
                    }
                } else {
                    $a_response["msg"][] = "Заполните поле \"Фамилия\"";
                }
            } else {
                $a_response["msg"][] = "Заполните поле \"Имя\"";
            }
        } else {
            $a_response["msg"][] = "Проверьте правильность адреса электронной почты";
        }
        return $a_response;
    }

    public static function rec_user()
    {
        $a_response = self::$response;
        $a_response['msg'] = array();
        $a_user = \CUser::GetByLogin($_REQUEST['email'])->Fetch();
        if ($a_user["ID"]) {
            $new_pass = strtoupper(substr(md5(time() . $a_user["LOGIN"]), 0, 8));
            global $USER;
            $b_change = $USER->Update($a_user["ID"], array(
                "PASSWORD" => $new_pass,
                "CONFIRM_PASSWORD" => $new_pass
            ));
            if ($b_change) {
                $a_mail_fields = array(
                    "MAILTO" => $a_user["EMAIL"],
                    "PASSWORD" => $new_pass,
                );/*
                if(\CEvent::Send("USER_REC", array("s1","ru", "en"), $a_mail_fields)){
                    $a_response['status'] = 'success';
                    $a_response["msg"] = "Успешно изменён, проверьте свой почтовый ящик";
                } else {
                    $a_response['msg'][] = 'Произошла внутренняя ошибка. Обратитесь к администратору';
                }*/
            } else {
                $a_response['msg'][] = $USER->LAST_ERROR;
            }
        } else {
            $a_response['msg'][] = "Пользователь не зарегистрирован";
        }
        return $a_response;
    }

    public static function pass_moderation($arItem)
    {
        $a_mail_fields = array(
            "MAILTO" => $arItem["USER"]["EMAIL"],
            "NAME" => $arItem["NAME"],
            "LINK" => "http://" . $_SERVER["HTTP_HOST"] . $arItem["DETAIL_PAGE_URL"]
        );
        return \CEvent::Send("MODERATION_PASSED", array("s1", "ru", "en"), $a_mail_fields);
    }

    public static function get_ratings()
    {
        $a_photos = self::get_iblock_elems(array(
            "iblock" => "photocont-gallery"
        ));

        $a_raitings = array();
        foreach ($a_photos as $a_photo) {
            $i_likes = (!empty($a_photo["PROPERTIES"]["VOTES"]["VALUE"])) ? count($a_photo["PROPERTIES"]["VOTES"]["VALUE"]) : 0;
            $a_raitings[$i_likes] = 1;
        }

        krsort($a_raitings);
        $i = 1;
        foreach ($a_raitings as &$a_raiting) {
            $a_raiting = $i;
            $i++;
        }

        return $a_raitings;
    }

    //returns: (Array) array of users of one partifular user if $b_unwrap is true.
    //takes: $s_group - name of the user's group, $b_index_id - defines whether set users' ids as array's keys or not,
    //$b_unwrap - return result as array of single user (cannot be used with $b_index_id = true and
    // if more than one user returns only the first one
    public static function get_user_list($s_group = false, $b_index_id = false, $b_unwrap = false)
    {
        $a_users = array();
        if ($s_group) {
            $a_filter = array();
            switch ($s_group) {
                case "admins":
                    $a_filter["GROUPS_ID"] = 1;
                    break;
                default:
                    $a_filter = false;
            }
        }
        $a_sort = array("id" => "asc");
        if ($a_filter) $rs_users = \CUser::GetList($a_sort, $a_filter);
        else $rs_users = \CUser::GetList($a_sort);
        while ($a_user = $rs_users->GetNext()) {
            if ($b_index_id) $a_users[$a_user["ID"]] = $a_user;
            else $a_users[] = $a_user;
        }
        if (count($a_users) == 0) return false;
        elseif (count($a_users) == 1 && $b_unwrap && !$b_index_id) return $a_users[0];
        else return $a_users;
    }

    //returns: (int|bool) group id of user or false if not found.
    //takes: $o_user - object of user, $s_role - particular name of the user group;
    public static function get_user_group($o_user, $s_role = false)
    {
        $b_return = false;
        if ($s_role) {
            switch ($s_role) {
                default:
                    $i_group = false;
            }
            if ($i_group) {
                $d_groups = $o_user->GetParam("GROUPS");
                if (is_array($d_groups)) {
                    foreach ($d_groups as $d_group) {
                        if ($d_group == $i_group) $b_return = true;
                    }
                } elseif (is_bool($d_groups)) $b_return = false;
                else {
                    if ($d_groups == $i_group) $b_return = true;
                    else $b_return = false;
                }
            }
        } else $b_return = $o_user->GetParam("GROUPS");
        return $b_return;
    }

    //returns: (bool) validates user's password with input and returns the result
    //takes: $userID - id of user, $password - user's password
    public static function isUserPassword($userId, $password)
    {
        $userData = CUser::GetByID($userId)->Fetch();
        $salt = substr($userData['PASSWORD'], 0, (strlen($userData['PASSWORD']) - 32));
        $realPassword = substr($userData['PASSWORD'], -32);
        $password = md5($salt . $password);
        return ($password == $realPassword);
    }

    public static function get_base_price($id, $fallback = '') {
        return \CPrice::GetBasePrice($id)?:$fallback;
    }

    public static function get_file($file) {
        return \CFile::GetFileArray($file);
    }

    public static function get_files($files) {
        foreach ($files as &$file) {
            $file = self::get_file($file);
        }
        return $files;
    }

    public static function resize_image($img, $iWidth = 800, $iHeight = 600, $iMode = BX_RESIZE_IMAGE_PROPORTIONAL_ALT) {
        return \CFile::ResizeImageGet($img,[
            'width' => $iWidth,
            'height' => $iHeight,
        ], $iMode);
    }

    public static function resize_images($images, $params = ['width' => 500, 'height' => 500, 'mode' => BX_RESIZE_IMAGE_PROPORTIONAL_ALT], $auxParams = []) {
        if (!$params) $images = self::get_files($images);
        if (empty($auxParams)) $auxParams = [['', $params['width'], $params['height'], $params['mode']]];
        foreach ($images as &$image) {
            if (!is_array($image)) $image = self::get_file($image);
            foreach ($auxParams as $auxParam) {
                $resized = self::resize_image($image['ID'],$auxParam[1]?:$params['width'], $auxParam[2]?:$params['height'], $auxParam[3]?:$params['mode']);
                if ($auxParam[0]) $image[$auxParam[0]] = $resized;
                else $image = $resized;
            }
        }
        return $images;
    }

    public static function get_iblocks($a_params, $b_get_files = true)
    {
        $a_iblocks = [];
        if (is_array($a_params["filter"])) $a_filter = $a_params["filter"];
        $a_sort = ($a_params["sort"]) ?: ['SORT' => 'ASC', 'ID' => "ASC"];
        if ($a_params["id"]) $a_filter["ID"] = $a_params["id"];
        if ($a_params["code"]) $a_filter["CODE"] = $a_params["code"];
        if ($a_params["inactive"]) $a_filter["ACTIVE"] = "";
        else $a_filter["ACTIVE"] = "Y";
        $o_iblocks = \CIBlock::GetList($a_sort, $a_filter, $a_params['count']);
        while ($a_iblock = $o_iblocks->GetNext()) {
            if ($b_get_files) {
                $a_iblock['PICTURE'] = \CFile::GetFileArray($a_iblock['PICTURE']);
            }
            $a_params['index'] ? $a_iblocks[$a_iblock[$a_params['index']]] = $a_iblock : $a_iblocks[] = $a_iblock;
        }
        if ($a_params["only"] || $a_params["id"]) return $a_iblocks[0];
        else return $a_iblocks;
    }

    //returns: (Array|bool) array of found IBlock elements or false if there are no any ones
    //takes: $a_params - an array of parameter, may contain such fields as: "filter" - particular array formatted as Bitrix filter for GetList,
    //"user" - user id (result is filtered by), "iblock" - code of particular IBlock, "inactive" - if it's defined result
    //includes inactive elements, "sort" - an array formatted as Bitrix sort for GetList
    public static function get_iblock_elems($a_params, $b_get_props = false, $b_get_files = false)
    {
        $a_elems = $a_filter = array();
        $a_select = ($a_params["select"]) ?: array("*");
        if (is_array($a_params["filter"])) $a_filter = $a_params["filter"];
        $a_sort = ($a_params["sort"]) ?: array('SORT' => 'ASC', 'ID' => "ASC");
        if ($a_params["user"]) $a_filter["CREATED_BY"] = $a_params["user"];
        if ($a_params["id"]) $a_filter["ID"] = $a_params["id"];
        if ($a_params["iblock"]) $a_filter["IBLOCK_CODE"] = $a_params["iblock"];
        if ($a_params["inactive"]) $a_filter["ACTIVE"] = "";
        else $a_filter["ACTIVE"] = "Y";
        $o_elems = \CIBlockElement::GetList($a_sort, $a_filter, false, false, $a_select);
        $i = 0;
        while ($o_elem = $o_elems->GetNextElement()) {
            $a_elem = $o_elem->GetFields();
            if ($b_get_files) {
                $a_elem['PREVIEW_PICTURE'] = \CFile::GetFileArray($a_elem['PREVIEW_PICTURE']);
                $a_elem['DETAIL_PICTURE'] = \CFile::GetFileArray($a_elem['DETAIL_PICTURE']);
            }
            if ($b_get_props) {
                $a_elem["PROPS"] = $o_elem->GetProperties();
                if ($b_get_files) {
                    foreach ($a_elem['PROPS'] as $key => &$prop) {
                        if ($prop['PROPERTY_TYPE'] !== 'F') continue;
                        if (is_array($prop['VALUE'])) {
                            foreach ($prop['VALUE'] as &$value) {
                                $value = \CFile::GetFileArray($value);
                            }
                        } else $prop['VALUE'] = \CFile::GetFileArray($prop['VALUE']);
                    }
                }
            }
            $i++;
            $a_params['index'] ? $a_elems[$a_elem[$a_params['index']]] = $a_elem : $a_elems[] = $a_elem;
        }
        if ($a_params["only"] || !!$a_params['id']) return $a_elems[0];
        else return $a_elems;
    }

    public static function get_iblock_sections($a_params, $b_get_files = false, $b_get_user_fields = false)
    {
        $a_sections = [];
        $a_select = ($a_params["select"]) ?: ['*'];
        if ($b_get_user_fields) $a_select[] = 'UF_*';
        if (is_array($a_params["filter"])) $a_filter = $a_params["filter"];
        $a_sort = ($a_params["sort"]) ?: ['SORT' => 'ASC', 'ID' => "ASC"];
        if ($a_params["user"]) $a_filter["CREATED_BY"] = $a_params["user"];
        if ($a_params["id"]) $a_filter["ID"] = $a_params["id"];
        if ($a_params["iblock"]) $a_filter["IBLOCK_CODE"] = $a_params["iblock"];
        if ($a_params["inactive"]) $a_filter["ACTIVE"] = "";
        else $a_filter["ACTIVE"] = "Y";
        $o_sections = \CIBlockSection::GetList($a_sort, $a_filter, $a_params['count']?:false, $a_select);
        while ($a_section = $o_sections->GetNext()) {
            if ($b_get_files) {
                $a_section['PICTURE'] = \CFile::GetFileArray($a_section['PICTURE']);
                $a_section['DETAIL_PICTURE'] = \CFile::GetFileArray($a_section['DETAIL_PICTURE']);
            }
            $a_params['index'] ? $a_sections[$a_section[$a_params['index']]] = $a_section : $a_sections[] = $a_section;
        }
        if ($a_params["only"] || $a_params["id"]) return $a_sections[0];
        else return $a_sections;
    }

    public static function get_section_id_by_code($s_iblock_code, $s_iblock_section_code) {
        return \CIBlockSection::GetList([],['IBLOCK_CODE' => $s_iblock_code, 'CODE' => $s_iblock_section_code], false, ['ID'])->Fetch()['ID']?:false;
    }

    public static function get_enum_list($i_block_id, $s_property_code)
    {
        if ($i_block_id > 0) {
            $o_props = \CIBlockProperty::GetList(array(), array(
                "IBLOCK_ID" => $i_block_id,
                "CODE" => $s_property_code
            ))->Fetch();
            $i_prop = $o_props["ID"];
            $o_props = \CIBlockPropertyEnum::GetList(array('sort' => 'asc', 'id' => 'asc'), array(
                "PROPERTY_ID" => $i_prop
            ));
            $a_props = array();
            while ($a_prop = $o_props->GetNext()) {
                $a_prop["VALUE_EN"] = &$a_prop["XML_ID"];
                $a_props[] = $a_prop;
            }
            return $a_props;
        } else return false;
    }

    public static function get_iblock_id($s_code)
    {
        $d_iblock = \CIBlock::GetList(array(), array("CODE" => $s_code))->Fetch();
        return $d_iblock["ID"];
    }

    //returns: (Array) an array of found properties
    //takes: $s_iblock_code - code of particular IBlock, if is not defined, return contains all the properties.
    public static function get_iblock_props($s_iblock_code)
    {
        $o_properties = \CIBlockProperty::GetList(array(), array("IBLOCK_CODE" => $s_iblock_code));
        $a_properties = array();
        while ($a_property = $o_properties->GetNext()) {
            if ($a_property["PROPERTY_TYPE"] == "L") {
                $o_enums = \CIBlockPropertyEnum::GetList(array(), array("PROPERTY_ID" => $a_property["ID"]));
                while ($a_enum = $o_enums->GetNext()) {
                    $a_property["VALUES"][] = $a_enum;
                }
            }
            $a_properties[$a_property["CODE"]] = $a_property;
        }
        return $a_properties;
    }

    //returns: (Array|int) an array of found property of just it's id
    //takes: $s_field_val - value of specific property or "false" if to search by property id, $s_property_code - code
    //of property or it's id (if the first argument equals "false"), $s_iblock_code = code of specific iblock
    //(might not be defined), $s_xml_id - XML_ID of property (can be defined only if IBlock code if defined)
    public static function get_prop_ids($s_field_val = false, $s_property_code = "", $s_iblock_code = "", $s_xml_id = "")
    {
        if ($s_field_val === false) $a_filter = array("CODE" => $s_property_code);
        elseif ($s_field_val === 0) $a_filter = array("ID" => $s_property_code);
        else $a_filter = array("VALUE" => $s_field_val, "CODE" => $s_property_code);
        if ($s_iblock_code) {
            if (is_numeric($s_iblock_code)) $a_filter["IBLOCK_ID"] = $s_iblock_code;
            else $a_filter["IBLOCK_CODE"] = $s_iblock_code;
        }
        if ($s_xml_id) $a_filter["XML_ID"] = $s_xml_id;
        $o_cdb_result = \CIBlockPropertyEnum::GetList(array(), $a_filter, false, false);
        if ($s_field_val) $a_props = $o_cdb_result->Fetch();
        else {
            $a_props = array();
            while ($a_prop = $o_cdb_result->GetNext()) {
                $a_props[$a_prop["ID"]] = $a_prop;
            }
        }
        return ($s_field_val && $s_field_val > 0) ? intval($a_props['ID']) : $a_props;
    }

    //returns: (Array|false) ar array of HLblock entries or entity class (or false if both are not found)
    //takes: $d_id - name of the HLBlock or it's id (if $a_param['block_id'] is defined), $a_params - set of parameters
    //that can includes the following fields: "block_id" (bool) - whether perform search by HLBlock id, "filter" -
    // Bitrix formatted filter (use Bitrix documentation for HLBlocks), "return_class" (bool) - whether return entity,
    //"index" (string) - contains code of particular HLBlock field which will be defined as an array's key;
    public static function get_hlblock_entries($d_id, $a_params = [])
    {
        if (!$a_params["block_id"]) {
            $d_id = self::get_hlblock_id($d_id);
        }
        $a_filter = $a_params['filter'] ?: [];
        $hlblock = HL\HighloadBlockTable::getById($d_id)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_class = $entity->getDataClass();
        if ($a_params["return_class"]) return $entity_class;
        else {
            $a_entries = array();
            $rs_result = $entity_class::GetList(array("select" => $a_params['select'] ?: ['*'], "filter" => $a_filter, "order" => $a_params['order'] ?: ["ID" => "ASC"]));
            while ($a_entry = $rs_result->Fetch()) {
                if ($a_params["index"]) $a_entries[$a_entry[$a_params["index"]]] = $a_entry;
                else $a_entries[] = $a_entry;
            }
            if (empty($a_entries)) return false;
            else return $a_params['only'] ? $a_entries[0] : $a_entries;
        }
    }

    //returns: (int) id of found HLBlock
    //takes: $s_hl - name of the required HLBlock;
    public static function get_hlblock_id($s_hl)
    {
        global $DB;
        $rs_result = $DB->Query("SELECT id FROM `b_hlblock_entity` WHERE name = '" . $s_hl . "'");
        $rs_result = $rs_result->Fetch();
        return $rs_result["id"];
    }

    public static function get_option($iID, $bWithHTML = false)
    {
        $res = CIBlockElement::GetByID($iID);
        if ($ar_res = $res->GetNext()) {
            if ($bWithHTML)
                return $ar_res['PREVIEW_TEXT'];
            else
                return strip_tags($ar_res['PREVIEW_TEXT']);
        } else
            return false;
    }

    public static function get_file_option($iID, $b_path)
    {
        $res = CIBlockElement::GetByID($iID);
        if ($ar_res = $res->GetNext()) {
            if ($ar_res['PREVIEW_PICTURE'])
                if (is_array($ar_res["PREVIEW_PICTURE"])) {
                    return $ar_res["PREVIEW_PICTURE"];
                } else {
                    if (intval($ar_res["PREVIEW_PICTURE"]) > 0) {
                        $a_ret = \CFile::GetFileArray($ar_res["PREVIEW_PICTURE"]);
                        if ($b_path) return $a_ret["SRC"];
                        else return $a_ret;
                    } else return false;
                }
            else {
                $res = CIBlockElement::GetProperty(1, $iID, array(), array("CODE" => "FILE"));
                $ar_res = $res->GetNext();
                if (is_array($ar_res["VALUE"])) {
                    return $ar_res["VALUE"];
                } else {
                    if (intval($ar_res["VALUE"]) > 0) {
                        $a_ret = \CFile::GetFileArray($ar_res["VALUE"]);
                        if ($b_path) return $a_ret["SRC"];
                        else return $a_ret;
                    } else return false;
                }
            }
        } else return false;
    }

    public static function get_name_option($iID, $bStrip = true)
    {
        $res = CIBlockElement::GetByID($iID);
        if ($ar_res = $res->GetNext())
            if ($bStrip)
                return strip_tags($ar_res['NAME']);
            else
                return $ar_res['NAME'];
        else
            return false;
    }

    public static function get_mail_template($iID, $b_html = true)
    {
        $sBody = getOption($iID, $b_html);
        $sTheme = trim(str_replace("Шаблон:", "", getNameOption($iID)));
        return array("body" => $sBody, "subject" => $sTheme);
    }

    //returns: (bool) whether successful or not was the email sending
    //takes: $a_message - an array of email parameters, must contain fields: "subject", "sender" - email of sender,
    // "sender-name", "recipient" - email of recipient.
    public static function send_mail($a_message, $s_type = false)
    {
        switch ($s_type) {
            default:
                $s_template = 'templates/mail_template.php';
        }
        if (!$a_message['body']) {
            ob_start();
            ob_implicit_flush(true);
            require_once($s_template);
            $a_message["body"] = ob_get_contents();
            ob_get_clean();
        }
        $o_mail = new \wrk\classes\mailer($a_message['sender'], $a_message['sender-name']);
        $b_mail = $o_mail->SendMail($a_message['recipient'], $a_message['subject'], $a_message['body']);
        return $b_mail;
    }

    public static function set_status($m_status = self::STATUS_404) {
        switch ($m_status) {
            case 404:
                \CHTTP::SetStatus("404 Not Found");
                define('ERROR_404', 'Y');
                break;
        }
    }

    public static function Reindex_Search()
    {
        $Result = false;
        $Result = CSearch::ReIndexAll(true, 60);
        while (is_array($Result)) {
            $Result = CSearch::ReIndexAll(true, 60, $Result);
        }
        return "Reindex_Search();";
    }

    //returns: (string|false) url of uploaded file or false if the failure took place
    //takes: $s_path - local file path, $d_name - file's name, $s_disk_path - path at Yandex Disk
    public static function send_to_remote_disk($s_path, $d_name, $s_disk_path = false)
    {
        global $o_yadisk;
        $o_yadisk->uploadFile(
            $s_disk_path,
            array(
                'path' => $s_path,
                'size' => filesize($s_path),
                'name' => $d_name
            )
        );

        if ($s_disk_path) $s_url = $o_yadisk->startPublishing($s_disk_path . $d_name);
        else return false;
        //$o_yadisk->delete($s_disk_path.$d_name);			//uncomment while in debug
        return $s_url;
    }
}
?>
<?

namespace wrk\classes;

use \Bitrix\Main\DB\MysqliConnection;

\CModule::IncludeModule("search");
\CModule::IncludeModule("iblock");
\CModule::IncludeModule("highloadblock");

use \Bitrix\Highloadblock as HL;

class main
{

    const PHONE_EXP = "/^(\+?[0-9]{1,3})?(\s?\(?[0-9]{2,4}\)?\s?)?([0-9]{2,4}-?\s?){1,3}$/";
    const EMAIL_EXP = "/^[a-zA-Z0-9-\._]+@([-a-z0-9]+\.)+[a-z]{2,4}$/";
    const DEFAULT_SIZE = 500;
    const STATUS_404 = 404;

    private static $response = array("status" => "error", "msg" => array());

    /**
     * TODO under development!
     *
     * @return array
     */
    public static function _auth_user()
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

    /**
     * TODO under development!
     *
     * @return array
     */
    public static function _reg_user()
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

    /**
     * TODO under development!
     *
     * @return array
     */
    public static function _rec_user()
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

    /**
     * TODO under development!
     *
     * @param $arItem
     * @return mixed
     */
    public static function _pass_moderation($arItem)
    {
        $a_mail_fields = array(
            "MAILTO" => $arItem["USER"]["EMAIL"],
            "NAME" => $arItem["NAME"],
            "LINK" => "http://" . $_SERVER["HTTP_HOST"] . $arItem["DETAIL_PAGE_URL"]
        );
        return \CEvent::Send("MODERATION_PASSED", array("s1", "ru", "en"), $a_mail_fields);
    }

    /**
     * TODO under development!
     *
     * @return array
     */
    public static function _get_ratings()
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

    /**
     * Returns user list
     *
     * @param string $s_group - group name for filtering
     * @param bool $b_index_id - use ids as indexes
     * @param bool $b_unwrap - returns the only element as array
     * @return array|bool|mixed
     */
    public static function get_user_list($s_group = '', $b_index_id = false, $b_unwrap = false)
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
        if (count($a_users) == 1 && $b_unwrap && !$b_index_id) return $a_users[0];
        return $a_users;
    }

    /**
     * TODO under development!
     * Returns user group
     *
     * @param $o_user
     * @param bool $s_role
     * @return bool
     */
    public static function _get_user_group($o_user, $s_role = false)
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

    /**
     * Checks the validity of user's password
     *
     * @param int|string $m_user_id - user id
     * @param string $s_password - user password
     * @return bool
     */
    public static function is_user_password($m_user_id, $s_password)
    {
        $a_user_data = \CUser::GetByID($m_user_id)->Fetch();
        $s_salt = substr($a_user_data['PASSWORD'], 0, (strlen($a_user_data['PASSWORD']) - 32));
        $s_real_password = substr($a_user_data['PASSWORD'], -32);
        $s_password = md5($s_salt . $s_password);
        return ($s_password == $s_real_password);
    }

    /**
     * Returns Bitrix Price array
     *
     * @param int|string $m_id - item id
     * @param string $s_fallback - fallback for empty result
     * @return string
     */
    public static function get_base_price($m_id, $s_fallback = '') {
        return \CPrice::GetBasePrice($m_id)?:$s_fallback;
    }

    /**
     * Retrieves Bitrix Property array or empty array if fails
     *
     * @param int|string $m_id - item id
     * @param string $s_prop_code - property code
     * @return array
     */
    public static function get_element_prop($m_id, $s_prop_code) {
        $a_elem = self::get_iblock_elems(['id' => $m_id]);
        if (!empty($a_elem)) return [];
        return \CIBlockElement::GetProperty($a_elem['IBLOCK_ID'], $a_elem['ID'], 'CODE', 'ASC', ['CODE' => $s_prop_code])->Fetch()[$s_prop_code]?:[];
    }

    /**
     * Retrieves Bitrix File array
     *
     * @param int|string $m_file - file id
     * @return mixed
     */
    public static function get_file($m_file) {
        return \CFile::GetFileArray($m_file);
    }

    /**
     * Retrieves an array of multiple Bitrix Files
     *
     * @param array $a_files - array of files ids
     * @return mixed
     */
    public static function get_files($a_files) {
        foreach ($a_files as &$m_file) {
            $m_file = self::get_file($m_file);
        }
        return $a_files;
    }

    /**
     * Returns an array of resized image
     *
     * @param int|string $m_img - image file ID
     * @param int[]|string[] $a_sizes - array of ints sizes as: [width [,height]]. If height is skipped, width is used instead
     * @param int $i_mode - Bitrix Resize Mode
     * @return mixed
     */
    public static function resize_image($m_img, $a_sizes = [], $i_mode = BX_RESIZE_IMAGE_PROPORTIONAL_ALT) {
        return \CFile::ResizeImageGet($m_img['ID']?:$m_img,[
            'width' => $a_sizes[0]?:self::DEFAULT_SIZE,
            'height' => $a_sizes[1]?:$a_sizes[0]?:self::DEFAULT_SIZE,
        ], $i_mode);
    }

    /**
     * Resizes multiple images for several dimentions
     * @param array $a_images - array of images
     * @param array $a_sizes - array of sizes. if key provided - returns each resize under the appropriate key, otherwise merges with original array.
     * @param bool $b_get_file - if true - retrieves the original array
     * @return mixed - array of resized images
     */
    public static function resize_images($a_images, $a_sizes = [self::DEFAULT_SIZE], $b_get_file = true) {
        foreach ($a_images as &$image) {
            if (empty($image)) continue;
            if (!is_array($image) && $b_get_file) $image = self::get_file($image);
            foreach ($a_sizes as $key => $auxParam) {
                $resized = self::resize_image($image,[$a_sizes[0]?:self::DEFAULT_SIZE, $a_sizes[1]?:$a_sizes[0]?:self::DEFAULT_SIZE], $a_sizes[2]?:BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                if (!is_numeric($key)) $image[$key] = $resized;
                else $image = array_merge($image, $resized);
            }
        }
        return $a_images;
    }

    /**
     * Retrieves the list of IBlocks as arrays
     *
     * @param array $a_params - array of the parameters:
     * [code] string - a code of the Iblock
     * [count] bool - retrieve the count of elements in each IBlock
     * [filter] array - Bitrix filter notation
     * [id] int|string - an ID of the IBlock
     * [inactive] bool - retrieve all IBlocks despote of ACTIVE field
     * [index] string - name of the fields to index IBlocks by it's value (use cautiously, recommended fields containing only unique values e.g. ID)
     * [only] - return the only one unwrapped IBLock array. the same behaviour provided for the [id] param
     * [sort] array - Bitrix sort notation
     * @param bool $b_get_files - retrieve files for IBlocks
     * @return array
     */
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
            if ($a_params["only"] || $a_params["id"]) return $a_iblock;
            $a_params['index'] ? $a_iblocks[$a_iblock[$a_params['index']]] = $a_iblock : $a_iblocks[] = $a_iblock;
        }
        return $a_iblocks;
    }

    /**
     * Retrieves the list of IBlockElements as arrays
     *
     * @param array $a_params - array of the parameters:
     * [filter] array - Bitrix filter notation
     * [group] - array of fields to group IBlockElements
     * [id] int|string - an ID of the IBlockElement
     * [inactive] bool - retrieve all IBlocksElements despote of ACTIVE field
     * [index] string - name of the fields to index IBlockElements by it's value (use cautiously, recommended fields containing only unique values e.g. ID)
     * [only] - return the only one unwrapped IBLockElement array. the same behaviour provided for the [id] param
     * [props] - array of properties parameters (using Bitrix notation). available fields - [order], [filter]
     * [select] array - array of fields to select
     * [sort] array - Bitrix sort notation
     * [user] int|string - user id to select IBlockElements created by user
     * @param bool $b_get_props - retrieve properties for each IBlockElements. If [props] is not set, retrieves all the properties
     * @param bool $b_get_files - retrieves file arrays for each IBlockElement, even for the property set as file
     * @return array
     */
    public static function get_iblock_elems($a_params, $b_get_props = false, $b_get_files = false)
    {
        $a_elems = $a_filter = array();
        $a_select = ($a_params["select"]) ?: array("*");
        $a_group = ($a_params["group"]) ?: false;
        if (is_array($a_params["filter"])) $a_filter = $a_params["filter"];
        $a_sort = ($a_params["sort"]) ?: array('SORT' => 'ASC', 'ID' => "ASC");
        if ($a_params["user"]) $a_filter["CREATED_BY"] = $a_params["user"];
        if ($a_params["id"]) $a_filter["ID"] = $a_params["id"];
        if ($a_params["iblock"]) $a_filter["IBLOCK_CODE"] = $a_params["iblock"];
        if ($a_params["inactive"]) $a_filter["ACTIVE"] = "";
        else $a_filter["ACTIVE"] = "Y";
        $o_elems = \CIBlockElement::GetList($a_sort, $a_filter, $a_group, $a_params['nav']?:false, $a_select);
        $i = 0;
        while ($o_elem = $o_elems->GetNextElement()) {
            $a_elem = $o_elem->GetFields();
            if ($b_get_files) {
                $a_elem['PREVIEW_PICTURE'] = \CFile::GetFileArray($a_elem['PREVIEW_PICTURE']);
                $a_elem['DETAIL_PICTURE'] = \CFile::GetFileArray($a_elem['DETAIL_PICTURE']);
                if (is_array($b_get_files)) {
                    foreach ($b_get_files as $s_field) {
                        $a_elem[$s_field] = self::{'get_file'.(is_array($s_field) ? 's' : '')}($a_elem[$s_field]);
                    }
                }
            }
            if ($b_get_props) {
                $a_elem["PROPS"] = $o_elem->GetProperties($a_params['props']['order']?:false,$a_params['props']['filter']?:[]);
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
            if ($a_params["only"] || !!$a_params['id']) return $a_elem;
            $i++;
            if (!!$a_params['index']) {
                if (is_array($a_params['index'])) {
                    $a_elems[$a_elem['PROPS'][$a_params['index'][0]][$a_params['index'][1]?:'VALUE']] = $a_elem;
                } else {
                    $a_elems[$a_elem[$a_params['index']]] = $a_elem;
                }
            } else $a_elems[] = $a_elem;
        }
        return $a_elems;
    }

    /**
     * Retrieves the list of IBlockSections as arrays
     *
     * @param array $a_params - array of the parameters:
     * [files] array - array of fields codes containing files to retrieve
     * [filter] array - Bitrix filter notation
     * [id] int|string - an ID of the IBlockSection
     * [inactive] bool - retrieve all IBlockSections despote of ACTIVE field
     * [index] string - name of the fields to index IBlockSEction by it's value (use cautiously, recommended fields containing only unique values e.g. ID)
     * [only] - return the only one unwrapped IBLock array. the same behaviour provided for the [id] param
     * [select] array - array of fields to select
     * [sort] array - Bitrix sort notation
     * [user] int|string - user id to select IBlockSections created by user
     * @param bool $b_get_files - retrieve files
     * @param bool $b_get_user_fields - retrieve user fields
     * @return array|mixed
     */
    public static function get_iblock_sections($a_params, $b_get_files = false, $b_get_user_fields = false)
    {
        $a_sections = [];
        if (is_array($a_params["filter"])) $a_filter = $a_params["filter"];
        $a_sort = ($a_params["sort"]) ?: ['SORT' => 'ASC', 'ID' => "ASC"];
        if ($a_params["user"]) $a_filter["CREATED_BY"] = $a_params["user"];
        if ($a_params["id"]) $a_filter["ID"] = $a_params["id"];
        if ($a_params["iblock"]) $a_filter["IBLOCK_CODE"] = $a_params["iblock"];
        if ($a_params["inactive"]) $a_filter["ACTIVE"] = "";
        else $a_filter["ACTIVE"] = "Y";
        $a_select = ($a_params["select"]) ?: ['*'];
        if ($b_get_user_fields) {
            $a_select[] = 'UF_*';
            if (!!$a_params['iblock'] && !$a_params['filter']['IBLOCK_ID']) {
                $a_filter['IBLOCK_ID'] = self::get_iblock_id($a_params['iblock']);
            }
        }
        $o_sections = \CIBlockSection::GetList($a_sort, $a_filter, $a_params['count']?:false, $a_select);
        while ($a_section = $o_sections->GetNext()) {
            if ($b_get_files) {
                $a_section['PICTURE'] = \CFile::GetFileArray($a_section['PICTURE']);
                $a_section['DETAIL_PICTURE'] = \CFile::GetFileArray($a_section['DETAIL_PICTURE']);
                if (!empty($a_params['files'])) {
                    foreach ($a_params['files'] as $s_field) {
                        if (array_key_exists($s_field, $a_section)) {
                            if (is_array($a_section[$s_field])) $a_section[$s_field] = self::get_files($a_section[$s_field]);
                            else self::get_file($a_section[$s_field]);
                        }
                    }
                }
            }
            $a_params['index'] ? $a_sections[$a_section[$a_params['index']]] = $a_section : $a_sections[] = $a_section;
        }
        if ($a_params["only"] || $a_params["id"]) return $a_sections[0];
        else return $a_sections;
    }

    /**
     * Returns Section ID or false if doesn't exist
     *
     * @param $s_iblock_code - IBlock Code
     * @param $s_iblock_section_code - IBlockSection Code
     * @return string|bool
     */
    public static function get_section_id_by_code($s_iblock_code, $s_iblock_section_code) {
        return \CIBlockSection::GetList([],['IBLOCK_CODE' => $s_iblock_code, 'CODE' => $s_iblock_section_code], false, ['ID'])->Fetch()['ID']?:false;
    }

    /**
     * Returns list of enumerates of LIST type property or false in case of error
     *
     * @param $i_block_id - Iblock ID
     * @param $s_property_code - Property code
     * @return array|bool
     */
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

    /**
     * Retrieves Iblock ID by its code or false in case of error
     *
     * @param $s_code - IBlock Code
     * @return string|bool
     */
    public static function get_iblock_id($s_code)
    {
        return \CIBlock::GetList(array(), array("CODE" => $s_code))->Fetch()["ID"]?:false;
    }

    /**
     * Retrieves the list of properties due to set parameters
     *
     * @param array $a_params - list parameters:
     * [filter] array - Bitrix notations filter
     * [order] array|bool - Bitrix notation order
     * [iblock] string - IBlock code
     * @return array
     */
    public static function get_iblock_props($a_params)
    {
        $a_filter = $a_params['filter']?:[];
        $a_order = $a_params['order']?:[];
        if (!empty($a_params['iblock'])) $a_filter['IBLOCK_CODE'] = $a_params['IBLOCK_CODE'];
        $o_properties = \CIBlockProperty::GetList($a_order, $a_filter);
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

    /**
     * Returns an array of IBlockProperty values
     *
     * @param string $s_iblock - IBlock code
     * @param string $s_prop_code - Property code
     * @param array $a_filter - Bitrix notation property filter
     * @param bool $b_counts - if set as true the returning value si going to be an arranged array of counted values grouped by the value as the key
     * @return array
     */
    public static function get_iblock_prop_vals($s_iblock, $s_prop_code, $a_filter = [], $b_counts = true) {
        $a_result = [];
        $s_prop_code = 'PROPERTY_'.strtoupper($s_prop_code);
        if (!!$s_iblock) $a_filter['IBLOCK_CODE'] = $s_iblock;
        $o_vals = \CIBlockElement::GetList([$s_prop_code => 'ASC'], $a_filter, [$s_prop_code]);
        while($a_val = $o_vals->GetNext()) {
            $a_result[$a_val[$s_prop_code.'_VALUE']] = $a_val['CNT'];
        }
        return $b_counts ? $a_result : array_keys($a_filter);
    }

    /**
     * Returns an array of mapped properties with grouped by value counters
     *
     * @param string $s_iblock - IBlock code
     * @param array $a_select - an array of selected fields
     * @param array $a_filter - Bitrix notation filtering array
     * @param bool $b_skip_empty - if set true, skips empty value cases
     * @return array
     */
    public static function get_iblock_props_vals_mapped($s_iblock, $a_select = [], $a_filter = [], $b_skip_empty = true) {
        $a_result = [];
        if (!!$s_iblock) $a_filter['IBLOCK_CODE'] = $s_iblock;
        if (!is_array($a_select)) {
            $a_select = [$a_select];
        }
        $o_vals = \CIBlockElement::GetList([], $a_filter, false, false, ['ID', 'IBLOCK_ID']);
        while($o_val = $o_vals->GetNextElement()) {
            if (!empty($a_select)) {
                foreach ($a_select as $a_prop) {
                    $a_prop = $o_val->GetProperty($a_prop);
                    if (empty($a_prop['VALUE'])) continue;
                    if (is_array($a_prop['VALUE']))
                        foreach ($a_prop['VALUE'] as $value)
                            ++$a_result[$a_prop['CODE']][$value];
                    else ++$a_result[$a_prop['CODE']][$a_prop['VALUE']];
                }
            } else {
                $a_filter = [];
                if ($b_skip_empty) $a_filter['EMPTY'] = "N";
                $a_props = $o_val->GetProperties(['CODE' => 'ASC'], $a_filter);
                foreach ($a_props as $a_prop) {
                    if (is_array($a_prop['VALUE']))
                        foreach ($a_prop['VALUE'] as $value)
                            ++$a_result[$a_prop['CODE']][$value];
                    else ++$a_result[$a_prop['CODE']][$a_prop['VALUE']];
                }
            }
        }
        return $a_result;
    }

    /**
     * Returns a property or its id (depends on arguments)
     *
     * @param bool $s_field_val - a value of the specific property or "false" if need to search by property id
     * @param string|int $s_property_code - property code or it's id (if the first argument is "false")
     * @param string $s_iblock_code - IBlock code
     * @param string $s_xml_id -  Property XML_ID (can be defined only if IBlock code if defined)
     * @return array|int
     */
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

    /**
     * Returns ar array of HLblock entries or entity class (or false if both are not found)
     *
     * @param int|string $m_id - HILoadBlock id or its code
     * @param array $a_params - array or parameters with following keys:
     * [entity] bool - if is true - returns the class entity
     * [files] array - a list of fields containing files to retrieve
     * [filter] array - Bitrix notation filter
     * [index] string - name of the field to index the final array by its value
     * [only] bool - if is true - returns the only one unwrapped element
     * [order] array - Bitrix notation order array
     * [select] array - a list of the fields to select
     * @return array|bool|mixed
     */
    public static function get_hlblock_entries($m_id, $a_params = [])
    {
        if (!is_numeric($m_id)) {
            $m_id = self::get_hlblock_id($m_id);
        }
        $a_filter = $a_params['filter'] ?: [];
        $hlblock = HL\HighloadBlockTable::getById($m_id)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_class = $entity->getDataClass();
        if ($a_params["entity"]) return $entity_class;
        else {
            $a_entries = array();
            $rs_result = $entity_class::GetList(array("select" => $a_params['select'] ?: ['*'], "filter" => $a_filter, "order" => $a_params['order'] ?: ["ID" => "ASC"]));
            while ($a_entry = $rs_result->Fetch()) {
                if (is_array($a_params['files']) && count($a_params['files'])) {
                    foreach ($a_params['files'] as $s_code) {
                        $method  = is_array($a_entry[$s_code]) ? 'get_files' : 'get_file';
                        $a_entry[$s_code] = self::$method($a_entry[$s_code]);
                    }
                }
                if ($a_params["index"]) $a_entries[$a_entry[$a_params["index"]]] = $a_entry;
                else $a_entries[] = $a_entry;
            }
            if (empty($a_entries)) return false;
            else return $a_params['only'] ? $a_entries[0] : $a_entries;
        }
    }

    /**
     * Returns HIloadBlock id by its code
     *
     * @param string $s_hl - HLBlock code
     * @return mixed
     */
    public static function get_hlblock_id($s_hl)
    {
        global $DB;
        return $DB->Query("SELECT id FROM `b_hlblock_entity` WHERE name = '{$s_hl}'")->Fetch()["id"]?:false;
    }

    /**
     * Returns PREVIEW_TEXT of an element provided vie its ID
     *
     * @param int|string $i_id - IBlockElement ID
     * @param bool $b_html - keep html switch
     * @return bool|string
     */
    public static function get_option($i_id, $b_html = false)
    {
        $res = \CIBlockElement::GetByID($i_id);
        if ($ar_res = $res->GetNext()) {
            if ($b_html)
                return $ar_res['PREVIEW_TEXT'];
            else
                return strip_tags($ar_res['PREVIEW_TEXT']);
        } else
            return false;
    }


    /**
     * Returns the the file array of IBlockElement or its path by its code
     *
     * @param int|string $i_id - IBlockElement id
     * @param string $s_field - field name or property code of IBlockElement
     * @param bool $b_path - if is true returns file path
     * @return array|string|bool - returns fale in case of error or non exist
     */
    public static function get_file_option($i_id, $s_field = 'PREVIEW_PICTURE', $b_path = true)
    {
        $res = \CIBlockElement::GetByID($i_id);
        if ($ar_res = $res->GetNext()) {
            $a_ret = false;
            switch ($s_field) {
                case 'PREVIEW_PICTURE':
                case 'DETAIL_PICTURE':
                    if (is_array($ar_res[$s_field])) {
                        $a_ret = $ar_res[$s_field];
                    } else {
                        if (intval($ar_res[$s_field]) > 0) {
                            $a_ret = \CFile::GetFileArray($ar_res[$s_field]);
                        } else return false;
                    }
                    if ($b_path) return $a_ret["SRC"];
                    else return $a_ret;
                    break;
                default:
                    $res = \CIBlockElement::GetProperty($ar_res['IBLOCK_ID'], $i_id, array(), array("CODE" => $s_field));
                    $ar_res = $res->GetNext();
                    if (is_array($ar_res["VALUE"])) {
                        $a_ret = $ar_res["VALUE"];
                    } else {
                        if (intval($ar_res["VALUE"]) > 0) {
                            $a_ret = \CFile::GetFileArray($ar_res["VALUE"]);
                        } else return false;
                    }
                    if ($b_path) return $a_ret["SRC"];
                    else return $a_ret;
            }
        } else return false;
    }

    /**
     * Returns the name of IBlockElement
     *
     * @param int|string $i_id - IBlockElement id
     * @param bool $b_html - if is true keeps html
     * @return bool|string - returns false in case of fail or error
     */
    public static function get_name_option($i_id, $b_html = false)
    {
        $res = \CIBlockElement::GetByID($i_id);
        if ($ar_res = $res->GetNext())
            if (!$b_html)
                return strip_tags($ar_res['NAME']);
            else
                return $ar_res['NAME'];
        else
            return false;
    }

    /**
     * Sends an email
     *
     * @param array $a_message - an array of parameters containing following fields:
     * [body] string - the body of the message
     * [recipient] string - email address of the recipient
     * [sender] string - sender email address
     * [sender-name] string - the name of the sender
     * [subject] string - email subject
     * @param string $s_type - name of the template
     * @return bool - returns false in case of error
     */
    public static function send_mail($a_message, $s_type = 'mail')
    {
        $s_template = "templates/{$s_type}_template.php";
        if (!$a_message['body']) {
            ob_start();
            ob_implicit_flush(true);
            require_once($s_template);
            $a_message["body"] = ob_get_contents();
            ob_get_clean();
        }
        $o_mail = new \wrk\classes\mailer($a_message['sender'], $a_message['sender-name']);
        return $o_mail->SendMail($a_message['recipient'], $a_message['subject'], $a_message['body']);
    }

    /**
     * Sets the document status
     *
     * @param int $m_status - status code
     */
    public static function set_status($m_status = self::STATUS_404) {
        switch ($m_status) {
            case 404:
                \CHTTP::SetStatus("404 Not Found");
                define('ERROR_404', 'Y');
                break;
        }
    }

    /**
     * Search results reindexing procedure
     */
    public static function reindex_search()
    {
        $Result = \CSearch::ReIndexAll(true, 60);
        while (is_array($Result)) {
            $Result = \CSearch::ReIndexAll(true, 60, $Result);
        }
    }

    /**
     * Uploads the file to remote Yandex Disc
     *
     * @param $s_path - file path
     * @param $d_name - Yandex Disc name
     * @param bool $s_disk_path - Yandex dist path
     * @return string|bool - returns url string in case of success, otherwise returns false
     */
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
        //$o_yadisk->delete($s_disk_path.$d_name); //uncomment in case of debug
        return $s_url;
    }
}
?>
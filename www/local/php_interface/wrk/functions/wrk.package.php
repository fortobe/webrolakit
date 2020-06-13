<?php
/**
 * WebRolaKit functions compilation v.2.0
 *
 * @author Serge Rola <serge.rola@gmail.com> and others (provided if are known)
 *
 */

/**
 * Initialises WRK classes
 *
 * @param string $s_class
 */
function app_init($s_class = '') {
    global $o_actions;
    require($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/wrk/classes/wrk.mailer.class.php");
    require($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/wrk/classes/wrk.main.class.php");
    require($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/wrk/classes/wrk.actions.class.php");
    if ($s_class) require($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/wrk/classes/wrk.".$s_class.".class.php");
    $o_actions = new \wrk\classes\wrk_actions;
}

/**
 * TODO under development
 *
 * @param $array
 * @param $key
 * @param $value
 * @return mixed
 */
function _back_in_array($array, $key, $value) {
    $results = array();

    if (is_array($array))
    {
        if (isset($array[$key]) && $array[$key] == $value)
            $results[] = $array;

        foreach ($array as $subarray)
            $results = array_merge($results, search($subarray, $key, $value));
    }

    return current($results);
}

/**
 * Returns clean phone string, e.g.: +79998887766;
 *
 * @param $sPhone
 * @return string|string[]|null
 */
function clear_phone_formatting($sPhone) {
    return preg_replace("/[^\+\d]/", "", $sPhone);
}

/**
 * Downloads files via web interface
 *
 * @param $url
 * @param $target
 * @return bool
 */
function download_via_web($url, $target) {
    if(!$hfile = fopen($target, "w")) return false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FILE, $hfile);

    if(!curl_exec($ch)){
        curl_close($ch);
        fclose($hfile);
        unlink($target);
        return false;
    }

    fflush($hfile);
    fclose($hfile);
    curl_close($ch);
    return true;
}

/**
 * Formats array output
 *
 * @param $ar
 * @param bool $die
 */
function ea($ar, $die = true) {
    echo "<pre>";
    print_r($ar);
    echo "</pre>";
    if ($die) die('.....end of debug.');
}

/**
 * Formats date to DB ready string
 *
 * @param $s_in
 * @return string
 */
function format_db_date($s_in) {
    return date('Y-m-d', strtotime($s_in));
}

/**
 * Formats number to thousand separated;
 *
 * @param $i_num
 * @return string
 */
function format_num($i_num = 0) {
    if (!is_numeric($i_num)) {
        return $i_num;
    } else {
        $i_nem_num = '';
        $a_num = array_reverse(str_split($i_num));
        foreach($a_num as $i_key => $i_val) {
            $i_nem_num = $i_val.$i_nem_num;
            if (($i_val + 1) % 3 === 0) {
                $i_nem_num = ' '.$i_nem_num;
            }
        }
        return $i_nem_num;
    }
}

/**
 * Formats number to proce string
 *
 * @param $s_in
 * @param string $s_postfix
 * @return string
 */
function format_price($s_in, $s_postfix = '.-') {
    return (format_num(trim($s_in))?:'0').$s_postfix;
}

/**
 * Extracts domain name from the link string
 *
 * @param $s_link
 * @return string|null
 */
function get_clear_link($s_link) {
    $s_link = preg_replace("#https?:\/\/#", "", $s_link);
    $a_link = explode("/", $s_link);
    $s_link = $a_link[0];
    return $s_link;
}
function get_count_days($s_sced_date, $b_full = true) {
    $s_sced_date = ($b_full)? $s_sced_date : explode(" ", $s_sced_date);
    if ($b_full) $s_sced_date = $s_sced_date[0];
    $a_return = array();
    if($s_sced_date !== null) {
        $b_delay = is_delayed($s_sced_date, $b_full);
        $o_sced = new \DateTime($s_sced_date);
        $o_today = get_today(true, $b_full);
        $d_days = $o_today->diff($o_sced)->days;
        if(substr($d_days , 0, 1) == "0" && strlen($d_days) > 1) $d_days = substr($d_days, 1);
        $s_pre = "Просрочено на ";
        if ($d_days >= 0) {
            if ($d_days <= 10 || $d_days >= 20 || ($d_days >= 100 & substr(strval($d_days), -2))) {
                switch(substr($d_days,-1)){
                    case "1":
                        $s_desc = "день";
                        $s_pre = ($b_delay)? $s_pre : "Остался ";
                        break;
                    case "2":
                    case "3":
                    case "4":
                        $s_desc = "дня";
                        $s_pre = ($b_delay)? $s_pre : "Осталось ";
                        break;
                    default:
                        $s_desc = "дней";
                        $s_pre = ($b_delay)? $s_pre : "Осталось ";
                }
            }
            else {
                $s_pre = ($b_delay)? $s_pre : "Осталось ";
                $s_desc = "дней";
            }
        }
        else $s_desc = "дней";
        if ($s_sced_date == get_today(false, $b_full)) $a_return["caption"] = "today";
        else $a_return["caption"] = $s_pre." ".$d_days." ".$s_desc;
        $a_return["delay"] = $b_delay;
    }
    else $a_return = array("caption" => false, "delay" => false);
    return $a_return;
}
function get_date_month($s_date) {
    $a_date = explode(" ", $s_date);
    $a_date = explode(".", $a_date[0]);
    $s_date = $a_date[2];
    $a_months = get_months();
    foreach($a_months as $a_month) {
        if ($a_month["ID"]*1==$a_date[1]*1) {
            return $a_month["NAME"].' '.$s_date;
        }
    }
}
function get_date_month_day($s_date) {
    $a_date = explode(" ", $s_date);
    $a_date = explode(".", $a_date[0]);
    $s_date = $a_date[2];
    $a_months = get_months();
    foreach($a_months as $a_month) {
        if ($a_month["ID"]*1==$a_date[1]*1) {
            return $a_date[0].' '.$a_month["NAME"];
        }
    }
}
function get_declensions($i_count, $a_desc, $b_return_array = false) {
    if ($i_count >= 0) {
        if ($i_count <= 10 || $i_count >= 20 || ($i_count >= 100 & substr(strval($i_count), -2))) {
            switch(substr($i_count,-1)){
                case "1":
                    $s_desc = $a_desc[0];
                    break;
                case "2":
                case "3":
                case "4":
                    $s_desc = $a_desc[1];
                    break;
                default:
                    $s_desc = $a_desc[2];
            }
        }
        else {
            $s_desc = $a_desc[2];
        }
        if (!$i_count && !empty($a_desc[3])) $i_count = $a_desc[3];
    } else return false;
    if($b_return_array) return array("count" => $i_count, "desc" => $s_desc);
    else return $i_count." ".$s_desc;
}
function get_images($url, $image_dir, $image_name)
{
    $image_name++;
    $savefile = $image_dir ."/". $image_name  . ".jpg";

    $ch = curl_init ($url);
    $fp = fopen ( $savefile, "wb");
    if (!$fp)
        write_log('Не удалось открыть файл для сохранения изображения ' . $url);
    curl_setopt ($ch, CURLOPT_FILE, $fp);
    curl_setopt ($ch, CURLOPT_HEADER, 0);
    curl_exec ($ch);
    curl_close ($ch);
    fclose ($fp);
    header("Content-type: application/x-download");
    header("Content-Disposition: attachment; filename=$image_name.jpg");
    readfile($savefile);
}
function get_link($s_link) {
    if (!strstr($s_link, "http://")) {
        return "http://".$s_link;
    } else {
        return $s_link;
    }
}
function get_months() {
    return array(
        array(
            "ID" => "1",
            "NAME" => "Январь",
            "GEN" => "Января"
        ),
        array(
            "ID" => "2",
            "NAME" => "Февраль",
            "GEN" => "Февраля"
        ),
        array(
            "ID" => "3",
            "NAME" => "Март",
            "GEN" => "Марта"
        ),
        array(
            "ID" => "4",
            "NAME" => "Апрель",
            "GEN" => "Апреля"
        ),
        array(
            "ID" => "5",
            "NAME" => "Май",
            "GEN" => "Мая"
        ),
        array(
            "ID" => "6",
            "NAME" => "Июнь",
            "GEN" => "Июня"
        ),
        array(
            "ID" => "7",
            "NAME" => "Июль",
            "GEN" => "Июля"
        ),
        array(
            "ID" => "8",
            "NAME" => "Август",
            "GEN" => "Августа"
        ),
        array(
            "ID" => "9",
            "NAME" => "Сентябрь",
            "GEN" => "Сентября"
        ),
        array(
            "ID" => "10",
            "NAME" => "Октябрь",
            "GEN" => "Октября"
        ),
        array(
            "ID" => "11",
            "NAME" => "Ноябрь",
            "GEN" => "Ноября"
        ),
        array(
            "ID" => "12",
            "NAME" => "Декабрь",
            "GEN" => "Декабря"
        ),
    );
}
function get_month_num($s_month) {
    $s_month = $s_month*1;
    if ($s_month>=10)
        return $s_month;
    else
        return "0".$s_month;
}
function get_random_string($length) {
    $chars = "0123456789abcdefjhijklmnopqrstuvwxyzABCDEFJHIGKLMNOPQRSTUVWXYZ-_";
    $res = '';
    for($i = 0; $i < $length; $i++) {
        $res .= $chars[rand(0, strlen($chars))];
    }
    return $res;
}
function get_stats($a_scope) {
    foreach ($a_scope as $a_item => &$values) {
        if ($values == false) $values = 0;
        else if (isset($values["ID"])) $values = 1;
        else $values = count($values);
    }
    return $a_scope;
}
function get_today($b_object = true, $b_full = true){
    $s_contsruct = ($b_full)? "d.m.Y H:i:s" : "d.m.Y";
    $d_today = ($b_object)? new \DateTime(date($s_contsruct)) : date($s_contsruct);
    return $d_today;
}
function get_years($i_init = 1990, $b_reverse = false) {
    $a_years = array();
    if ($b_reverse) {
        for ($i=date("Y");$i>=$i_init; $i--) {
            $a_years[] = array("ID"=>$i, "NAME"=>$i);
        }
    } else {
        for ($i=$i_init;$i<=date("Y"); $i++) {
            $a_years[] = array("ID"=>$i, "NAME"=>$i);
        }
    }
    return $a_years;
}
function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}
function init_template($a_params = "all", $b_local = false) {
    global $APPLICATION;
    $s_path = ($b_local)? SITE_DIR."/local" : SITE_TEMPLATE_PATH;
    if ($a_params == "all") $a_params = array(
        "font-exotwo",
        "font-awesome",
        "font-myriadpro",
        "font-pfd",
        "font-bravo",
        "font-roboto",
        "font-proxima",
        "font-gotham",
        "css-normalize",
        "js-jquery",
        "init-wrk",
        "wrk-tooltip",
        "wrk-parallax",
        "wrk-accordeon",
        "js-light-scroll",
        "js-sticky",
        "js-mask",
        "lib-slider",
        "lib-modal",
        "lib-modal-helpers",
        "lib-form-styler"
    );
    $s_plugins = "";
    foreach($a_params as $a_param){
        switch($a_param){
            case "font-exotwo":
                $APPLICATION->SetAdditionalCSS($s_path."/fonts/exo/exotwo.css");
                break;
            case "font-awesome":
                $APPLICATION->SetAdditionalCSS($s_path."/fonts/fontawesome/font-awesome.min.css");
                break;
            case "font-myriadpro":
                $APPLICATION->SetAdditionalCSS($s_path."/fonts/myriadpro/myriadpro.css");
                break;
            case "font-pfd":
                $APPLICATION->SetAdditionalCSS($s_path."/fonts/pfd/pfd.css");
                break;
            case "font-bravo":
                $APPLICATION->SetAdditionalCSS($s_path."/fonts/bravo/bravo.css");
                break;
            case "font-roboto":
                $APPLICATION->SetAdditionalCSS($s_path."/fonts/roboto/roboto.css");
                break;
            case "font-proxima":
                $APPLICATION->SetAdditionalCSS($s_path."/fonts/proxima_nova/proxima_nova.css");
                break;
            case "font-gotham":
                $APPLICATION->SetAdditionalCSS($s_path."/fonts/gotham_pro/gotham_pro.css");
                break;
            case "css-normalize":
                $APPLICATION->SetAdditionalCSS($s_path."/css/normalize.css");
                break;
            case "js-jquery":
                $APPLICATION->AddHeadScript($s_path."/js/jquery.min.js");
                break;
            case "init-wrk":
                $APPLICATION->SetAdditionalCSS($s_path."/css/wrk.css");
                $APPLICATION->AddHeadScript($s_path."/js/wrk.js");
                break;
            case "js-mask":
                $APPLICATION->AddHeadScript($s_path."/lib/jquery-mask-plugin/dist/jquery.mask.min.js");
                $s_plugins .="mask;";
                break;
            case "wrk-tooltip":
                $s_plugins .="wrktooltip;";
                break;
            case "wrk-parallax":
                $s_plugins .="wrkparallax;";
                break;
            case "wrk-accordeon":
                $s_plugins .="wrkaccordeon;";
                break;
            case "js-light-scroll":
                $s_plugins .="light-scroll;";
                break;
            case "js-sticky":
                $APPLICATION->AddHeadScript($s_path."/lib/jquery-sticky/jquery.sticky.js");
                $s_plugins .="sticky;";
                break;
            case "lib-slider":
                $APPLICATION->SetAdditionalCSS($s_path."/lib/slick-carousel/slick/slick.css");
                $APPLICATION->AddHeadScript($s_path."/lib/slick-carousel/slick/slick.min.js");
                $s_plugins .= "slider;";
                break;
            case "lib-modal":
                $APPLICATION->SetAdditionalCSS($s_path."/lib/fancybox/source/jquery.fancybox.css");
                $APPLICATION->AddHeadScript($s_path."/lib/fancybox/source/jquery.fancybox.pack.js");
                $s_plugins .= "modal;";
                break;
            case "lib-modal-helpers":
                $APPLICATION->SetAdditionalCSS($s_path."/lib/fancybox/source/helpers/jquery.fancybox-buttons.css");
                $APPLICATION->SetAdditionalCSS($s_path."/lib/fancybox/source/helpers/jquery.fancybox-thumbs.css");
                $APPLICATION->AddHeadScript($s_path."/lib/fancybox/source/helpers/jquery.fancybox-buttons.js");
                $APPLICATION->AddHeadScript($s_path."/lib/fancybox/source/helpers/jquery.fancybox-media.js");
                $APPLICATION->AddHeadScript($s_path."/lib/fancybox/source/helpers/jquery.fancybox-thumbs.js");
                break;
            case "lib-form-styler":
                $APPLICATION->SetAdditionalCSS($s_path."/lib/jquery-form-styler/jquery.formstyler.css");
                $APPLICATION->AddHeadScript($s_path."/lib/jquery-form-styler/jquery.formstyler.js");
                break;
            case "lib-all":
                $APPLICATION->SetAdditionalCSS($s_path."/css/plugins.css");
                $APPLICATION->AddHeadScript($s_path."/js/plugins.js");
                break;
            default:
                echo "ERROR: Unknown module - ".$a_param;
        }
    }
    $APPLICATION->AddHeadScript($s_path."/css/main.css");
    $APPLICATION->AddHeadScript($s_path."/js/script.js");
    if ($s_plugins){
        echo '<meta name="plugins" content="'.$s_plugins.'">';
    }
}
function is_delayed($s_sced_date, $b_full = true) {
    $o_sced = new \DateTime($s_sced_date);
    $o_today = get_today(true, $b_full);
    if ($o_sced < $o_today) $b_delay = true;
    else $b_delay = false;
    return $b_delay;
}
function organise_files_array($a_stack){
    $a_files = array();
    foreach($a_stack as $k => $key){
        $i = 0;
        foreach($key as $val) {
            $a_files[$i][$k] = $val;
            $i++;
        }
    }
    return $a_files;
}
function price_format($price, $decimals = 0, $separator = ' ', $point = '.') {
    return number_format($price, $decimals, $point, $separator);
}
function qrplust($imgpath, $qrpath, $qrcode) {
    /* Create a blank image */
    $im  = imagecreatetruecolor(1053, 585);
    $bgc = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, 1053, 585, $bgc);
    /* load template */
    $source = imagecreatefrompng($imgpath);
    imagecopyresized($im, $source, 0, 0, 0, 0, 1053, 585, 1053, 585);
    /* load qr */
    $sourceqr = imagecreatefrompng($qrpath);
    imagecopyresized($im, $sourceqr,  67, 110, 0, 0, 330, 330, 330, 330);
    /* string */
    $textcolor = imagecolorallocate($im, 0, 0, 0);

    $textwidth = strlen($qrcode)*20;
    $leftmargin = round((300-$textwidth)/2);

    imagettftext($im, 24, 0, 75+$leftmargin, 510, $textcolor, $_SERVER["DOCUMENT_ROOT"].'/bitrix/php_interface/wrk/functions/include/fonts/open-sans.extrabold.ttf', $qrcode);
    return $im;
}
function random_string($length, $chartypes = array())
{
    $chartypes_array=explode(",", $chartypes);
    // задаем строки символов.
    //Здесь вы можете редактировать наборы символов при необходимости
    $lower = 'abcdefghijklmnopqrstuvwxyz'; // lowercase
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // uppercase
    $numbers = '1234567890'; // numbers
    $special = '^@*+-+%()!?'; //special characters
    $chars = "";
    // определяем на основе полученных параметров,
    //из чего будет сгенерирована наша строка.
    if (in_array('all', $chartypes_array)) {
        $chars = $upper;
    } else {
        if(in_array('upper', $chartypes_array))
            $chars = $upper;
        if(in_array('numbers', $chartypes_array))
            $chars .= $numbers;
        if(in_array('lower', $chartypes_array))
            $chars .= $lower;
        if(in_array('special', $chartypes_array))
            $chars .= $special;
    }
    // длина строки с символами
    $chars_length = strlen($chars) - 1;
    // создаем нашу строку,
    //извлекаем из строки $chars символ со случайным
    //номером от 0 до длины самой строки
    $string = $chars{rand(0, $chars_length)};
    // генерируем нашу строку
    for ($i = 1; $i < $length; $i = strlen($string)) {
        // выбираем случайный элемент из строки с допустимыми символами
        $random = $chars{rand(0, $chars_length)};
        // убеждаемся в том, что два символа не будут идти подряд
        if ($random != $string{$i - 1}) $string .= $random;
    }
    // возвращаем результат
    return $string;
}
function remdir_recurse($dir) {
    if ($objs = glob($dir."/*")) {
        foreach($objs as $obj) {
            is_dir($obj) ? removeDirectory($obj) : unlink($obj);
        }
    }
    rmdir($dir);
}
function search($array, $key, $value)
{
    $results = array();
    search_r($array, $key, $value, $results);
    return $results;
}
function search_r($array, $key, $value, &$results)
{
    if (!is_array($array)) {
        return;
    }

    if (isset($array[$key]) && $array[$key] == $value) {
        $results[] = $array;
    }

    foreach ($array as $subarray) {
        search_r($subarray, $key, $value, $results);
    }
}
function set_on_response_js($obj, $handler = 'onResponse'){
    echo "<script type=\"text/javascript\">window.parent.".$handler."('".$obj."');</script>";
}
<?php
/**
 * WebRolaKit functions compilation v.2.0
 *
 * @author Serge Rola <serge.rola@gmail.com> and others (provided if are known)
 */

/**
 * Initialises WRK classes
 *
 * @param string $s_class - an additional custom class name should be initialised as well
 */
function app_init($s_class = '')
{
    global $o_actions;
    require($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/wrk/classes/wrk.mailer.class.php");
    require($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/wrk/classes/wrk.main.class.php");
    require($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/wrk/classes/wrk.actions.class.php");
    if ($s_class) require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/wrk/classes/wrk." . $s_class . ".class.php");
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
function _back_in_array($array, $key, $value)
{
    $results = array();

    if (is_array($array)) {
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
 * @param string $sPhone - string of the phone
 * @return string|string[]|null
 */
function clear_phone_formatting($sPhone)
{
    return preg_replace("/[^\+\d]/", "", $sPhone);
}

/**
 * Downloads files via web interface
 *
 * @param string $url - source url
 * @param string $target - target location
 * @return bool - result either is success or fail
 */
function download_via_web($url, $target)
{
    if (!$hfile = fopen($target, "w")) return false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FILE, $hfile);

    if (!curl_exec($ch)) {
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
 * @param mixed $a - supposed to be an array to be displayed, but may be any kind of value
 * @param bool $die - this parameter determines if the code running should be stopped after the output
 */
function ea($a, $die = true)
{
    echo "<pre>";
    print_r($a);
    echo "</pre>";
    if ($die) die('.....end of debug.');
}

/**
 * Formats date to DB ready string
 *
 * @param string $s_in - date string of any supported format
 * @return string - formatted date
 */
function format_db_date($s_in)
{
    return date('Y-m-d', strtotime($s_in));
}

/**
 * Formats number to thousand separated
 *
 * @param int|string $i_num
 * @return string
 */
function format_num($i_num = 0)
{
    if (!is_numeric($i_num)) {
        return $i_num;
    } else {
        $i_nem_num = '';
        $a_num = array_reverse(str_split($i_num));
        foreach ($a_num as $i_key => $i_val) {
            $i_nem_num = $i_val . $i_nem_num;
            if (($i_val + 1) % 3 === 0) {
                $i_nem_num = ' ' . $i_nem_num;
            }
        }
        return $i_nem_num;
    }
}

/**
 * Formats number to the string of price
 *
 * @param int|string $s_in - raw number
 * @param string $s_postfix - currency postfix
 * @return string
 */
function format_price($s_in, $s_postfix = '.-')
{
    return (format_num(trim($s_in)) ?: '0') . $s_postfix;
}

/**
 * Extracts domain name from the link string
 *
 * @param string $s_link - url
 * @return string|null
 */
function get_clear_link($s_link)
{
    $s_link = preg_replace("#https?:\/\/#", "", $s_link);
    $a_link = explode("/", $s_link);
    $s_link = $a_link[0];
    return $s_link;
}

/**
 * Returns string of count bound with proper declension
 *
 * @param int $i_count
 * @param array $a_desc - should contain three cases of declensions, fourth is optional - is being used in zero cases
 * @param bool $b_return_array - determines whether to return string or descriptive array. string is by default
 * @return string|array|bool - returns false in case of feeding wrong parameters
 */
function get_declensions($i_count, $a_desc, $b_return_array = false)
{
    if ($i_count >= 0) {
        if ($i_count <= 10 || $i_count >= 20 || ($i_count >= 100 & substr(strval($i_count), -2))) {
            switch (substr($i_count, -1)) {
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
        } else {
            $s_desc = $a_desc[2];
        }
        if (!$i_count && !empty($a_desc[3])) $i_count = $a_desc[3];
    } else return false;
    if ($b_return_array) return array("count" => $i_count, "desc" => $s_desc);
    else return $i_count . " " . $s_desc;
}

/**
 * TODO under development
 * Opens image to download
 *
 * @param $url
 * @param $image_dir
 * @param $image_name
 */
function _get_images($url, $image_dir, $image_name)
{
    $image_name++;
    $savefile = $image_dir . "/" . $image_name . ".jpg";

    $ch = curl_init($url);
    $fp = fopen($savefile, "wb");
    if (!$fp) {
        fclose($savefile);
//        write_log('Не удалось открыть файл для сохранения изображения ' . $url);
    }
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    header("Content-type: application/x-download");
    header("Content-Disposition: attachment; filename=$image_name.jpg");
    readfile($savefile);
}

/**
 * Provides an array of months in two languages
 *
 * @return array
 */
function get_months_array()
{
    return array(
        array(
            "ID" => "1",
            "NAME" => "Январь",
            "GEN" => "Января",
            "NAME_EN" => "January",
        ),
        array(
            "ID" => "2",
            "NAME" => "Февраль",
            "GEN" => "Февраля",
            "NAME_EN" => "February",
        ),
        array(
            "ID" => "3",
            "NAME" => "Март",
            "GEN" => "Марта",
            "NAME_EN" => "March",
        ),
        array(
            "ID" => "4",
            "NAME" => "Апрель",
            "GEN" => "Апреля",
            "NAME_EN" => "April",
        ),
        array(
            "ID" => "5",
            "NAME" => "Май",
            "GEN" => "Мая",
            "NAME_EN" => "May",
        ),
        array(
            "ID" => "6",
            "NAME" => "Июнь",
            "GEN" => "Июня",
            "NAME_EN" => "June",
        ),
        array(
            "ID" => "7",
            "NAME" => "Июль",
            "GEN" => "Июля",
            "NAME_EN" => "July",
        ),
        array(
            "ID" => "8",
            "NAME" => "Август",
            "GEN" => "Августа",
            "NAME_EN" => "August",
        ),
        array(
            "ID" => "9",
            "NAME" => "Сентябрь",
            "GEN" => "Сентября",
            "NAME_EN" => "September",
        ),
        array(
            "ID" => "10",
            "NAME" => "Октябрь",
            "GEN" => "Октября",
            "NAME_EN" => "October",
        ),
        array(
            "ID" => "11",
            "NAME" => "Ноябрь",
            "GEN" => "Ноября",
            "NAME_EN" => "November",
        ),
        array(
            "ID" => "12",
            "NAME" => "Декабрь",
            "GEN" => "Декабря",
            "NAME_EN" => "December",
        ),
    );
}

/**
 * Returns string containing random chars
 *
 * @param int $length - the length of expected string
 * @return string - generated string
 */
function get_random_string($length)
{
    if (!is_numeric($length)) return '';
    $chars = "0123456789abcdefjhijklmnopqrstuvwxyzABCDEFJHIGKLMNOPQRSTUVWXYZ-_";
    $res = '';
    for ($i = 0; $i < $length; $i++) {
        $res .= $chars[rand(0, strlen($chars))];
    }
    return $res;
}

/**
 * Returns either an object or a string of the current date
 *
 * @param bool $b_object - determines whether to reurn an object or a string
 * @param bool $b_full - determines whthere to consider the current time as well
 * @return DateTime|string|false - returns false if case of wrong parameters have been fed in
 * @throws Exception in case of wrong parameters provided during the Date object construction
 */
function get_today($b_object = true, $b_full = true)
{
    $s_contsruct = ($b_full) ? "d.m.Y H:i:s" : "d.m.Y";
    try {
        return ($b_object) ? new \DateTime(date($s_contsruct)) : date($s_contsruct);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generates an array of years
 *
 * @param bool $b_reverse - determines whether to start from the earlier or the later year
 * @param int|string|null $i_init - an initial year. must be less than the current one
 *
 * @return array
 */
function get_years($b_reverse = false, $i_init = null)
{
    $m_current = +date('Y');
    if (!is_numeric($i_init) || $i_init >= $m_current) {
        $i_init = $m_current - 30;
    }
    $a_years = array();
    for ($i = $i_init; $i <= $m_current; $i++) {
        $a_years[] = $i;
    }
    return $b_reverse ? array_reverse($a_years) : $a_years;
}

/**
 * Recursively checks whether the haystack array contains a needle item
 *
 * @param mixed $needle - item to search
 * @param array $haystack - an array
 * @param bool $strict - strict comparison mode switcher. true by default (compares strictly)
 * @return bool
 */
function in_array_r($needle, $haystack, $strict = true)
{
    if (!is_array($haystack)) return false;
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }
    return false;
}

/**
 * Checks whether the scheduled date is overdue
 *
 * @param string $s_sced_date - scheduled date string
 * @param bool $b_full - determines whether the full date has been fed in as a parameter
 * @return bool - returns false in case of throwing exception
 */
function is_overdue($s_sced_date, $b_full = true)
{
    try {
        $o_sced = new \DateTime($s_sced_date);
        $o_today = get_today(true, $b_full);
        return $o_sced < $o_today;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * organises $_FILES array according to each file instead of keys
 *
 * @param array $a_stack - a subarray of $_FILES containing files information
 * @return array - organised array
 */
function organise_files_array($a_stack)
{
    $a_files = array();
    foreach ($a_stack as $k => $key) {
        $i = 0;
        foreach ($key as $val) {
            $a_files[$i][$k] = $val;
            $i++;
        }
    }
    return $a_files;
}

/**
 * A wrapping for number_format is used for price formatting
 *
 * @see number_format()
 *
 * @param int|string $price - a number to format
 * @param bool $decimals - decimals switcher: true - show (2), false - hide
 * @param string $separator - thousands separator (a space char by default)
 * @param string $point - decimals point (a point char by default)
 * @return string - formatted price string
 */
function price_format($price, $decimals = false, $separator = ' ', $point = '.')
{
    return number_format($price, $decimals ? 2 : 0, $point, $separator);
}

/**
 * Recursively removes directory and contained files unconditionally
 *
 * @param string $dir - absolute path to the directory to remove
 * @return bool - the result of operation
 */
function remdir_recurse($dir)
{
    if ($objs = glob($dir . "/*")) {
        foreach ($objs as $obj) {
            is_dir($obj) ? remdir_recurse($obj) : unlink($obj);
        }
    }
    return rmdir($dir);
}

/**
 * Calls global javascript handler from iframe
 *
 * @param string $obj - javascript object to path. should be JSON valid
 *
 * @see json_encode()
 *
 * @param string $handler - valid name of the existing javascript handler
 */
function set_on_response_js($obj, $handler = 'onResponse')
{
    echo "<script type=\"text/javascript\">window.parent." . $handler . "('" . $obj . "');</script>";
}
<?php
$a_files = organise_files_array($_FILES['files']);

$new_dir = md5($_REQUEST["user"].time())."/";
$path = "/upload/tmp_files/";
if (!is_dir($path)) mkdir($path);
$dir = $_SERVER["DOCUMENT_ROOT"].$path;
$dir = (mkdir($dir.$new_dir))? $dir.$new_dir : $dir;

$a_js_obj = array();
foreach ($a_files as $a_file) {
    $name = basename($a_file['name']);
    $file = $dir . $name;
    $filepath = $path.$new_dir.$name;
    $s_type = explode(".", $name);
    $s_type = (count($s_type) > 1)? $s_type[count($s_type)-1] : $s_type[0];
    if ($s_type) $b_allow = preg_match("/^(!!!/* TODO ATTENTION!!! PUT HERE ALLOWED FILE EXTENSIONS!!!*/!!!)$/si", $s_type);
    else $b_allow = false;
    if ($b_allow || $b_allow == 0) $success = move_uploaded_file($a_file['tmp_name'], $file);
    else $success = false;
    $a_js_obj[] = '{filename:"' . $name . '", success:"' . $success . '", path: "'.$filepath.'", code: "'.md5($name).'", dir: "'.substr($dir, 0, strlen($dir)- 1).'", count:"'.count($a_files).'"}';
}

if (count(scandir($dir)) < 3) rmdir($dir);
set_on_response_js("[".implode(", ", $a_js_obj)."]");
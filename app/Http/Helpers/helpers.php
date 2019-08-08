<?php
/**
 * Created by PhpStorm.
 * User: mentalhealthai
 * Date: 06/09/2017
 * Time: 17:54
 */


/**
 * Replace "_" with " "
 *
 * @return string
 */
function str_spacecase($string)
{
    return str_replace("_", " ", $string);
}

/**
 * Check is null or empty string
 *
 * @return string
 */
function IsNullOrEmptyString($string)
{
    return (!isset($string) || trim($string) === '');
}


/**
 * Find index of key
 *
 * @return string
 */
function findIndexOfKey($key_to_index, $array)
{
    return array_search($key_to_index, array_keys($array));
}

/**
 * Check if file is a csv
 *
 * @return boolean
 */
function is_csv($fileName)
{
    $arrayName = explode('.', $fileName);
    $extension = end($arrayName);
    return $extension == 'csv' ? true : false;
}

function checkUploadFileName($fileName)
{
    $splitedFileName = preg_split("/[._]/", $fileName);
    if (count($splitedFileName) < 3) {
        return false;
    }
    if (strlen($splitedFileName[1]) < 6) {
        return false;
    }
    if (!is_numeric(substr($splitedFileName[1], 0, 6))){
        return false;
    }
    if (end($splitedFileName) != 'csv') {
        return false;
    }
    return true;
}



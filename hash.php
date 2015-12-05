<?php
/**
 * Created by PhpStorm.
 * User: Youi
 * Date: 2015-12-05
 * Time: 12:13
 */

main();

function main() {
    !isset($_GET['url']) && exit_script('Hello Tumblr!');
    isImagesUrl($_GET['url']) && redirect_location($_GET['url']) && exit_script();

    $hosts_number = 4;
    $hash_no      = str_hash($_GET['url'], $hosts_number);
    $redirect_url = "http://tumblr-images-$hash_no.appspot.com/fetch.php?url={$_GET['']}";

    redirect_location($redirect_url);
}

function redirect_location($redirect_url) {
    header('Location: ' . $redirect_url, true, 301);

    return true;
}

function str_hash($str, $range = 100) {
    $number = crc32($str);

    return abs($number % $range) + 1;
}

function exit_script($message = null) {
    exit($message);
}
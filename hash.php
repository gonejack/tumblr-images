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
    isImageUrl($_GET['url']) && redirect_location($_GET['url']) && exit_script();

    $hosts_number = 4;
    $hash_no      = str_hash($_GET['url'], $hosts_number);
    $redirect_url = "http://tumblr-images-$hash_no.appspot.com/fetch.php?url={$_GET['url']}";
    $redirect_url = encode_cjk_url($redirect_url);

    redirect_location($redirect_url);
}

function encode_cjk_url($raw_url) {

    $url = $raw_url;
    if (preg_match('<(http.+?tumblr\.com)(.+$)>i', $raw_url, $matches)) {
        $path_parts = array_map('urlencode', explode('/', $matches[2]));
        $url        = $matches[1] . implode('/', $path_parts);
    }

    return $url;
}

function isImageUrl($url) {
    $pattern = "<https?://\d+\.media\.tumblr\.com/(\w+/)?tumblr_\w+_(1280|540|500|400|250)\.(png|jpg|gif)>";

    return !!preg_match($pattern, $url);
}

function redirect_location($redirect_url) {
    header("Location: $redirect_url", true, 301);

    return true;
}

function str_hash($str, $range = 100) {
    $number = crc32($str);

    return abs($number % $range) + 1;
}

function exit_script($message = null) {
    exit($message);
}
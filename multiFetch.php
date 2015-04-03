<?php

if (empty($_GET['url'])) {
    exit('Hello World! fetch more images');
}

$htmlSource     = getContent($_GET['url']);
$imageSources   = getSrcArray($htmlSource);
$numberOfImages = count($imageSources);

if (!$htmlSource || $numberOfImages === 0) {

    echo 'seems something wrong';

} else {

    if ($numberOfImages === 1) {
        header('Location: ' . $imageSources[0], true, 301);
        exit;
    } else {
        $zipString = makeZipString($imageSources);

        header('Content-Type: application/zip');
        header('Content-Length: ' . strlen($zipString));
        header('Content-Disposition: attachment; filename=' . date('Y/M/j/D G:i:s') . '.zip');

        echo $zipString;
    }

}

/**
 * Parse URL
 * tumblr has two type of url
 * first type like http://blabla/digits
 * second type like http://blabla/digits/blabla
 * when you can't load content from second type, try first type
 *
 * @param $origin string Original URL(second type)
 * @return mixed
 */
function parseUrl($origin) {
    preg_match('<http.+/post/\d+>', $origin, $new);

    return $new[0];
}

/**
 * Get HTML content
 * failed first time would try to convert URL to fetch content
 * @param $url
 * @return string|false
 */
function getContent($url) {
    $content = @file_get_contents($url);
    if (strlen($content) < 100) {
        $content = @file_get_contents(parseUrl($url));
    }

    return $content;
}

/**
 * Get all URL of largest images
 * @param $content String
 * @return array
 */
function getSrcArray($content) {

    $patten = "<(?:content|src)=\"((?:https?://\d+\.media\.tumblr\.com)/(?:(\w+)/)?(?:tumblr_\w+_(1280|540|500|400|250)\.(?:png|jpg|gif)))\">i";

    $return = array();

    if (preg_match_all($patten, $content, $matches)) {

        list(, $srcList, $hashList, $sizeList) = $matches;

        $temp = array();
        foreach ($srcList as $index => $src) {
            $hash = $hashList[$index];
            $size = $sizeList[$index];

            if (empty($temp[$hash]) || $size > $temp[$hash]['size']) {
                $temp[$hash] = array('src' => $src, 'size' => $size);
            }
        }

        foreach ($temp as $item) {
            $return[] = $item['src'];
        }
    }

    return $return;
}

/**
 * Download images, pack into a zip, return as zip string.
 * @param $imageSources
 * @return string
 */
function makeZipString($imageSources) {
    require_once('zip.lib.php');

    $zip = new ZipFile();
    foreach ($imageSources as $source) {
        $zip->addFile(file_get_contents($source), basename($source));
    }

    return $zip->file();
}
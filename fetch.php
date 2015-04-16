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
    }

    $imageZipAndValidSrc = getImageZipAndValidSrc($imageSources);

    if (count($imageZipAndValidSrc['validSrc']) === 1) {
        header('Location: ' . $imageZipAndValidSrc['validSrc'][0], true, 301);
        exit;
    }

    header('Content-Type: application/zip');
    header('Content-Length: ' . strlen($imageZipAndValidSrc['zipFile']));
    header('Content-Disposition: attachment; filename=' . date('Y/M/j/D G:i:s') . '.zip');

    echo $imageZipAndValidSrc['zipFile'];

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
 * Parse a set of HTTP headers
 *
 * @param array The php headers to be parsed
 * @param [string] The name of the header to be retrieved
 * @return A header value if a header is passed;
 *         An array with all the headers otherwise
 */
function parseHeaders(array $headers, $header = null) {
    $output = array();

    if ('HTTP' === substr($headers[0], 0, 4)) {
        list(, $output['status'], $output['status_text']) = explode(' ', $headers[0]);
        unset($headers[0]);
    }

    foreach ($headers as $v) {
        $h                         = preg_split('/:\s*/', $v);
        $output[strtolower($h[0])] = $h[1];
    }

    if (null !== $header) {
        if (isset($output[strtolower($header)])) {
            return $output[strtolower($header)];
        }

        return;
    }

    return $output;
}

/**
 * Download images, pack into a zip, return as zip string.
 * @param Array $imageSources
 * @return Array
 */
function getImageZipAndValidSrc($imageSources) {
    require_once('zip.lib.php');

    $zip      = new ZipFile();
    $validSrc = array();
    foreach ($imageSources as $source) {

        $image = file_get_contents($source);

        if (in_array(parseHeaders($http_response_header, 'status'), array(200, 301, 304))) {

            $zip->addFile($image, basename($source));
            $validSrc[] = $source;

        }

    }

    return array('zipFile' => $zip->file(), 'validSrc' => $validSrc);
}
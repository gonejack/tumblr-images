<?php

main();

function main() {
    (!isset($_GET['url']) || !preg_match('#^http.?://.+\.tumblr\.com.*#i', $_GET['url'])) && exit('Hello World!');

    $strPageSource = getPageSource(encode_cjk_url($_GET['url']));   #get HTML page source code
    !$strPageSource && echoImageNotFoundTextFileAndExit($_GET['url']);

    $arrImagesUrls = parseImagesUrls($strPageSource);   #parse urls of images

    $intCountOfImagesUrls = count($arrImagesUrls);
    $intCountOfImagesUrls === 0 && echoImageNotFoundTextFileAndExit($_GET['url']);  #no image url found, echo error message as txt file.
    $intCountOfImagesUrls === 1 && redirectAndExit(array_pop($arrImagesUrls));  #we got just one image url to be fetch, so no need for fetching, just redirect the browser to it.


    $arrContentAndUrlOfValidImages = fetchImages($arrImagesUrls); #not every url is available, so try every one.

    $intCountOfValidImagesUrls = count($arrContentAndUrlOfValidImages['validImagesUrls']);    #check out the number of available urls
    $intCountOfValidImagesUrls === 0 && echoImageNotFoundTextFileAndExit($_GET['url']);
    $intCountOfValidImagesUrls === 1 && redirectAndExit(array_pop($arrContentAndUrlOfValidImages['validImagesUrls']));    #if we got just one available url, no need to pack the image cause we could just redirect the browser.

    //when we got multiple images to deal with
    $strZipString = makeZipPack($arrContentAndUrlOfValidImages['imageStrings'], $arrContentAndUrlOfValidImages['validImagesUrls']);
    outputZipPackAsFileDownload($strZipString);
}

function encode_cjk_url($raw_url) {

    $url = $raw_url;
    if (preg_match('#(http.+?tumblr\.com)(.+$)#i', $raw_url, $matches)) {
        $path_parts = array_map('urlencode', explode('/', $matches[2]));
        $url        = $matches[1] . implode('/', $path_parts);
    }

    return $url;
}

/**
 * get HTML page source
 * @param $strUrl
 * @return bool|string
 */
function getPageSource($strUrl) {

    $strPageSource = @file_get_contents($strUrl);

    //Tumblr has two URL types, try the short one when the long one failed to be access.
    if (strlen($strPageSource) < 100) {

        $strShortUrl = '';

        preg_match('<http.+/post/\d+>', $strUrl, $arrMatch) && $strShortUrl = $arrMatch[0];

        $strShortUrl && $strPageSource = @file_get_contents($strShortUrl);

        //check one more time
        strlen($strPageSource) < 100 && $strPageSource = false;
    }

    return $strPageSource;
}

/**
 * regular expression fetching operation for images urls on HTML page source
 * @param $strPageSource
 * @return array
 */
function parseImagesUrls($strPageSource) {

    $arrReturnUrls = array();

    $strRegPatten = "<(?:content|src)=\"((?:https?://\d+\.media\.tumblr\.com)/(?:(\w+)/)?(?:tumblr_\w+_(1280|540|500|400|250)\.(?:png|jpg|gif)))\">i";

    if (preg_match_all($strRegPatten, $strPageSource, $arrMatches)) {

        $arrTemp = array(); #array( hashValue => array('url' => url, 'size' => size), hashValue => array('url' => url, 'size' => size),...)

        list(, $arrUrls, $arrHashes, $arrSizes) = $arrMatches;

        //filter, find out the url which represent the max size of the image.
        for ($i = 0, $length = sizeof($arrUrls); $i < $length; $i++) {

            $strUrl    = $arrUrls[$i];
            $strHashes = $arrHashes[$i];
            $strSize   = $arrSizes[$i];

            if (empty($arrTemp[$strHashes]) || $arrTemp[$strHashes]['size'] < $strSize) {
                $arrTemp[$strHashes] = array('url' => $strUrl, 'size' => $strSize);
            }
        }

        foreach ($arrTemp as $arrItem) {
            $arrReturnUrls[] = $arrItem['url'];
        }
    }

    return $arrReturnUrls;
}

/**
 * get images raw strings
 * @param $arrImagesUrls
 * @return array
 */
function fetchImages($arrImagesUrls) {

    $arrReturn = array('imageStrings' => array(), 'validImagesUrls' => array());

    $arrValidStatus = array(200, 301, 304);

    foreach ($arrImagesUrls as $strImageUrl) {

        $strImageString = @file_get_contents($strImageUrl);
        if ($strImageString === false) {
            continue;
        }

        $intHttpStatus = parseHeaders($http_response_header, 'status');

        $boolFetchSuccess = in_array($intHttpStatus, $arrValidStatus);

        if ($boolFetchSuccess) {
            $arrReturn['imageStrings'][]    = $strImageString;
            $arrReturn['validImagesUrls'][] = $strImageUrl;
        }

    }

    return $arrReturn;
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
 * redirect the browser to the direct image url
 * @param $strImageUrl
 */
function redirectAndExit($strImageUrl) {
    header('Location: ' . $strImageUrl, true, 301);
    exit;
}

/**
 * make a txt file including error message
 * @param $strUrl
 */
function echoImageNotFoundTextFileAndExit($strUrl) {

    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename=' . date('Y/M/j/D G:i:s') . '.htm');

    echo "No tumblr images found at <a href='$strUrl' target='_self'><i>$strUrl</i></a>";

    exit;
}

/**
 * generate zip file stream
 * @param $arrImageStrings
 * @param $arrImageUrls
 * @return string
 */
function makeZipPack($arrImageStrings, $arrImageUrls) {
    require_once('zip.lib.php');
    $zipGenerator = new ZipFile();

    for ($i = 0, $length = sizeof($arrImageStrings); $i < $length; $i++) {

        $strImageString = $arrImageStrings[$i];
        $strImageUrl    = $arrImageUrls[$i];

        $zipGenerator->addFile($strImageString, basename($strImageUrl));

    }

    return $zipGenerator->file();
}

/**
 * make some headers for zip file as attachment download
 * @param $strZipString
 */
function outputZipPackAsFileDownload($strZipString) {

    header('Content-Type: application/zip');
    header('Content-Length: ' . strlen($strZipString));
    header('Content-Disposition: attachment; filename=' . date('Y/M/j/D G:i:s') . '.zip');

    echo $strZipString;
}
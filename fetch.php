<?php
/**
 * Created by PhpStorm.
 * User: Youi
 * Date: 2015-12-03
 * Time: 22:45
 */

main();

function main() {
    !isset($_GET['url']) && exit_script('Hello Tumblr!');

    $query_param = get_query_param($_GET['url']);
    !$query_param && echoTxtFile("NOT VALID TUMBLR URL: [{$_GET['url']}]") && exit_script();

    $post_info = query_tumblr_api($query_param);
    !$post_info && echoTxtFile("NO POST INFO FETCHED FROM TUMBLR WITH GIVEN URL: [{$_GET['url']}], THE POST MIGHT BE DELETED") && exit_script();

    $post_info = $post_info['posts'][0];

    switch ($post_info['type']) {
        case 'answer':
            $question = htmlCharsDecode($post_info['question']);
            $answer   = htmlCharsDecode($post_info['answer']);
            $tags     = implode(', ', $post_info['tags']);
            $output   = "[Q&A]\r\n\r\n$question\r\n\r\n$answer\r\n\r\nTags: $tags\r\n";
            echoTxtFile($output);
            exit_script();
            break;
        case 'video':
            $url = get_video_url($post_info);
            redirect_location($url) && exit_script();
            break;
        case 'photo':
        default:
            $urls  = get_photo_urls($post_info);
            $count = count($urls);
            if ($count === 1) {
                redirect_location($urls[0]);
                exit_script();
            } else {
                $image_pack = fetch_images($urls);
                $zip_str    = makeZip($image_pack);
                echoZipFile($zip_str);
                exit_script();
            }
            break;
    }
}

/**
 * @param $url
 * @return array|bool
 */
function get_query_param($url) {
    if (preg_match('<https?://(.+)/post/(\d+)>', $url, $match)) {
        return array(
            'post_domain' => $match[1],
            'post_id'     => $match[2]
        );
    } else {
        return false;
    }
}

/**
 * @param $query_param
 * @return bool|mixed
 */
function query_tumblr_api($query_param) {
    $api_url = "http://{$query_param['post_domain']}/api/read/json?id={$query_param['post_id']}";

    $i = 0;
    do {
        $json_str    = file_get_contents($api_url);
        $status_code = (int)parseHeaders($http_response_header, 'status');
    } while (strlen($json_str) < 10 && $i++ < 3 && $status_code !== 404);

    if (preg_match('<\{.+\}>', $json_str, $match)) {
        return json_decode($match[0], true);
    } else {
        return false;
    }
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

        return null;
    }

    return $output;
}

/**
 * @param $post_info
 * @return array
 */
function get_photo_urls($post_info) {
    $urls = array();

    if ($post_info['photos']) {
        foreach ($post_info['photos'] as $item) {
            $urls[] = $item['photo-url-1280'];
        }
    } else {
        $urls[] = $post_info['photo-url-1280'];
    }

    return $urls;
}

function get_video_url($post_info) {
    $video_source = $post_info['video-source'];
    if ($video_info = unserialize($video_source)) {
        $video_info = $video_info['o1'];
        $video_id   = substr($video_info['video_preview_filename_prefix'], 0, -1);

        return "http://vt.tumblr.com/$video_id.mp4";
    }

    if (preg_match('<src="(.+?)">', $video_source, $match)) {
        return $match[1];
    }

    return false;
}

function redirect_location($redirect_url) {
    header('Location: ' . $redirect_url, true, 301);

    return true;
}

function htmlCharsDecode($str) {
    $convertMap = array(0x0, 0x2FFFF, 0, 0xFFFF);

    return mb_decode_numericentity(html_entity_decode($str), $convertMap, 'UTF-8');
}

/**
 * get images raw strings
 * @param $urls
 * @return array
 */
function fetch_images($urls) {

    $images_pack = array('images' => array(), 'fileNames' => array(), 'count' => 0);

    $valid_status = array(200, 301, 304);

    foreach ($urls as $url) {

        $image_str = @file_get_contents($url);
        if ($image_str === false) {
            continue;
        }

        $status = parseHeaders($http_response_header, 'status');

        $fetched = in_array($status, $valid_status);
        if ($fetched) {
            $images_pack['images'][]    = $image_str;
            $images_pack['fileNames'][] = basename($url);
            $images_pack['count']++;
        }

    }

    return $images_pack;
}

function makeZip($images_pack) {
    require_once('zip.lib.php');
    $zipGenerator = new ZipFile();

    for ($i = 0; $i < $images_pack['count']; $i++) {
        $image_str = $images_pack['images'][$i];
        $filename  = $images_pack['fileNames'][$i];

        $zipGenerator->addFile($image_str, $filename);
    }

    return $zipGenerator->file();
}

function echoZipFile($zip_str) {
    header('Content-Type: application/zip');
    header('Content-Length: ' . strlen($zip_str));
    header('Content-Disposition: attachment; filename=' . date('Y/M/j/D G:i:s') . '.zip');

    echo $zip_str;

    return true;
}

function echoTxtFile($content) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename=' . date('Y/M/j/D G:i:s') . '.txt');

    echo $content;

    return true;
}

function exit_script($message = null) {
    exit($message);
}
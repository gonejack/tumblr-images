<?php
/**
 * Created by PhpStorm.
 * User: Youi
 * Date: 2015-12-03
 * Time: 22:45
 */

main();

function main() {
    !isset($_GET['url']) && exit_script('Hello tumblr!');

    $query_param = get_query_param($_GET['url']);
    !$query_param && exit('Not a valid tumblr URL');

    $post_info = query_tumblr_api($query_param);
    !$post_info && exit('No post info fetched from tumblr');

    $post_info = $post_info['posts'][0];

    switch ($post_info['type']) {
        case 'video':
            $url = get_video_url($post_info);
            redirect_location($url) && exit_script();
            break;

        case 'photo':
        default:
            $urls  = get_photo_urls($post_info);
            $count = count($urls);
            $count === 1 && redirect_location($urls[0]) && exit_script();
            $count > 1 && echoTxtFile(implode("\r\n", $urls)) && exit_script();
            break;

    }
}

/**
 * @param $url
 * @return array|bool
 */
function get_query_param($url) {
    if (preg_match('<http\w?://(.+\.tumblr\.com)/post/(\d+)>', $url, $match)) {
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
    $video_info = unserialize($post_info['video-source'])['o1'];
    $video_id   = substr($video_info['video_preview_filename_prefix'], 0, -1);

    return "http://vt.tumblr.com/$video_id.mp4";
}

function redirect_location($redirect_url) {
    header('Location: ' . $redirect_url, true, 301);

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
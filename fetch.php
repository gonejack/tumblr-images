<?php

if (empty($_GET['url'])) {
	exit('Hello World!');
}

$blog    = isset($_GET['blog']) ? $_GET['blog'] : 'unknow';
$content = getContent($_GET['url']);
$src     = getSrc($content);

if ($content && $src) {
	header('Location: ' . $src, true, 301);
	//logInfo($src, $blog);
	if (isset($_GET['forward'])) {
		file_get_contents("{$_GET['forward']}?{$_SERVER['QUERY_STRING']}");
	}
} else {
	//logError($src, $content, $_GET['url'], parseUrl($_GET['url']));
	echo 'seems something wrong';
}


/**
 * Parse URL
 * tumblr has two type of url
 * first type like http://blabla/digits
 * second type like http://blabla/digits/blabla
 * when you can't load content from second type, try first type
 *
 * @param $origin Original URL(second type)
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
 * Fetch Image file Source from HTML content using Regular Expression
 * @param $content HTML file content
 * @return bool|string
 */
function getSrc($content) {
	$specs = array(1280, 500, 400, 250);
	
	foreach ($specs as $spec) {
		$pattern = "<(?:content|src)=\"((?:https?://\d+\.media\.tumblr\.com)/(?:\w+/)?(?:tumblr_\w+_$spec\.(?:png|jpg|gif)))\">i";
		if (preg_match($pattern, $content, $match)) return $match[1];
	}

	foreach ($specs as $spec) {
		$pattern = "<(?:content|src)=\"((?:https?://\d+\.media\.tumblr\.com)/(?:\w+/)?(?:\w+_$spec\.(?:png|jpg|gif)))\">i";
		if (preg_match($pattern, $content, $match)) return $match[1];
	}
	
	return false;
}

//log
function logInfo($src, $blog) {
	$time     = date('Y-m-d H:i:s');
	$imageTag = <<<END
<div>
	<img src="$src">
	<p>
		Time $time<br>
		Source $src<br>
		Blog $blog
	</p>
</div>
END;

	file_put_contents('images.html', $imageTag, FILE_APPEND);

	$filename = basename($src);
	if (!file_exists($filename)) {
		file_put_contents($filename, fopen($src, 'r'));
	}
}

//error log
function logError($src, $content, $originUrl, $parsedUrl) {
	$time    = date('Y-m-d H:i:s');
	$src     = ($src ? $src : 'False');
	$content = strlen($content);
	$error   = <<<END
<p>
	Time $time<br>
	Source $src<br>
	Content $content<br>
	ParedURL $parsedUrl
</p>
END;

	file_put_contents('errors.html', $error, FILE_APPEND);
}

<?php
    if (empty($_GET['url'])) {
        echo 'Hello World!';
        exit();
    }

    $blog = isset($_GET['blog']) ? $_GET['blog'] : 'unknow';
    $content = getContent($_GET['url']);
	$src = getSrc($content);

    if ($content && $src) {
        header('Location: '. $src, true, 301);
        //logInfo($src, $blog);
    } else {
    	//logError($src, $content, $_GET['url'], parseUrl($_GET['url']));
    	echo 'seems something wrong';
    }

    if (isset($_GET['forward'])) {
    	file_get_contents($_GET['forward'].'?'.$_SERVER['QUERY_STRING']);
    }

	//parse URL tumblr has two type of url, first type like http://blabla/digits, another type like http://blabla/digits/blabla
    function parseUrl($origin) {
    	preg_match('<http.+/post/\d+>', $origin, $new);
    	return $new[0];
    }

    //get html content
    function getContent($url) {
    	$content = @file_get_contents($url);
    	if (strlen($content) < 100) {
    		$content = @file_get_contents(parseUrl($url));
    	}

    	return $content;
    }

    //regexp to match image url from html content
    function getSrc($content) {
		$specs = array('1280', '500', '400', '250');
		foreach ($specs as $item) {
			$pattern = '<(?:content|src)="((?:https?://\d+\.media\.tumblr\.com)/(?:\w+/)?(?:tumblr_\w+_'.$item.'\.(?:png|jpg|gif)))">i';
			preg_match($pattern, $content, $match);
			if ($match) {
				return $match[1];
			}
		}

		return false;
    }

    //log
    function logInfo($src, $blog) {
    	$imageTag =
	    			'<div>
	    				<img src="'.$src.'">'
	    			.'<p>'
	    				.'Time '.date('Y-m-d H:i:s').'<br>'
	    				."Source $src<br>"
	    				."Blog $blog"
	    			.'</p>'
	    			.'</div>';

    	file_put_contents('images.html', $imageTag, FILE_APPEND);

    	$filename = basename($src);
    	if (!file_exists($filename)) {
    		file_put_contents($filename, fopen($src, 'r'));
    	}
    }

    //error log
	function logError($src, $content, $originUrl, $parsedUrl) {
		$error = '<p>'
				.'Time '.date('Y-m-d H:i:s').'<br>'
				.'Source '.($src ? $src : 'False').'<br>'
				.'Content '.($content ? strlen($content) : 'False').'<br>'
				.'OriginalURL '.$originUrl.'<br>'
				."ParsedURL $parsedUrl"
				.'</p>';

		file_put_contents('errors.html', $error, FILE_APPEND);
	}

<?php
    if (!isset($_GET['url'])) {
        echo 'hello';
        exit();
    }

    $blog = $_GET['blog'];
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


    function parseUrl($origin) {
    	preg_match('@http.+/post/\d+@', $origin, $temp);
    	return $temp[0];
    }
    
    function getContent($url) {
    	$content = file_get_contents($url);
    	if (!$content || strlen($content) < 100) {
    		$content = file_get_contents(parseUrl($url));
    	}
    	
    	return $content;
    }
    
    function getSrc($content) {
    	$pattern = '@(?:(?:content=")|(?:src="))(https?://[\S]+?media\.tumblr\.com[\S]+?_)((?:1280)|(?:500)|(?:400)|(?:250))(\.(?:png)?(?:jpg)?(?:gif)?)"@';
    	preg_match($pattern, $content, $matches);

    	return preg_replace($pattern, '${1}${2}${3}', $matches[0]);
    }
    
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

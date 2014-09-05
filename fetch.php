<?php
        $pattern = '@(?:(?:content=")|(?:src="))(https?://[\S]+?media\.tumblr\.com[\S]+?_)((?:1280)|(?:500)|(?:400)|(?:250))(\.(?:png)?(?:jpg)?)"@';

        $content = file_get_contents($_GET['url']);

        if ($content) {
        	
	       preg_match($pattern, $content, $matches);

                $src = $matches[0];
                
                $img1280 = preg_replace($pattern, '${1}1280${3}', $src);
                $img500 = preg_replace($pattern, '${1}500${3}', $src);

                $src = preg_replace($pattern, '${1}${2}${3}', $src);

                header('location: ' . $src);
        }
<?php
	$url = $_GET['url'];
	include_once('simple_html_dom.php');
	$html = new simple_html_dom($url);

	$images = $html->find('img');
	foreach ($images as $img) {
		if (preg_match("/(.+media\.tumblr\.com.+)((1280)|(500)|(250))(\.(png)?(jpg)?)$/", $img->src)) {

			$src = $img->src;

			$img1280 = preg_replace('/(.+media\.tumblr\.com.+)((1280)|(500))(\.(png)?(jpg)?)$/', '${1}1280$5', $src);
			$img500 = preg_replace('/(.+media\.tumblr\.com.+)((1280)|(500))(\.(png)?(jpg)?)$/', '${1}500$5', $src);

			if (fopen($img1280, 'r')) {
				$src = $img1280;
			} else if (fopen($img500, 'r')){
				$src = $img500;
			}

			header('location: ' . $src);
			break;
		}
	}
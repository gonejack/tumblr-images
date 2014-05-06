<?php
	include_once('simple_html_dom.php');
	$pattern = "/(.+media\.tumblr\.com.+)((1280)|(500)|(250))(\.(png)?(jpg)?)$/";

	$url = $_GET['url'];
	$html = new simple_html_dom($url);
	$images = $html->find('img');

	foreach ($images as $img) {
		if (preg_match($pattern, $img->src)) {

			$src = $img->src;

			$img1280 = preg_replace($pattern, '${1}1280$6', $src);
			$img500 = preg_replace($pattern, '${1}500$6', $src);

			if (fopen($img1280, 'r')) {
				$src = $img1280;
			} else if (fopen($img500, 'r')){
				$src = $img500;
			}

			header('location: ' . $src);
			break;
		}

	}
<?php
	$url = $_GET['url'];
	include_once('simple_html_dom.php');
	$html = new simple_html_dom($url);

	$images = $html->find('img');
	foreach ($images as $img) {
		if (preg_match("/media\.tumblr\.com.+((1280)|(500))\.(png)?(jpg)?$/", $img->src)) {
			header('location: ' . $img->src);
			break;
		}
	}
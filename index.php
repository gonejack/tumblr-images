<?php
	$url = $_GET['url'];
	include_once('simple_html_dom.php');
	$html = new simple_html_dom($url);
	$img = $html->find('ol#posts img', 0);
	$src = $img->src;
	header('location: ' . $src);
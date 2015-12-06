<?php
/**
 * Created by PhpStorm.
 * User: Youi
 * Date: 2015-12-05
 * Time: 02:47
 */

// buffer all upcoming output
ob_start();
echo "Here's my awesome web page";

// get the size of the output
$size = ob_get_length();

// send headers to tell the browser to close the connection
header("Content-Length: $size");
header('Connection: close');

// flush all output
ob_end_flush();
ob_flush();
flush();

// close current session
if (session_id()) session_write_close();

sleep(10);

echo 'you should not see';
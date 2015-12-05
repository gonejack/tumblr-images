<?php
/**
 * Created by PhpStorm.
 * User: Youi
 * Date: 2015-12-05
 * Time: 02:47
 */

require_once 'google-api-php-client/vendor/autoload.php';

$client = new Google_Client();
$client->setApplicationName("Client_Library_Examples");
$client->setDeveloperKey("YOUR_APP_KEY");

$service   = new Google_Service_Books($client);
$optParams = array('filter' => 'free-ebooks');
$results   = $service->volumes->listVolumes('Henry David Thoreau', $optParams);

foreach ($results as $item) {
    echo $item['volumeInfo']['title'], "<br /> \n";
}
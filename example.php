<?php

use DevIT\MelodiMedia\ApiClient;
use DevIT\MelodiMedia\Parsers\XMLApiParser;

require __DIR__ . '/vendor/autoload.php';

$user = 'user';
$pass = 'pass';
$siteId = 'siteid';

$xmlReader = new XMLApiParser();
$melodi = new ApiClient($user, $pass, $siteId, $xmlReader);
$types = $melodi->contentTypes();

print_r($types);
$contentType = $types[0];

$categories = $melodi->categoriesForContentType($contentType['ContentTypeID']);
//print_r($categories);
$category = $categories[0];
print_r($categories);
$content = $melodi->contentForCategory($category['categoryID']);
////
print_r($content);

//$item = $melodi->contentDetails($content[0]['ContentID']);
//print_r($item);
//
$itemExtended = $melodi->contentDetailsExtended(294392);
var_dump($itemExtended);

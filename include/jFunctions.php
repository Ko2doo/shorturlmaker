<?php
//    header('Content-Type: application/json');
include "config.php";
include "ShortUrl.php";

$json = array();
$shortUrl = new ShortUrl($pdo);

if ($_POST["url_live"]) {
    $shortUrl->changeUrlLive($_POST["url_live"], $_POST["short_code"]);
    return;
}

$json["result"] = $shortUrl->getUrlsFromDb();

echo json_encode($json);
return;

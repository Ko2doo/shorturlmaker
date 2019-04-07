<?php

    include "include/config.php";
    include "include/ShortUrl.php";

    $shortUrl = new ShortUrl($pdo);

    $shortCode = $_GET["c"];

    if ($shortCode) {
        $code = $shortCode;
    } else {
        if (empty($_POST["long_url"])) {
            return print_r("Введите адрес");
        }

        $longUrl = cUrlEncode($_POST["long_url"]);
        $longUrl = iconv("Windows-1251", "UTF-8", $longUrl);//сайт в кодировке win-1251

        $json = array();
        $code = $shortUrl->urlToShortCode($longUrl);
        $json['success'] = 1;
        $json['code'] = $code;

        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
            // В защищенном! Добавим протокол...
            $result .= 'https://';
        } else {
            // Обычное соединение, обычный протокол
            $result .= 'http://';
        }
        // Имя сервера, напр. site.com или www.site.com
        $result .= $_SERVER['SERVER_NAME'];

        $json['url'] = $result . "/r/" . $code;
        echo json_encode($json);
        return;
    }

    try {
        $url = $shortUrl->shortCodeToUrl($code);
        header("Location: " . $url);
        exit;
    } catch (\Exception $e) {
        echo "<h1 align='center'>" . $e->getMessage() . "</h1>";
    //        header("Location: /error");
        exit;
    }

    function cUrlEncode($string)
    {
        $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        return str_replace($entities, $replacements, urlencode($string));
    }
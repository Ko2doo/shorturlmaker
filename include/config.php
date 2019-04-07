<?php

    $host = "127.0.0.1"; //Хост MySQL сервера
    $dbname = "shorturl"; //Имя базы данных
    $user = "root"; //Пользователь
    $pass = ""; //Пароль

    try {
        return $pdo = new PDO("mysql:host=".$host.";dbname=" . $dbname,$user, $pass);
    } catch (\PDOException $e) {
        trigger_error("Ошибка: не могу соединится с базой данных.");
        exit;
    }
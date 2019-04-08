# shorturlmaker

Short URL maker для ClickUz

#Установка проекта через Composer
```
composer create-project deadheadcore/shorturlmaker
```
Импортируйте sql-backup

SQL файл для импорта с нужными таблицами находится в папке "sql_backup" 

Настройте include/config.php файл для подключения к вашей БД

Геолокация юзеров будет работать только с внешнего IP, используется сайт geoplugin.net:
Переходы с локального IP адреса поумолчанию записываются как Uzbekistan
```
http://www.geoplugin.net/php.gp?ip=$user_ip
```

Использована библиотека Bootstrap 3;
За основу проекта взята статья сайта ruseller.com;
```
https://ruseller.com/lessons.php?rub=37&id=1579
```

Возможности проекта:

    Создание коротких ссылок;
    Изменение срока жизни ссылки;
    
    Ведение статистики каждой ссылки:
    Переходы;
    Дата создания;
    Срок жизни ссылки;
    Диаграмма user-agent`ов;
    Географию переходов по странам;
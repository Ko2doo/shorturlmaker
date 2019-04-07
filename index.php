<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Выше 3 Мета-теги ** должны прийти в первую очередь в голове; любой другой руководитель контент *после* эти теги -->
    <title>Short URL Maker</title>

    <!-- Bootstrap -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- Предупреждение: Respond.js не работает при просмотре страницы через файл:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-md-2"></div>
        <div class="col-md-8">
            <h2 align="center">Short URL Maker</h2>
            <form id="ajax_form">
                <div class="input-group">
                    <input id="long_link" type="text" class="form-control" name="long_url"
                           placeholder="Введите полный URL для сжатия"
                           required>
                    <span class="input-group-btn">
                    <button class="btn btn-default" type="submit">Уменьшить</button>
                </span>
                </div><!-- /input-группа -->
            </form>
            <br>

            <div id="alert" hidden="hidden" class="alert" role="alert" style="white-space: nowrap; /* Отменяем перенос текста */
                overflow: hidden; /* Обрезаем содержимое */
                padding-right: 50px; /* Поля */
                text-overflow: ellipsis;">
                <small><a target="_blank" id="long_url" href="#" class="alert-link"></a></small>

                <br>
                <br>

                <a target="_blank" id="short_link" href="#" class="alert-link"></a>
                <p class="navbar-text navbar-right" style="margin: 0">
                    <span class="glyphicon glyphicon-stats" aria-hidden="true"></span>
                    <a id="stats_link" href="/s" target="_blank" class="alert-link"> Статистика</a>
                </p>

                <br>
                <br>

                <label class="control-label" for="company">Изменить срок ссылки</label>
                <select id="hours" name="url_live" class="form-control" title="" style="width: 30%">
                    <option value="1">1 час</option>
                    <option value="2">2 часа</option>
                    <option value="3">3 часа</option>
                </select>

            </div>

            <div id="successDB" hidden="hidden" class="alert alert-success" role="alert"></div>

            <div id="error" hidden="hidden" class="alert alert-danger" role="alert"></div>

        </div>
        <div class="col-md-2"></div>
    </div>
</div>

<script src="/js/jquery-3.3.1.min.js"></script>
<script src="/js/bootstrap.min.js"></script>

<script>

    var code,
        form = $("#ajax_form"),
        successDB = $('#successDB');

    form.submit(function (e) {
        e.preventDefault();
        var alert = $("#alert"),
            error = $("#error"),
            short_link = $('#short_link'),
            stats_link = $('#stats_link'),
            long_link = $('form input[type="text"]');

        if (alert.attr("hidden", false)) {
            alert.attr("hidden", true);
        }

        if (error.attr("hidden", false)) {
            error.attr("hidden", true);
        }

        if (successDB.attr("hidden", false)) {
            successDB.attr("hidden", true);
        }

        $.ajax({
            url: "/r", //url страницы (action_ajax_form.php)
            type: "POST", //метод отправки
            dataType: 'json',
            data: $(this).serialize(),  // Сеарилизуем объект
            success: function (response) { //Данные отправлены успешно
                console.log(response);
                code = response["code"];
                short_link.attr("href", "/r/" + code).html(response["url"]);
                stats_link.attr("href", "/s/" + code);
                $('#long_url').attr("href", long_link.val()).html(long_link.val());
                if (alert.attr("hidden", true)) {
                    alert.attr("hidden", false);
                }
                long_link.val('');
            },
            error: function (response) { // Данные не отправлены
                error.html("Ошибка, проверьте правильность ввода URL");
                if (error.attr("hidden", true)) {
                    error.attr("hidden", false);
                }
            }
        });

        return false;

    });

    $("#hours").change(function () {

        var dataString = $(this).val();

        $.ajax({
            type: "POST",
            url: "include/jFunctions.php",
            data: {url_live: dataString, short_code: code},
            success: function (result) {
                successDB.html("Срок действительности ссылки продлён на " + dataString + " часа");
                if (successDB.attr("hidden", true)) {
                    successDB.attr("hidden", false);
                }
            }
        });
    });

</script>

</body>
</html>
<?php

include "include/config.php";
include "include/ShortUrl.php";

$result = null;

$shortUrl = new ShortUrl($pdo);

$shortCode = $_GET["id"];

if ($shortCode) {

    $result = $shortUrl->getFromCode($shortCode);
    $userAgents = $shortUrl->getFromCodeAgents($shortCode);
    $countries = $shortUrl->getFromCodeCountries($shortCode);

    if ($result) {
        $shortLink = serverURL() . "/r/" . $result["short_code"];
    } else {
        header("Location: " . serverURL());
        exit;
    }
} else {
    header("Location: " . serverURL());
    exit;
}

function serverURL()
{
    $url = "";
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
        // В защищенном! Добавим протокол...
        $url .= 'https://';
    } else {
        // Обычное соединение, обычный протокол
        $url .= 'http://';
    }
    // Имя сервера, напр. site.com или www.site.com
    $url .= $_SERVER['SERVER_NAME'];

    return $url;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">

        // Load the Visualization API and the corechart package.
        google.charts.load("current", {packages: ["corechart", "geochart"]});

        // Set a callback to run when the Google Visualization API is loaded.
        google.charts.setOnLoadCallback(drawChart);

        // Callback that creates and populates a data table,
        // instantiates the pie chart, passes in the data and
        // draws it.
        function drawChart() {

            var chartDiv = document.getElementById('chart_div');

            var dataArrAgents = <?= json_encode($userAgents) ?>;
            var dataArrCountries = <?= json_encode($countries) ?>;
            var dataArr = [];
            var dataArrC = [];

            for (var i = 1; i <= dataArrAgents.length; i++) {
                dataArr[0] = ['Статистика', 'User-agent'];
                dataArr[i] = [dataArrAgents[i - 1]["agent"], parseInt(dataArrAgents[i - 1]["a_counter"])];
            }

            for (var q = 1; q <= dataArrCountries.length; q++) {
                dataArrC[0] = ['Страна', 'Всего переходов'];
                dataArrC[q] = [dataArrCountries[q - 1]["country"], parseInt(dataArrCountries[q - 1]["c_counter"])];
            }
            // Create the data table.
            var data = google.visualization.arrayToDataTable(
                dataArr
            );

            // Set chart options
            var options = {
//                title: "Статистика переходов",
                is3D: true,
                height: 500
            };

            function drawMaterialChart() {
                var chart = new google.visualization.PieChart(document.getElementById('piechart_3d'));
                chart.draw(data, options);
            }

            drawMaterialChart();
            window.onresize = drawMaterialChart;

            var dataGeo = google.visualization.arrayToDataTable(
                dataArrC
            );

            var optionsGeo = {
                colorAxis: {colors: ['#00853f', 'black', '#e31b23']},
                backgroundColor: '#ffffff',
                datalessRegionColor: '#919191',
                defaultColor: '#f5f5f5'
            };

            function drawMaterialGeo() {
                var chartGeo = new google.visualization.GeoChart(document.getElementById('regions_div'));
                chartGeo.draw(dataGeo, optionsGeo);
            }

            drawMaterialGeo();
            window.onresize = drawMaterialGeo();

        }
    </script>
    <style>
        a.qwe {
            white-space: nowrap; /* Запрещаем перенос строк */
            overflow: hidden; /* Обрезаем все, что не помещается в область */
            padding: 5px; /* Поля вокруг текста */
            text-overflow: ellipsis; /* Добавляем многоточие */
        }
    </style>
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
            <hr/>
            <br>
            <h5><span class="glyphicon glyphicon-stats" aria-hidden="true"></span> Переходов по
                ссылке: <?= $result['counter'] ?></h5>
            <h5><span class="glyphicon glyphicon-time" aria-hidden="true"></span> Время создания
                ссылки: <?= date("d.m.y, g:i a", $result["date_created"]); ?></h5>
            <h5><?= ($result["url_live"] < time()) ? "<span class=\"glyphicon glyphicon-remove\" aria-hidden=\"true\"></span> Ссылка не действительна!" : "<span class=\"glyphicon glyphicon-ok\" aria-hidden=\"true\"></span> Ссылка действительна до: " . date("d.m.y, g:i a", $result["url_live"]) ?></h5>
            <h4><a class="list-group-item qwe" style="border: 0"
                   href="<?= $result["long_url"] ?>" target="_blank"><?= $result["long_url"] ?></a></h4>

            <h5><span class="glyphicon glyphicon-link" aria-hidden="true"></span><b><a target="_blank" class="qwe"
                                                                                       style="color: #E31B23"
                                                                                       href="<?= $shortLink ?>"><?= $shortLink ?></a></b>
            </h5>
            <hr/>

            <!--begin tabs going in wide content -->
            <ul class="nav nav-tabs" id="maincontent" role="tablist">
                <li class="active"><a href="#pie" role="tab" data-toggle="tab">Диаграмма</a></li>
                <li><a href="#geo" role="tab" data-toggle="tab">Геолокация</a></li>
            </ul><!--/.nav-tabs.content-tabs -->

            <div class="tab-content" align="center">
                <div class="tab-pane fade in active" id="pie">
                    <div id="piechart_3d"></div>
                </div><!--/.tab-pane -->

                <div class="tab-pane fade" id="geo">
                    <br>
                    <div id="regions_div"></div>
                </div><!--/.tab-pane -->
            </div><!--/.tab-content -->
            <br>
            <br>
        </div>
        <div class="col-md-2"></div>

        <script src="/js/jquery-3.3.1.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>

</body>
</html>



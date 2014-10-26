<?php

namespace Rcms\Core;

use DateTime;
use Rcms\Page\View\DatePickerView;

// Setup environment
require("environment.php");

$oWebsite = new Website();

//JAAR- EN MAANDLIJST
$selectedMonth= $oWebsite->getRequestInt("month", date('n'));//geselecteerd of huidig
$selectedYear = $oWebsite->getRequestInt("year", date('Y'));//geselecteerd of huidig
$dateTime = DateTime::createFromFormat("n Y", $selectedMonth . " " .$selectedYear);

//OBJECTEN
$oArticles = new ArticleRepository($oWebsite);
$articles = $oArticles->getArticlesDataCalendarMonth($dateTime);
$calendarView = new DatePickerView($oWebsite->getText(), $dateTime, $articles);

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="<?php echo $oWebsite->getUrlThemes() . $oWebsite->getConfig()->get('theme') ?>/main.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $oWebsite->getUrlContent() ?>whitebackground.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?php echo $oWebsite->getUrlJavaScripts() ?>tooltip.js"> </script>
        <title><?php echo $oWebsite->t("calendar.pick_a_date"); ?></title>
    </head>
    <body>
        <div>
            <?php
                echo $calendarView->getText();
            ?>
        </div>
    </body>
</html>
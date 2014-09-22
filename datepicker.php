<?php

namespace Rcms\Core;

use DateTime;
use Rcms\Page\View\DatePickerView;

// Report all errors
error_reporting(E_ALL);

// Valid HTML please
ini_set('arg_separator.output', '&amp;');

// Classloader
spl_autoload_register(function($fullClassName) {
    $class = str_replace('\\', '/', subStr($fullClassName, strLen("Rcms\\")));

    // Try to see if it's a class in the library
    if (file_exists('src/' . $class . '.php')) {
        require_once('src/' . $class . '.php');
    }
});

$oWebsite = new Website();

//JAAR- EN MAANDLIJST
$selectedMonth= $oWebsite->getRequestInt("month", date('n'));//geselecteerd of huidig
$selectedYear = $oWebsite->getRequestInt("year", date('Y'));//geselecteerd of huidig
$dateTime = DateTime::createFromFormat("n Y", $selectedMonth . " " .$selectedYear);

//OBJECTEN
$oArticles = new ArticleRepository($oWebsite);
$articles = $oArticles->getArticlesDataCalendarMonth($dateTime);
$calendarView = new DatePickerView($oWebsite, $dateTime, $articles);

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
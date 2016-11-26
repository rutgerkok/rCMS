<?php

namespace Rcms\Core;

use DateTime;
use Rcms\Template\DatePickerTemplate;
use Zend\Diactoros\Stream;

// Setup environment
require(__DIR__ . "/environment.php");

$website = new Website();

//JAAR- EN MAANDLIJST
$selectedMonth= $website->getRequestInt("month", date('n'));//geselecteerd of huidig
$selectedYear = $website->getRequestInt("year", date('Y'));//geselecteerd of huidig
$dateTime = DateTime::createFromFormat("n Y", $selectedMonth . " " .$selectedYear);

//OBJECTEN
$oArticles = new ArticleRepository($website);
$articles = $oArticles->getArticlesDataCalendarMonth($dateTime);
$calendarTemplate = new DatePickerTemplate($website->getText(), $dateTime, $articles);

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="<?php echo $website->getUrlActiveTheme() ?>main.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $website->getUrlMain() ?>whitebackground.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?php echo $website->getUrlJavaScripts() ?>tooltip.js"> </script>
        <title><?php echo $website->t("calendar.pick_a_date"); ?></title>
    </head>
    <body>
        <div>
            <?php
                $stream = new Stream("php://output", 'w');
                $calendarTemplate->writeText($stream);
                $stream->close();
            ?>
        </div>
    </body>
</html>
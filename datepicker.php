<?php
const LOOKBACK_YEARS = 4;
const LOOKAHEAD_YEARS = 8;

error_reporting(E_ALL);

//SITEINSTELLINGEN
ini_set('arg_separator.output','&amp;'); 
// Classloader
function __autoLoad($class) {
    $class = strToLower($class);

    // Try to see if it's a view
    if (subStr($class, -4) == "view") {
        require_once('application/views/' . $class . '.class.php');
        return;
    }

    // Try to see if it's a class in the library
    if (file_exists('application/library/' . $class . '.class.php')) {
        require_once('application/library/' . $class . '.class.php');
        return;
    }

    // Try to load a model
    require_once('application/models/' . $class . '.class.php');
}

$oWebsite = new Website();

//JAAR- EN MAANDLIJST
$selectedMonth= $oWebsite->getRequestInt("month", date('n'));//geselecteerd of huidig
$selectedYear = $oWebsite->getRequestInt("year", date('Y'));//geselecteerd of huidig
$dateTime = new DateTime($selectedYear . "-" . $selectedMonth);

//OBJECTEN
$oArticles = new Articles($oWebsite);
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
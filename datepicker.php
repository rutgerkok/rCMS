<?php

error_reporting(E_ALL);

//SITEINSTELLINGEN
session_start();
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

//OBJECTEN
$oWebsite = new Website();
$oCalendar = new Calendar($oWebsite, $oWebsite->getDatabase());

//JAAR- EN MAANDLIJST
$selected_month=(int)isSet($_POST['month'])? $_POST['month']:date('n');//geselecteerd of huidig
$selected_year = (int) isSet($_POST['year'])? $_POST['year']:date('Y');//geselecteerd of huidig
$monthlist=$oCalendar->get_monthlist($selected_month);
$yearlist =  $oCalendar->get_yearlist($selected_year);
$oCalendar->set_month_and_year($selected_month,$selected_year);//voor calender

?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link href="<?php echo $oWebsite->getUrlThemes() . $oWebsite->getConfig()->get('theme') ?>/main.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo $oWebsite->getUrlContent() ?>whitebackground.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="<?php echo $oWebsite->getUrlJavaScripts() ?>tooltip.js"> </script>
		<title>Kies een datum</title>
		<script type="text/javascript">
			function sendAndClose(obj)
			{
				var day, month, year, date;
				if(obj.firstChild.innerHTML)
				{
					day = parseInt(obj.firstChild.innerHTML);
				}
				else
				{
					day = parseInt(obj.innerHTML);
				}
				month = <?php echo $selected_month-1 ?>;//php gaat van 1-12, maar javascript van 0-11
				year = <?php echo $selected_year ?>;
				date = new Date(year, month, day, 0, 0, 0, 0);
				window.opener.receiveDate(date);
				window.close();
			}
		</script>
	</head>
	<body>
		<div>
			<form action="datepicker.php" method="post">
			<p>
					<?php echo $monthlist ?>
					<?php echo $yearlist ?>
					</p>
			</form>
			<?php
				echo $oCalendar->get_datepicker();
			?>
		</div>
	</body>
</html>
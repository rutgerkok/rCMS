<?php

error_reporting(E_ALL);

//SITEINSTELLINGEN
session_start();
ini_set('arg_separator.output','&amp;'); 
function __autoload($klasse)
{	//automatisch laden van klassen
	require_once('./code/class_'.strtolower($klasse).'.php');
}

//SITE WEERGEVEN
$oWebsite = new Website();
//$oWebsite->echo_header();
$oWebsite->echo_page();
//$oWebsite->echo_footer();
?>
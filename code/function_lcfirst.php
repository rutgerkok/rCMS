<?php
function lcfirst($string)
{
	$eersteletter = strtolower(substr($string,0,1));//eerste letter converteren
	$string =  substr($string,1);//eerste letter weglaten
	return $eersteletter.$string;
}
?>
<?php
$this->set_pagevar('sidebars',false);
//KOPREGEL
echo '<h2>'.$this->translations[0].' '.strtolower($this->translations[11]).'</h2>';

//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oCats = new Categories($oDB,$this);
$oEdit = new Edit($oDB,$oCats,$this);
unset($oDB,$oCats);//niet meer nodig

//INLOGGEN
$logged_in = $oAuth->check(false,true);
unset($oAuth);

//INGEVOERDE GEGEVENS
if($oEdit->check_input()&&$logged_in)
{
	$oEdit->save();
}

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//EDITOR TONEN
if($logged_in)
{
	$oEdit->echo_editor();
	unset($oEdit);
}
?>
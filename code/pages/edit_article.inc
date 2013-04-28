<?php
//KOPREGEL
echo '<h2>'.$this->t("articles.edit").'</h2>';

//OBJECTEN
$oDB = $this->get_database();
$oAuth = new Authentication($this);
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
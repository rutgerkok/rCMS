<h2>Create a new category</h2>
<?php
$text_to_display='';

//OBJECTEN
$oDB = $this->get_database();
$oAuth = new Authentication($this);
$oCat = new Categories($oDB,$this);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$logged_in = $oAuth->check(true,true);
unset($oAuth);

if($logged_in)
{
	$text_to_display.= $oCat->create_category();
}
unset($oCat);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//WEERGEVEN
echo $text_to_display;
?>
<h2>Create a new category</h2>
<?php
$textToDisplay='';

//OBJECTEN
$oDB = $this->getDatabase();
$oAuth = new Authentication($this);
$oCat = new Categories($oDB,$this);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$loggedIn = $oAuth->check(true,true);
unset($oAuth);

if($loggedIn)
{
	$textToDisplay.= $oCat->createCategory();
}
unset($oCat);

//FOUTAFHANDELING
if(!$this->errorsDisplayed)
{
	$this->echoErrors();
}

//WEERGEVEN
echo $textToDisplay;
?>
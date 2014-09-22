<?php namespace Rcms\Core; ?>

<h2>Create a new category</h2>
<?php
$textToDisplay='';

//OBJECTEN
$oAuth = new Authentication($this);
$oCat = new CategoryRepository($this);
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
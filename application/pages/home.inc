<?php
//FOUTAFHANDELING
if(!$this->errorsDisplayed)
{
	$this->echoErrors();
}

$this->getThemeManager()->echo_widgets(1);

?>
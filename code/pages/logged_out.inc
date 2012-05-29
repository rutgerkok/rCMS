<?php

//KOPREGEL
echo '<h2>'.$this->translations[5].'...</h2>';

//UITLOGGEN
$authentication = new Authentication(new Database($this),$this);
$authentication->log_out();
unset($authentication);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//BERICHT TONEN
echo "<h3>{$this->translations[62]} <!-- uitgelogd --> </h3>";
echo "<p>{$this->translations[63]} <!-- je bent nu uitgelogd --> </p>";
echo '<p><a href="index.php" class="button">Home</a></p>';
?>
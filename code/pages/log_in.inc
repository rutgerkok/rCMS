<?php

//KOPREGEL
echo '<h2>'.$this->translations[4].'...</h2>';

//INLOGGEN
$authentication = new Authentication(new Database($this),$this);
$logged_in = $authentication->check(false,true);
unset($authentication);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//EVENTUEEL BERICHT TONEN
if($logged_in)
{
	echo "<h3>{$this->translations[60]} <!-- ingelogd --> </h3>";
	echo "<p>{$this->translations[61]} <!-- je bent nu ingelogd --> </p>";
	echo '<p><a href="index.php" class="arrow">Home</a><br />';
	echo '<a href="index.php?p=account_management" class="arrow">'.$this->translations[3].'</a></p>';
	echo '<script type="text/javascript">location.href = "index.php?p=logged_in"</script>';
}
?>
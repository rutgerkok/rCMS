<?php

//KOPREGEL
echo '<h2>'.$this->t("main.log_out").'...</h2>';

//UITLOGGEN
$authentication = new Authentication($this);
$authentication->log_out();
unset($authentication);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//BERICHT TONEN
echo "<h3>{$this->t("users.logged_out")}</h3>"; //uitgelogd
echo "<p>{$this->t("users.succesfully_logged_out")}</p>"; //u bent nu uitgelogd
echo '<p><a href="'.$this->get_url_main().'" class="button">Home</a></p>';

?>
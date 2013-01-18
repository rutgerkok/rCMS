<?php

//KOPREGEL
echo '<h2>'.$this->t("main.log_in").'...</h2>';

//INLOGGEN
$authentication = new Authentication($this);
$logged_in = $authentication->check(Authentication::$USER_RANK, true);
unset($authentication);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//EVENTUEEL BERICHT TONEN
if($logged_in)
{
	echo "<h3>{$this->t('users.logged_in')}</h3>"; //ingelogd
	echo "<p>{$this->t('users.succesfully_logged_in')}</p>"; //u bent nu ingelogd
	echo '<p><a href="'.$this->get_url_main().'" class="arrow">Home</a><br />';
	echo '<a href="'.$this->get_url_page("account_management").'" class="arrow">'.$this->t("main.account").'</a></p>';

}
?>
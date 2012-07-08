<h2><?php echo $this->t("links.create") ?></h2>
<?php
$text_to_display='';

//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oMenu = new Menu($this,$oDB);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$logged_in = $oAuth->check(false,true);
$is_admin = $oAuth->check(true,false);
unset($oAuth);

if($logged_in)
{
	$text_to_display.= $oMenu->create_menuitem();
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
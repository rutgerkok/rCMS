<h2>Delete a article</h2>
<?php
$text_to_display = '';

//OBJECTEN
$oDB = new Database($this);
$oEdit = new Edit($oDB,new Categories($oDB,$this),$this);
$oAuth = new Authentication($oDB,$this);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$logged_in = $oAuth->check(false,true);
unset($oAuth);

if($logged_in)
{
	//VERWIJDER ARTIKEL
	$text_to_display = $oEdit->delete_article();
	unset($oEdit);
}

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//MELDING WEERGEVEN
if($text_to_display&&!empty($text_to_display))
{
	echo $text_to_display;
}
else
{	//geef zoekvak voor artikelen weer
	echo <<<EOT
	<p>
		Search for the article you want to remove:
		<form id="removesearchform" name="removesearchform" action="index.php" method="GET">
			<input type="hidden" name="p" value="search" />
			<input type="text" size="21" value="" name="searchbox" id="searchbox" />
			<input type="submit" class="button" value="Search" name="searchbutton" id="searchbutton" />
		</form>
	</p
	
	
EOT;

}
?>
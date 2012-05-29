<h2><?php echo $this->translations[70] ?></h2>
<?php
//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oComments = new Comments($this,$oDB,$oAuth);
unset($oDB,$oAuth);//niet meer nodig

//INGEVOERDE GEGEVENS
$saved = false;
if($oComments->check_input())
{
	if($oComments->save())
	{
		$saved = true;
	}
}

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//EDITOR OF MELDING TONEN
if($saved)
{
	echo "<p>".$this->translations[73]." ".$this->translations[46].".</p>";
	echo '<p><a href="index.php?p=view_article&amp;id='.$_REQUEST['id'].'">'.$this->translations[75].'</p>';
}
else
{
	$oComments->echo_editor();
}
unset($oComments);
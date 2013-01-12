<h2><?php echo $this->t("articles.comment.add") ?></h2>
<?php
//OBJECTEN
$oAuth = new Authentication($this);
$oComments = new Comments($this,$oAuth);
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
	echo "<p>".$this->t("articles.comment")." ".$this->t("editor.is_created").".</p>\n\n";
	echo '<p><a href="'.$this->get_url_page("article",$_REQUEST['id']).'">'.$this->t("main.ok").'</p>';
}
else
{
	$oComments->echo_editor();
}
unset($oComments);
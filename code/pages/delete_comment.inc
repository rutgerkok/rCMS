<h2><?php echo $this->translations[1] ?>  <?php echo strtolower($this->translations[73]) ?></h2>
<?php
//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oComments = new Comments($this,$oDB,$oAuth);
$logged_in = $oAuth->check(false,true);//admin niet noodzakelijk
unset($oDB,$oAuth);//niet meer nodig

//INHOUD VOOR PAGINA
$text_to_display = '';

if($logged_in) 
{
	
	$id = isset($_REQUEST['id'])? (int) $_REQUEST['id']: 0;
	
	if(isset($_REQUEST['confirm']))
	{
		//verwijder
		if($oComments->delete_comment($id))
		{
			$text_to_display.= '<p>'.$this->translations[73].' '.$this->translations[82].'</p>';//reactie is verwijderd
			$text_to_display.= '<p><a href="index.php" onclick="history.go(-2);return false">'.$this->translations[75].'</a></p>';//ok en terug
		}
		else
		{
			$text_to_display.= '<p>'.$this->translations[73].' '.$this->translations[83].'</p>';//reactie is NIET verwijderd
			$text_to_display.= '<p><a href="index.php" onclick="history.go(-2);return false">'.$this->translations[75].'</a></p>';//ok en terug
		}
	}
	else
	{
		//toon bevestigingsvraag
		$text_to_display.= '<p>'.$this->translations[79].'</p>';
		
		//en laat daarbij de comment zien
		$comment = $oComments->get_comment($id);
		if(empty($comment))
		{
			//maar geef helemaal niks weer als de pagina niet bestaat
			$text_to_display = '';//maak leeg, geef niks weer
		}
		else
		{
			$text_to_display.= $comment;
			$text_to_display.= '<p><a href="index.php?p=delete_comment&amp;confirm=1&amp;id='.$id.'">'.$this->translations[80].'</a>|';
			$text_to_display.= '<a href="index.php" onclick="history.back();return false">'.$this->translations[81].'</a></p>';
		}
	}
}

unset($oComments);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//WEERGEVEN
echo $text_to_display;//alleen als er geen fouten zijn

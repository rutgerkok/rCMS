<h2><?php echo $this->t("articles.comment.delete") ?></h2>
<?php
//OBJECTEN
$oAuth = new Authentication($this);
$oComments = new Comments($this,$oAuth);
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
			$text_to_display.= '<p>'.$this->t("articles.comment").' '.$this->t("editor.is_removed").'</p>';//reactie is verwijderd
			$text_to_display.= '<p><a href="'.$this->get_url_main().'" onclick="history.go(-2);return false">'.$this->t("main.ok").'</a></p>';//ok en terug
		}
		else
		{
			$text_to_display.= '<p>'.$this->t("articles.comment").' '.$this->t("errors.is_not_removed").'</p>';//reactie is NIET verwijderd
			$text_to_display.= '<p><a href="'.$this->get_url_main().'" onclick="history.go(-2);return false">'.$this->t("main.ok").'</a></p>';//ok en terug
		}
	}
	else
	{
		//toon bevestigingsvraag
		$text_to_display.= '<p>'.$this->t("editor.confirmation.delete.comment").'</p>';
		
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
			$text_to_display.= '<p><a href="'.$this->get_url_page("delete_comment",$id,array("confirm"=>1)).'">'.$this->t("main.yes").'</a>|';
			$text_to_display.= '<a href="'.$this->get_url_main().'" onclick="history.back();return false">'.$this->t("main.no").'</a></p>';
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

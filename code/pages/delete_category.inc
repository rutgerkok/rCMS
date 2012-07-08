<h2>Delete a category</h2>
<?php
$text_to_display='';

//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oCat = new Categories($oDB,$this);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$logged_in = $oAuth->check(true,true);
unset($oAuth);

if($logged_in)
{
	//VERWIJDER CATEGORIE
	if(isset($_REQUEST['id']))
	{	
		$text_to_display=$oCat->delete_category()."<hr />\n";//verwijder catgorie
	}
	
	//OVERZICHT CATEGORIEEN
	$cat_array = $oCat->get_categories();
	$text_to_display.='<p>';
	
	foreach($cat_array as $id=>$name)
	{
		if($id!=1&&$id!=2)
		{	
			$text_to_display.= '<a href="'.$this->get_url_page("delete_category",$id).'" class="arrow">';
			$text_to_display.= 'Delete \''.$name.'\'</a><br />';
		}
		else
		{	//eerste en tweede categorie mogen niet verwijderd worden, geef grijze link weer
			$text_to_display.= '<a href="'.$this->get_url_page("delete_category",$id).'" class="arrow-disabled" title="Cannot delete a standard category!">';
			$text_to_display.= 'Delete \''.$name.'\'</a><br />';
		}
	}
	$text_to_display.= '</p><p>';
	$text_to_display.= '<a href="'.$this->get_url_page("create_category").'" class="arrow">Create a new category</a><br />';
	$text_to_display.= '<a href="'.$this->get_url_page("rename_category").'" class="arrow">Rename a category</a>';
	$text_to_display.= '</p><p>';
	$text_to_display.= '<a href="'.$this->get_url_page("admin").'" class="arrow">Admin</a>';
	$text_to_display.= '</p>';
}

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//WEERGEVEN
echo $text_to_display;
?>
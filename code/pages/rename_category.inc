<h2>Rename a category</h2>
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
	//HERNOEM CATEGORIE
	if(isset($_REQUEST['id']))
	{	
		$text_to_display=$oCat->rename_category()."<hr />\n";//hernoem catgorie
	}
	
	//OVERZICHT CATEGORIEEN
	$cat_array = $oCat->get_categories();
	unset($oCat);//niet meer nodig
	$text_to_display.='<p>';
	
	foreach($cat_array as $id=>$name)
	{
		$text_to_display.= '<a href="index.php?p=rename_category&id='.$id.'" class="arrow">';
		$text_to_display.= 'Rename \''.$name.'\'</a><br />';
	}
	
	$text_to_display.= '</p><p>';
	$text_to_display.= '<a href="index.php?p=create_category" class="arrow">Create a new category</a><br />';
	$text_to_display.= '<a href="index.php?p=delete_category" class="arrow">Delete a category</a>';
	$text_to_display.= '</p><p>';
	$text_to_display.= '<a href="index.php?p=admin" class="arrow">Admin</a>';
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
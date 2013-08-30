<h2>Rename a category</h2>
<?php
$textToDisplay='';

//OBJECTEN
$oAuth = new Authentication($this);
$oCat = new Categories($this);

//INLOGGEN
$loggedIn = $oAuth->check(true,true);
unset($oAuth);

if($loggedIn)
{
	//HERNOEM CATEGORIE
	if(isSet($_REQUEST['id']))
	{	
		$textToDisplay=$oCat->renameCategory()."<hr />\n";//hernoem catgorie
	}
	
	//OVERZICHT CATEGORIEEN
	$cat_array = $oCat->getCategories();
	unset($oCat);//niet meer nodig
	$textToDisplay.='<p>';
	
	foreach($cat_array as $id=>$name)
	{
		$textToDisplay.= '<a href="'.$this->getUrlPage("rename_category",$id).'" class="arrow">';
		$textToDisplay.= 'Rename \''.$name.'\'</a><br />';
	}
	
	$textToDisplay.= '</p><p>';
	$textToDisplay.= '<a href="'.$this->getUrlPage("create_category").'" class="arrow">Create a new category</a><br />';
	$textToDisplay.= '<a href="'.$this->getUrlPage("delete_category").'" class="arrow">Delete a category</a>';
	$textToDisplay.= '</p><p>';
	$textToDisplay.= '<a href="'.$this->getUrlPage("admin").'" class="arrow">Admin</a>';
	$textToDisplay.= '</p>';
}
	
//FOUTAFHANDELING
if(!$this->errorsDisplayed)
{
	$this->echoErrors();
}

//WEERGEVEN
echo $textToDisplay;

?>
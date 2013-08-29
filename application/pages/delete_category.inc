<h2>Delete a category</h2>
<?php
$textToDisplay='';

//OBJECTEN
$oDB = $this->getDatabase();
$oAuth = new Authentication($this);
$oCat = new Categories($oDB,$this);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$loggedIn = $oAuth->check(true,true);
unset($oAuth);

if($loggedIn)
{
	//VERWIJDER CATEGORIE
	if(isSet($_REQUEST['id']))
	{	
		$textToDisplay=$oCat->deleteCategory()."<hr />\n";//verwijder catgorie
	}
	
	//OVERZICHT CATEGORIEEN
	$cat_array = $oCat->getCategories();
	$textToDisplay.='<p>';
	
	foreach($cat_array as $id=>$name)
	{
		if($id!=1)
		{	
			$textToDisplay.= '<a href="'.$this->getUrlPage("delete_category",$id).'" class="arrow">';
			$textToDisplay.= 'Delete \''.$name.'\'</a><br />';
		}
		else
		{	//eerste en tweede categorie mogen niet verwijderd worden, geef grijze link weer
			$textToDisplay.= '<a href="'.$this->getUrlPage("delete_category",$id).'" class="arrow-disabled" title="Cannot delete a standard category!">';
			$textToDisplay.= 'Delete \''.$name.'\'</a><br />';
		}
	}
	$textToDisplay.= '</p><p>';
	$textToDisplay.= '<a href="'.$this->getUrlPage("create_category").'" class="arrow">Create a new category</a><br />';
	$textToDisplay.= '<a href="'.$this->getUrlPage("rename_category").'" class="arrow">Rename a category</a>';
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
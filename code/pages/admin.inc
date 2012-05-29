<h2><?php echo $this->translations[2] ?></h2> <!-- admin -->
<?php
//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oCat = new Categories($oDB,$this);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//AUTHENTICATIE
$logged_in = $oAuth->check(true,true);
unset($oAuth);


//CATEGORIEENLIJST
$cat_rename_list = '';
$cat_delete_list = '';

$cat_events_name = $oCat->get_category_name(2);

$cat_array = $oCat->get_categories();

if(count($cat_array)>0)
{
	$cat_rename_list = '<p>';
	$cat_delete_list = '<p>';
	
	foreach($cat_array as $id=>$name)
	{
		//hernoemlijst
		$cat_rename_list.= '<a href="index.php?p=rename_category&amp;id='.$id.'" class="arrow">';
		$cat_rename_list.= $this->translations[109].' \''.$name.'\'</a><br />';
		
		//verwijderlijst
		if($id!=1&&$id!=2)
		{	
			$cat_delete_list.= '<a href="index.php?p=delete_category&id='.$id.'" class="arrow">';
			$cat_delete_list.= $this->translations[1].' \''.$name.'\'</a><br />';
		}
		else
		{	//eerste drie categorieen mogen niet verwijderd worden, geef grijze link weer
			$cat_delete_list.= '<a href="index.php?p=delete_category&id='.$id.'" class="arrow-disabled" title="Cannot delete a standard category!">';
			$cat_delete_list.= $this->translations[1].' \''.$name.'\'</a><br />';
		}
	}
	
	$cat_rename_list.= '</p>';
	$cat_delete_list.= '</p>';
}

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//PAGINA WEERGEVEN
if($logged_in)
{	
	?>
	
	<fieldset class="panel">
		<legend><?php echo $this->translations[16] ?></legend> <!-- artikelen -->
		<p><a href="index.php?p=edit_article&amp;id=0" class="arrow"><?php echo $this->translations[9] ?></a></p> <!-- nieuw artikel -->
	</fieldset>
	
	<fieldset class="panel">
		<legend><?php echo $this->translations[88] ?></legend> <!-- accounts -->
		<p>
			<a href="index.php?p=create_account" class="arrow"><?php echo $this->translations[93] ?></a><br /> <!-- voeg account toe -->
			<a class="arrow" href="index.php?p=change_password"><?php echo $this->translations[94]. ' ' .strtolower($this->translations[59]) ?></a><br /><!-- verander wachtwoord -->
			<a class="arrow" href="index.php?p=change_email"><?php echo $this->translations[94]. ' ' .strtolower($this->translations[72]) ?></a><br /><!-- verander email -->
			<a href="index.php?p=account_management" class="arrow"><?php echo $this->translations[88] ?></a><br /> <!-- accountbeheer -->
		</p>
	</fieldset>
	
	<fieldset class="panel">
		<legend><?php echo ucfirst($this->translations[99]) ?></legend> <!-- research groups -->
		<p>
			<a href="index.php?p=create_link" class="arrow"><?php echo $this->translations[32] ?></a><br />
			<a href="index.php?p=change_link" class="arrow"><?php echo $this->translations[106] ?> <?php echo $this->translations[14] ?></a><br />
			<a href="index.php?p=delete_link" class="arrow"><?php echo $this->translations[107] ?> <?php echo $this->translations[14] ?></a>
		</p>
	</fieldset>
	
	<fieldset class="panel">
		<legend><?php echo $this->translations[17] ?></legend><!--categorie-->
		<p>
			<a href="index.php?p=create_category" class="arrow"><?php echo $this->translations[108] ?></a><!--nieuwe categorie-->
		</p>
		<?php
			echo $cat_rename_list;
			echo $cat_delete_list;
		?>
	</fieldset>
	
	<fieldset class="panel">
		<legend><?php echo $this->translations[105] ?></legend><!--reset-->
		<p>
			<a href="index.php?p=installing_database" class="arrow"><?php echo $this->translations[110] ?></a>
		</p>
	</fieldset>
	
	
	<?php
}
?>

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
		$cat_rename_list.= '<a href="'.$this->get_url_page("rename_category",$id).'" class="arrow">';
		$cat_rename_list.= $this->t("main.edit").' \''.$name.'\'</a><br />';
		
		//verwijderlijst
		if($id!=1&&$id!=2)
		{	
			$cat_delete_list.= '<a href="'.$this->get_url_page("delete_category",$id).'" class="arrow">';
			$cat_delete_list.= $this->t("main.delete").' \''.$name.'\'</a><br />';
		}
		else
		{	//eerste drie categorieen mogen niet verwijderd worden, geef grijze link weer
			$cat_delete_list.= '<a href="'.$this->get_url_page("delete_category",$id).'" class="arrow-disabled" title="Cannot delete a standard category!">';
			$cat_delete_list.= $this->t("main.delete").' \''.$name.'\'</a><br />';
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
	
	<h3><?php echo $this->t("main.articles") ?></h3> <!-- artikelen -->
	<p><a href="<?php echo $this->get_url_page("edit_article",0) ?>" class="arrow"><?php echo $this->t("articles.create") ?></a></p> <!-- nieuw artikel -->
	
	<h3><?php echo $this->t("users.account_management") ?></h3> <!-- accounts -->
	<p>
		<a href="<?php echo $this->get_url_page("create_account") ?>" class="arrow"><?php echo $this->t("users.create") ?></a><br /> <!-- voeg account toe -->
		<a class="arrow" href="<?php echo $this->get_url_page("change_password") ?>"><?php echo $this->t("users.password.edit") ?></a><br /><!-- verander wachtwoord -->
		<a class="arrow" href="<?php echo $this->get_url_page("change_email") ?>"><?php echo $this->t("users.email.edit") ?></a><br /><!-- verander email -->
		<a href="<?php echo $this->get_url_page("account_management") ?>" class="arrow"><?php echo $this->t("users.account_management") ?></a><br /> <!-- accountbeheer -->
	</p>
	
	<h3><?php echo $this->t("main.links") ?></h3> <!-- research groups -->
	<p>
		<a href="<?php echo $this->get_url_page("create_link") ?>" class="arrow"><?php echo $this->t("links.create") ?></a><br />
		<a href="<?php echo $this->get_url_page("change_link") ?>" class="arrow"><?php echo $this->t("links.edit") ?></a><br />
		<a href="<?php echo $this->get_url_page("delete_link") ?>" class="arrow"><?php echo $this->t("links.delete") ?></a>
	</p>
	
	<h3><?php echo $this->t("main.categories") ?></h3><!--categorie-->
	<p>
		<a href="<?php echo $this->get_url_page("create_category") ?>" class="arrow"><?php echo $this->t("categories.create") ?></a><!--nieuwe categorie-->
	</p>
	<?php
		echo $cat_rename_list;
		echo $cat_delete_list;
	?>
	
	<h3><?php echo $this->t("main.reset") ?></h3><!--reset-->
	<p>
		<a href="<?php echo $this->get_url_page("installing_database") ?>" class="arrow"><?php echo $this->t("main.reset") ?></a>
	</p>

	
	<?php
}
?>

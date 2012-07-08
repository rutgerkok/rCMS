<?php
$cat_id = 0;//standaardwaarde
$text_to_display = '';

$oDB = new Database($this);
$oCats = new Categories($oDB,$this);

if(isset($_REQUEST['id']))
{
	$cat_id = (int) $_REQUEST['id'];
}
if($cat_id>0)
{	//geef artikelen in die categorie weer
	$name = $oCats->get_category_name($cat_id);
	if(!empty($name))
	{	//geef artikelen in die categorie weer
		echo "<h2>".$this->t("categories.articles_in_category")." \"".$oCats->get_category_name($cat_id)."\"</h2> \n";//artikelen in categorie
		echo '<p style="position:relative;top:-.9em">&nbsp;&nbsp;&nbsp;&nbsp;<a class="arrow" href="'.$this->get_url_page("category",$cat_id).'">'.$this->t("categories.show_all").'</a></p>'."\n";//toon alle categorieën
		$oArticles = new Articles($this,$oDB);
		$text_to_display.= $oArticles->get_articles_list_category($cat_id,false,true);
		unset($oArticles);//niet meer nodig
		
		if(empty($text_to_display))
		{	//nog leeg? dan zijn er blijkbaar geen artikelen in die categorie
			$text_to_display.= "<p><em>{$this->t("errors.nothing_found")}.</em></p>";
		}
	}
}

if(empty($text_to_display))
{	//nog leeg? geef dan lijst met categorieen weer
	echo "<h2>".$this->t("categories.all")."</h2>\n";//alle categoriën
	$cats = $oCats->get_categories();
	$text_to_display.= "<p>\n";
	foreach($cats as $cat_id=>$name)
	{
		$text_to_display.= '<a class="arrow" href="'.$this->get_url_page("category",$cat_id).'">'.$name."</a> <br /> \n";
	}
	$text_to_display.= '</p>';
}
unset($oDB);//niet meer nodig
unset($oCats);//niet meer nodig



//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//ARTIKELEN WEERGEVEN
echo $text_to_display;

?>
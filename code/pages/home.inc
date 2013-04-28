<?php
$oArticles = new Articles($this,$this->get_database());
$article_code = $oArticles->get_articles_list_category($this->get_sitevar("sidebarcategories"),true,false);//alles behalve de zijbalkcategorie�n, geen metainfo
unset($articles);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//ARTIKELEN WEERGEVEN
echo $article_code;

?>
<?php
$oArticles = new Articles($this,new Database($this));
$article_code = $oArticles->get_articles_list_category($this->get_sitevar("sidebarcategories"),true,false);//alles behalve de zijbalkcategorieën, geen metainfo
unset($articles);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//ARTIKELEN WEERGEVEN
echo $article_code;

?>
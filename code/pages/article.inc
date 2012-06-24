<?php
$article_id = 0;
if(isset($_REQUEST['id']))
{
	$article_id = $_REQUEST['id'];
}


$articles = new Articles($this,new Database($this));
$article_code = $articles->get_article($article_id);
unset($articles);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//ARTIKELEN WEERGEVEN
if($article_code)
{
	echo $article_code;
}
else
{
	echo '&nbsp;';	
}
?>
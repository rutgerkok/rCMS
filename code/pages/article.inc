<?php

$article_id = 0;
if (isset($_REQUEST['id'])) {
    $article_id = $_REQUEST['id'];
}

$oArticles = new Articles($this, $this->get_database());
$article_code = $oArticles->get_article($article_id, new Comments($this, new Authentication($this)));
unset($oArticles);

//FOUTAFHANDELING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

//ARTIKELEN WEERGEVEN
if ($article_code) {
    echo $article_code;
} else {
    echo '&nbsp;';
}
?>
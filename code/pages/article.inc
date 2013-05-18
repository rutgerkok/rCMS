<?php

// EXECUTE LOGIC
$article_id = 0;
if (isset($_REQUEST['id'])) {
    $article_id = (int) $_REQUEST['id'];
}

$oArticles = new Articles($this, $this->get_database());
$article = $oArticles->get_article_data($article_id);
$article_code = "";
if ($article != null) {
    $article_code = $oArticles->get_article_text_full($article, new Comments($this, new Authentication($this)));
} else {
    $this->add_error($this->t('main.article') . ' ' . $this->t('errors.not_found'));
}

unset($oArticles, $article);

// ERROR HANDLING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// DISPLAY PAGE
if ($article_code) {
    echo $article_code;
}
?>
<?php

// EXECUTE LOGIC
$article_id = 0;
if (isSet($_REQUEST['id'])) {
    $article_id = (int) $_REQUEST['id'];
}

$oArticles = new Articles($this, $this->getDatabase());
$article = $oArticles->getArticleData($article_id);
$article_code = "";
if ($article != null) {
    $article_code = $oArticles->getArticleTextFull($article, new Comments($this, new Authentication($this)));
} else {
    $this->addError($this->t('main.article') . ' ' . $this->t('errors.not_found'));
}

unset($oArticles, $article);

// ERROR HANDLING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// DISPLAY PAGE
if ($article_code) {
    echo $article_code;
}
?>
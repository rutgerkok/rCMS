<h2>Delete a article</h2>
<?php
$textToDisplay = '';

//OBJECTEN
$oDB = $this->getDatabase();
$oEdit = new ArticleEdit($oDB, new Categories($this), $this);
$oAuth = new Authentication($this);
unset($oDB); //al niet meer nodig, want doorgegeven als parameter
//INLOGGEN
$loggedIn = $oAuth->check(false, true);
unset($oAuth);

if ($loggedIn) {
    //VERWIJDER ARTIKEL
    $textToDisplay = $oEdit->deleteArticle();
    unset($oEdit);

    if (!$textToDisplay || empty($textToDisplay)) {
        $textToDisplay = <<<EOT
	<p>
		Search for the article you want to remove:
		<form id="removesearchform" name="removesearchform" action="{$this->getUrlMain()}" method="GET">
			<input type="hidden" name="p" value="search" />
			<input type="text" size="21" value="" name="searchbox" id="searchbox" />
			<input type="submit" class="button" value="Search" name="searchbutton" id="searchbutton" />
		</form>
	</p>
		
EOT;
    }
}

//FOUTAFHANDELING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

//MELDING WEERGEVEN
if($loggedIn) {
    echo $textToDisplay;
}
?>
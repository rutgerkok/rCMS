<h2><?php echo $this->t("main.log_in") ?>...</h2>
<?php
$oAuth = new Authentication($this);
$textToDisplay = "";

// LOG IN AS SOMEONE ELSE
$loggedIn = $oAuth->check(Authentication::$ADMIN_RANK,true);
if($loggedIn) {
    $id = isSet($_REQUEST["id"])? (int) $_REQUEST["id"]: 0;
    $user = null;
    if($id > 0) {
        $user = User::getById($this, $id);
        if($user->isAdmin()) {
            // Can not spoof login of admin account
            $user = null;
        }
    }
    $loggedIn = false;
    if($user != null) {
        if($oAuth->setCurrentUser($user)) {
            $textToDisplay = "<p>" . $this->tReplaced('users.succesfully_loggedIn.other', $user->getUsername()) . "</p>\n";
            $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlMain() . '">' . $this->t("main.ok") . "</a></p>\n";
            $loggedIn = true;
        }
    }
    if(!$loggedIn) {
        $textToDisplay = "<p>" . $this->t('users.account') . " " . $this->t('errors.not_found') . "</p>\n";
        $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlMain() . '">' . $this->t("main.ok") . "</a></p>\n";
    }
}
unset($oAuth);

// ERROR HANDLING
if(!$this->errorsDisplayed)
{
	$this->echoErrors();
}

// SHOW MESSAGE
echo $textToDisplay;
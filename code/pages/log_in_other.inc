<h2><?php echo $this->t("main.log_in") ?>...</h2>
<?php
$oAuth = new Authentication($this);
$text_to_display = "";

// LOG IN AS SOMEONE ELSE
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK,true);
if($logged_in) {
    $id = isset($_REQUEST["id"])? (int) $_REQUEST["id"]: 0;
    $user = null;
    if($id > 0) {
        $user = User::get_by_id($this, $id);
        if($user->is_admin()) {
            // Can not spoof login of admin account
            $user = null;
        }
    }
    $logged_in = false;
    if($user != null) {
        if($oAuth->set_current_user($user)) {
            $text_to_display = "<p>" . $this->t_replaced('users.succesfully_logged_in.other', $user->get_username()) . "</p>\n";
            $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_main() . '">' . $this->t("main.ok") . "</a></p>\n";
            $logged_in = true;
        }
    }
    if(!$logged_in) {
        $text_to_display = "<p>" . $this->t('users.account') . " " . $this->t('errors.not_found') . "</p>\n";
        $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_main() . '">' . $this->t("main.ok") . "</a></p>\n";
    }
}
unset($oAuth);

// ERROR HANDLING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

// SHOW MESSAGE
echo $text_to_display;
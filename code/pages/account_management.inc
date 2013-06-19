<?php
$text_to_display = '';

//KOPREGEL
echo '<h2>'.$this->t("users.account_management").'</h2>';//accountbeheer

//INLOGGEN
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK, true);//wel formulier, geen admin


//ACCOUNT WIJZIGEN
if($logged_in)
{
	$user = $oAuth->get_current_user()->get_username();
	$email = $oAuth->get_current_user()->get_email();
	if(empty($email))
		$email = '<em>'.$this->t('errors.not_set').'</em>';//niet ingesteld

	$password_text = strtolower($this->t("users.password"));
	$email_text = strtolower($this->t("users.email"));
        $display_name_text = strtolower($this->t("users.display_name"));
	
	$text_to_display.= <<<EOT
	<p>
		<table>
			<tr>
				<th>{$this->t("users.username")}</th><td>$user</td>
			</tr>
			<tr>
				<th>{$this->t("users.email")}</th><td>$email</td>
			</tr>
		</table>
	</p>
	
	<h3>{$this->t("users.edit")}</h3>
	<p>
		<a class="arrow" href="{$this->get_url_page('edit_password')}">{$this->t("main.edit")} {$password_text}</a><br />
		<a class="arrow" href="{$this->get_url_page('edit_email')}">{$this->t("main.edit")} {$email_text}</a><br />
                <a class="arrow" href="{$this->get_url_page('edit_display_name')}">{$this->t("main.edit")} {$display_name_text}</a><br />    
	</p>
EOT;
    // Users table
    $text_to_display.= "<h3>{$this->t("users.edit.other")}</h3>";
    $text_to_display.= $oAuth->get_users_table();
    $text_to_display.= '<p><br /><a class="arrow" href="'.$this->get_url_page('admin').'">'.$this->t("main.admin").'</a></p>';
}
unset($oAuth);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//TEKST WEERGEVEN
echo $text_to_display;
?>
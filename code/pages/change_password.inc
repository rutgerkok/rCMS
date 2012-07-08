<h2><?php echo $this->t("password.edit") /*verander wachtwoord*/ ?></h2>
<?php
$text_to_display='';

//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oMenu = new Menu($this,$oDB);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$logged_in = $oAuth->check(false,true);


if($logged_in)
{
	$display_form = true;//vooralsnog weergeven
	if(isset($_POST['pass'])&&isset($_POST['pass2']))
	{
		if($oAuth->valid_password($_POST['pass'],$_POST['pass2']))
		{
			if($oAuth->set_password($_POST['pass']))
			{
				$text_to_display.='<p>'.$this->t("users.password").' '.$this->t("editor.is_changed").'.</p>';//password changed
				$text_to_display.='<p><a href="'.$this->get_url_page("account_management").'" class="arrow">'.$this->t("users.account_management").'</a></p>';//account management
				
				$display_form = false;
			}
			else
			{
				$text_to_display.='<p>Your password has <strong>not</strong> been changed.</p>';
				$display_form = true;
			}
		}
		else
		{
			$text_to_display.='<p>Your password has <strong>not</strong> been changed.</p>';
			$display_form = true;
		}
	}
	if($display_form)
	{
		
		$text_to_display.="<p>\n".string_replace("#",5,$this->t("users.password.edit.explained"))."\n</p>\n\n"; //hardcoded totdat gebruikersklasse opnieuw geschreven wordt
		$text_to_display.=<<<EOT
		<form action="{$this->get_url_main()}" method="post">
			<p>
				<label for="pass">{$this->t("users.password")}:</label><br /><input type="password" id="pass" name="pass" value=""/><br />
				<label for="pass2">{$this->t("users.password.repeat")}</label><br /><input type="password" id="pass2" name="pass2" value=""/><br />
			</p>
			<p>
				<input type="hidden" name="p" value="change_password" />
				<input type="submit" value="{$this->t("users.password.edit")}" class="button" />
			</p>
		</form>
		
		<p>
				<a href="'.$this->get_url_page("account_management").'" class="arrow">{$this->t("users.account_management")}</a><!-- account management -->
		</p>
EOT;
	}
}
unset($oAuth);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//WEERGEVEN
echo $text_to_display;
?>
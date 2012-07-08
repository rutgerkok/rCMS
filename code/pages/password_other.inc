<h2>Change someone else's password</h2>
<?php
$text_to_display='';

//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oMenu = new Menu($this,$oDB);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$logged_in = $oAuth->check(true,true);


if($logged_in)
{
	$id = isset($_REQUEST['id'])? $_REQUEST['id']: 0;
	$name = $oAuth->get_username($id);
	if(!empty($name))
	{	//alleen als gebruiker bestaat
		$display_form = true;//vooralsnog weergeven
		if(isset($_POST['pass'])&&isset($_POST['pass2']))
		{
			if($oAuth->valid_password($_POST['pass'],$_POST['pass2']))
			{
				if($oAuth->set_password($_POST['pass'],$id))
				{
					$text_to_display.='<p>The password has been changed.</p>';
					$text_to_display.='<p><a href="'.$this->get_url_page("account_management").'" class="arrow">Account management</a><br />';
					$text_to_display.='<a href="'.$this->get_url_page("admin").'" class="arrow">Admin</a></p>';
					
					$display_form = false;
				}
				else
				{
					$text_to_display.='<p>The password has <strong>not</strong> been changed.</p>';
					$display_form = true;
				}
			}
			else
			{
				$text_to_display.='<p>The password has <strong>not</strong> been changed.</p>';
				$display_form = true;
			}
		}
		if($display_form)
		{
			$text_to_display.=<<<EOT
			<h3>Changing the password of $name</h3>
			
			<form action="{$this->get_url_main()}" method="post">
				<p>
					<label for="pass">Password:</label><br /><input type="password" id="pass" name="pass" value=""/><br />
					<label for="pass2">Repeat password:</label><br /><input type="password" id="pass2" name="pass2" value=""/><br />
				</p>
				<p>
					<input type="hidden" name="p" value="password_other" />
					<input type="hidden" name="id" value="$id" />
					<input type="submit" value="Change password" class="button" />
				</p>
			</form>
			
			<p>
				<a href="{$this->get_url_page("account_management")}" class="arrow">Account management</a><br />
				<a href="{$this->get_url_page("admin")}" class="arrow">Admin</a>
			</p>
			
EOT;
		}
	}
}
else
{	//niet goed ingelogd
	echo "<p>\n";
	echo 'This function is for administrators only. To change your password, <a href="'.$this->get_url_main().'">click here</a>.'."\n";
	echo "</p>\n";
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
<h2>Change someone else's email address</h2>
<?php
$text_to_display='';

//OBJECTEN
$oDB = $this->get_database();
$oAuth = new Authentication($oDB,$this);
$oMenu = new Menus($this,$oDB);
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
		if(isset($_POST['email']))
		{
			if($oAuth->valid_email($_POST['email']))
			{
				if($oAuth->set_email($_POST['email'],$id))
				{
					$text_to_display.='<p>The email address has been changed.</p>';
					$text_to_display.='<p><a href="'.$this->get_url_page("account_management").'" class="arrow">Account management</a><br />';
					$text_to_display.='<a href="'.$this->get_url_page("admin").'" class="arrow">Admin</a></p>';
					
					$display_form = false;
				}
				else
				{
					$text_to_display.='<p>The email has <strong>not</strong> been changed.</p>';
					$display_form = true;
				}
			}
			else
			{
				$text_to_display.='<p>The email has <strong>not</strong> been changed.</p>';
				$display_form = true;
			}
		}
		if($display_form)
		{
			$email = isset($_POST['email'])?$_POST['email']:$oAuth->get_email($id);
		
			$text_to_display.=<<<EOT
			<h3>Changing the email address of $name</h3>
			
			<form action="{$this->get_url_main()}" method="post">
				<p>
					<label for="email">Email:</label><br /><input type="email" id="email" name="email" value="$email"/>
				</p>
				<p>
					<input type="hidden" name="p" value="email_other" />
					<input type="hidden" name="id" value="$id" />
					<input type="submit" value="Change email address" class="button" />
				</p>
			</form>
			
			<p>
				<a href="'.$this->get_url_page("account_management").'" class="arrow">Account management</a><br />
				<a href="'.$this->get_url_page("admin").'" class="arrow">Admin</a>
			</p>
EOT;
		}
	}
}
else
{	//niet goed ingelogd
	?>
		<p>
			This function is for administrators only. To change your email address, <a href="'.$this->get_url_page('change_email').'">click here</a>.
		</p>
	<?php
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
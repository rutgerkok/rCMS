<h2>Change your email address</h2>
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
	if(isset($_POST['email']))
	{
		if($oAuth->valid_email($_POST['email']))
		{
			if($oAuth->set_email($_POST['email']))
			{
				$text_to_display.='<p>Your email address has been changed.</p>';
				$text_to_display.='<p><a href="index.php?p=account_management" class="arrow">Account management</a></p>';
				
				$display_form = false;
			}
			else
			{
				$text_to_display.='<p>Your email address has <strong>not</strong> been changed.</p>';
				$display_form = true;
			}
		}
		else
		{
			$text_to_display.='<p>Your email address has <strong>not</strong> been changed.</p>';
			$display_form = true;
		}
	}
	if($display_form)
	{
		$email = isset($_POST['email'])? $_POST['email']:$oAuth->get_current_email();
	
		$text_to_display.=<<<EOT
		<p>
			Fill out the form below to change your email address. 
		</p>
		
		<form action="index.php" method="post">
			<p>
				<label for="email">Email address:</label><br /><input type="email" id="email" name="email" value="$email"/><br />
			</p>
			<p>
				<input type="hidden" name="p" value="change_email" />
				<input type="submit" value="Change email address" class="button" />
			</p>
		</form>
		
		<p>
			<a href="index.php?p=account_management" class="arrow">Account management</a>
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
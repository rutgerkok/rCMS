<h2><?php echo $this->translations[94] ?> <?php echo strtolower($this->translations[59]) /*verander wachtwoord*/ ?></h2>
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
				$text_to_display.='<p>'.$this->translations[59].' '.$this->translations[45].'.</p>';//password changed
				$text_to_display.='<p><a href="index.php?p=account_management" class="arrow">'.$this->translations[88].'</a></p>';//account management
				
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
		//vertaalvariabelen
		$repeat_password = $this->translations[96].' '.strtolower($this->translations[59]);
		$change_password = $this->translations[94].' '.strtolower($this->translations[59]);
		
		$text_to_display.=<<<EOT
		<p>
			{$this->translations[95]}
		</p>
		
		<form action="index.php" method="post">
			<p>
				<label for="pass">{$this->translations[59]}:</label><br /><input type="password" id="pass" name="pass" value=""/><br />
				<label for="pass2">$repeat_password:</label><br /><input type="password" id="pass2" name="pass2" value=""/><br />
			</p>
			<p>
				<input type="hidden" name="p" value="change_password" />
				<input type="submit" value="$change_password" class="button" />
			</p>
		</form>
		
		<p>
				<a href="index.php?p=account_management" class="arrow">{$this->translations[88]}</a><!-- account management -->
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
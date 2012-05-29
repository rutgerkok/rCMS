<h2><?php echo $this->translations[107] ?> <?php echo lcfirst($this->translations[14]) ?></h2><!-- verwijder link -->
<?php
$text_to_display='';

//OBJECTEN
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
$oMenu = new Menu($this,$oDB);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$logged_in = $oAuth->check(false,true);
$is_admin = $oAuth->check(true,false);
unset($oAuth);

if($logged_in)
{
	//VERWIJDER MENUITEM
	if(isset($_REQUEST['id']))
	{	
		$text_to_display=$oMenu->delete_menuitem()."<hr />\n";//verwijder menuitem
	}
	
	//OVERZICHT MENUITEMS
	$menu_array = $oMenu->get_items();
	$text_to_display.='<p>';
	
	if(is_array($menu_array))
	{	//voor de zekerheid
		foreach($menu_array as $id=>$name)
		{
			$text_to_display.= '<a href="index.php?p=delete_link&amp;id='.$id.'" class="arrow">';
			$text_to_display.= 'Delete \''.$name[1].'\' </a><br />';
			$found_link = true;
		}
	}
	else
	{
		$text_to_display.= '<em>No research groups found.</em>';
	}
	
	$text_to_display.= '</p><p>';
	$text_to_display.= '<a href="index.php?p=create_link" class="arrow">Create a new research group</a><br />';
	$text_to_display.= '<a href="index.php?p=change_link" class="arrow">Change a research group</a>';
	$text_to_display.= '</p><p>';
	if($is_admin) $text_to_display.= '<a href="index.php?p=admin" class="arrow">Admin</a><br />';
	$text_to_display.= '<a href="index.php" class="arrow">Home</a>';
	$text_to_display.= '</p>';
}

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//WEERGEVEN
echo $text_to_display;
?>
<h2><?php echo $this->t("links.edit") ?></h2>
<?php
$text_to_display='';

//OBJECTEN
$oDB = $this->get_database();
$oAuth = new Authentication($this);
$oMenu = new Menu($this,$oDB);
unset($oDB);//al niet meer nodig, want doorgegeven als parameter

//INLOGGEN
$logged_in = $oAuth->check(false,true);
$is_admin = $oAuth->check(true,false);
unset($oAuth);

if($logged_in)
{
	//HERNOEM MENUITEM
	if(isset($_REQUEST['id']))
	{	
		$text_to_display=$oMenu->change_menuitem()."<hr />\n";//verander research group
	}
	
	//OVERZICHT MENUITEMS
	$menu_array = $oMenu->get_items();
	$text_to_display.='<p>';
	
	if(is_array($menu_array))
	{	//voor de zekerheid
		foreach($menu_array as $id=>$name)
		{
			$text_to_display.= '<a href="'.$this->get_url_page("change_link",$id).'" class="arrow">';
			$text_to_display.= 'Change \''.$name[1].'\' </a><br />';
		}
	}
	else
	{
		$text_to_display.= '<em>No research groups found.</em>';
	}
	
	$text_to_display.= '</p><p>';
	$text_to_display.= '<a href="'.$this->get_url_page("create_link").'" class="arrow">Create a new research group</a><br />';
	$text_to_display.= '<a href="'.$this->get_url_page("delete_link").'" class="arrow">Remove a research group</a>';
	$text_to_display.= '</p><p>';
	if($is_admin) $text_to_display.= '<a href="'.$this->get_url_page("admin").'" class="arrow">Admin</a><br />';
	$text_to_display.= '<a href="'.$this->get_url_main().'" class="arrow">Home</a>';
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
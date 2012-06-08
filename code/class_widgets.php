<?php
//Is verantwoordelijk voor het bijhouden van alle widgets
class Widgets
{
	private $website_object;
	
	public function __construct($oWebsite)
	{
		$this->website_object = $oWebsite;
	}
	
	//Slaat op in de database of dat die widget geïnstalleerd is
	public function add_widget_sidebar($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $oWebsite->get_database();
		
	}
	
	//Geeft een lijst terug van alle geïnstalleerde widgets
	public function get_widgets_installed()
	{
		
	}
	
	//Echo't alle widgets
	public function echo_widgets_sidebar($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $oWebsite->get_database();
		
		$id = (int) $id;//beveiliging
		
		$result = $oDB->query("SELECT `widget_id`, `widget_naam`, `widget_data` FROM `widgets` WHERE `sidebar_id` = $id");
		
		while(list($id,$name,$data) = $oDB->fetch($result))
		{
			$file = $oWebsite->get_uri_widgets().$name."/main.php";
			if(file_exists($file))
			{
				require($file);
				$widget->echo_widget($oWebsite,json_decode($data,true));
			}
			else
			{
				$oWebsite->add_error("The widget $name (id=$id) was not found. File <code>$file</code> was missing.","A widget was missing.");
			}
		}
	}
}	
?>
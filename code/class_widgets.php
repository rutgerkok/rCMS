<?php
//Is verantwoordelijk voor het bijhouden van alle widgets
class Widget
{
	private $website_object;
	
	public function __contruct($oWebsite)
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
	
	//Geeft een lijst terug van alle actieve widgets voor de opgegeven sidebar
	public function get_widgets_sidebar($id)
	{
		$oWebsite = $this->website_object;
		$oDB = $oWebsite->get_database();
		
		$result = $oDB->query("SELECT `widget_id`, `widget_naam`, `sidebar_id`");
	}
}	
?>
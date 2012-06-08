<?php
$widget = new Disp_articles();

//Voorbeeldwidget
class Disp_articles
{
	private $name = "Display articles";
	private $version = "1.0";
	
	public function get_name()
	{
		return $this->name;
	}
	
	public function get_version()
	{
		return $this->version;	
	}
	
	public function echo_widget($oWebsite,$data)
	{
		//if(isset($data["cat_id"]))
		//{
		//	if((int)$data["cat_id"]!=0)
		//	{	//geef alle artikelen weer
		$data["cat_id"] = 1;
				$oArticles = new Articles($oWebsite,$oWebsite->get_database());
				echo $oArticles->get_articles_list_category(array(2),false,false,4);
		//	}
		//}
	}
}	
?>
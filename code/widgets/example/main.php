<?php
$widget = new Example();

//Voorbeeldwidget
class Example
{
	private $name = "Example widget";
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
		echo '<div class="widget"><h3>VARDUMP</h3><pre>';
		var_dump($data);
		echo "</pre></div>";
	}
}	
?>
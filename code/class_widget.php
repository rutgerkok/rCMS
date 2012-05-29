<?php
//Representeert een willekeurig widget
class Widget
{
	private $name = "Example widget";
	private $version = "1.0";
	private $sidebar = 0;
	private $data = array();
	
	public function __contruct($oWebsite,$data)
	{
		//doet niks, het is een standaardwidget
	}
	
	public function get_name()
	{
		return $this->name;
	}
	
	public function get_version()
	{
		return $this->version;	
	}
	
	public function get_sidebar()
	{
		return $this->sidebar;
	}
	
	public function echo_widget()
	{
		echo <<<EOT
		<div id="widget">
			<h3>Example widget</h3>
			<p>
				An example widget.
			</p>
		</div>
EOT;
	}
	
	public function set_sidebar($id)
	{
		$this->sidebar = $id;
	}
}	
?>
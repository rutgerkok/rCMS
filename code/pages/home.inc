<?php
//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

$this->get_theme_manager()->echo_widgets(1);

?>
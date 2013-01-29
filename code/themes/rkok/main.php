<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link href="<?php echo $this->get_url_theme()."main.css" ?>" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="<?php echo $this->get_url_scripts() ?>tooltip.js"> </script>
		<title><?php echo $this->get_page_title(); ?></title>
	</head>
	<body>
                <div id="header">
                        <div id="hornav">
                                <h1> <?php echo $this->get_page_title(); ?> </h1>
                                <ul>
                                    <?php $this->echo_menu(); ?>
                                    <?php $this->echo_accounts_menu(); ?>
                                </ul>
                                <div id="search">
                                    <?php $this->echo_search_form(); ?>
				</div>
                        </div>
                </div>
                <div id="container">
			<div id="containerwrapper">				
				<div <?php
					if($this->get_page_type()=="NORMAL")
					{
						echo 'id="content"';
					}
					elseif($this->get_page_type()=="BACKSTAGE")
					{
						echo 'id="contentadmin"';
					}
					else // dus $this->get_page_type()=="NOWIDGETS"
					{
						echo 'id="contentwide"';
					}
				 
				 
				 	?> >
				<!-- Einde header -->
				
				<?php $this->echo_page(); ?>
				
				<!-- Begin footer -->
		
				</div><!-- id="content"/"contentwide" -->
				
				<?php if($this->get_page_type()=="NORMAL") { ?>
					<div id="sidebar">
						<?php $this->echo_widgets(0); ?>
					</div>
				<?php } ?>
				
				<div id="footer">
					<?php $this->echo_copyright(); ?>
				</div>
			</div><!-- id="containerwrapper" -->
		</div><!-- id="container" -->
	</body>
</html>	

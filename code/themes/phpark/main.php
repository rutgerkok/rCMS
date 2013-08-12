<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="<?php echo $this->get_url_theme() . "main.css" ?>" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?php echo $this->get_url_scripts() ?>tooltip.js"> </script>
        <title><?php echo $this->get_site_title(); ?></title>
    </head>
    <body>
        <div id="container">
            <div id="containerwrapper">
                <div id="header">
                    <h1>
                        <?php echo $this->get_site_title(); ?>
                    </h1>
                    <div id="search">
                        <?php $this->echo_search_form(); ?>
                    </div>
                    <?php if($this->logged_in()) { ?>
                        <div id="account_label">
                            <?php $this->echo_account_label(); ?>
                            <div id="account_box">
                                <?php $this->echo_account_box(80); ?>
                                <div style="clear:both"></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <div id="hornav">
                    <ul>
                        <?php $this->echo_menu(); ?>
                    </ul>
                    <?php if(!$this->logged_in()) { ?>
                        <ul id="accountlinks">
                            <?php $this->echo_accounts_menu(); ?>
                        </ul>
                    <?php } ?>
                </div>
                <div <?php
                        if ($this->get_page_type() == "HOME") {
                            echo 'id="content"';
                        } elseif ($this->get_page_type() == "BACKSTAGE") {
                            echo 'id="contentadmin"';
                        } else { // dus $this->get_page_type()=="NORMAL"
                            echo 'id="contentwide"';
                        }
                        ?> >
                <!-- Einde header -->

                <?php $this->echo_page_content(); ?>

                <!-- Begin footer -->

                </div><!-- id="content"/"contentwide" -->

                    <?php if ($this->get_page_type() == "HOME") { ?>
                    <div id="sidebar">
                        <?php $this->echo_widgets(2); ?>
                    </div>
                    <div id="nav">
                        <?php $this->echo_widgets(3); ?>
                    </div>
                <?php } ?>

                <div id="footer">
<?php $this->echo_copyright(); ?>
                </div>
            </div><!-- id="containerwrapper" -->
        </div><!-- id="container" -->
    </body>
</html>	

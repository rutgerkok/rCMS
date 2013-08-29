<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="<?php echo $this->get_url_theme()."main.css" ?>" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?php echo $this->getUrlJavaScripts() ?>tooltip.js"> </script>
        <title><?php echo $this->getSiteTitle(); ?></title>
    </head>
    <body <?php if($this->isLoggedIn()) { 
        echo 'class="loggedIn"';
    }
    ?>>
        <div id="container">
            <div id="header">
                <h1> <?php echo $this->getSiteTitle(); ?> </h1>
                <ul id="main_menu">
                    <?php $this->echo_menu(); ?>
                </ul>
                <div id="search">
                    <?php $this->echo_search_form(); ?>
                </div>
                <div id="account_label">
                    <?php $this->echo_account_label(); ?>
                    <div id="account_box">
                        <?php $this->echo_account_box(); ?>
                        <div style="clear:both"></div>
                    </div>
                </div>
            </div>
            <div <?php
                    if($this->getPageType()=="BACKSTAGE")
                    {
                            echo 'id="contentadmin"';
                    }
                    else
                    {
                            echo 'id="content"';
                    }
                    ?>>
                <?php $this->echoPageContent(); ?>
            </div>

            <?php if($this->getPageType()!="BACKSTAGE") { ?>
                    <div id="sidebar">
                            <?php $this->echo_widgets(2); ?>
                    </div>
            <?php } ?>

            <div id="footer">
                    <?php $this->echo_copyright(); ?>
            </div>
        </div>
    </body>
</html>	

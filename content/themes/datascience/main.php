<?php

// Protect against calling this script directly
if (!isset($this)) {
    die();
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="<?php echo $this->get_url_theme() . "main.css" ?>" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?php echo $this->getUrlJavaScripts() ?>tooltip.js"></script>
        <title><?php echo $this->getSiteTitle(); ?></title>
    </head>
    <body>
        <div id="container">
            <div id="containerwrapper">
                <div id="breadcrumbs">
                    <?php $this->echo_breadcrumbs(); ?>
                    <ul id="accountlinks">
                        <?php $this->echo_accounts_menu(); ?>
                    </ul>
                </div>
                <div id="header">
                    <div id="search">
                        <?php $this->echo_search_form(); ?>
                    </div>
                    <h1>
                        <?php echo $this->getSiteTitle(); ?>
                    </h1>
                </div>
                <div id="hornav">
                    <ul>
                        <?php $this->echo_menu(); ?>
                    </ul>
                </div>
                <div <?php
                        if ($this->getPageType() == "HOME") {
                            echo 'id="content"';
                        } elseif ($this->getPageType() == "BACKSTAGE") {
                            echo 'id="contentadmin"';
                        } else { // dus $this->get_page_type()=="NORMAL"
                            echo 'id="contentwide"';
                        }
                        ?> >
                    <!-- Einde header -->

                <?php $this->echoPageContent(); ?>

                    <!-- Begin footer -->

                </div><!-- id="content"/"contentwide" -->

                    <?php if ($this->getPageType() == "HOME") { ?>
                    <div id="sidebar">
    <?php $this->echoWidgets(2); ?>
                        &nbsp;
                    </div>
                    <div id="nav">
    <?php $this->echoWidgets(3); ?>
                        &nbsp;
                    </div>
                <?php } ?>

                <div id="footer">
<?php $this->echo_copyright(); ?>
                </div>
            </div><!-- id="containerwrapper" -->
        </div><!-- id="container" -->
    </body>
</html>	

<?php
// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="<?php echo $this->getUrlTheme() . "main.css" ?>" rel="stylesheet" type="text/css" />
        <script src="<?php echo $this->getUrlJavaScripts() ?>tooltip.js"></script>
        <!--[if lte IE 8]>
            <script src="<?php echo $this->getUrlJavaScripts() ?>html5.js"></script>
        <![endif]-->
        <title><?php echo $this->getSiteTitle(); ?></title>
    </head>
    <body>
        <div id="container">
            <div id="containerwrapper">
                <div id="breadcrumbs">
<?php $this->echoBreadcrumbs(); ?>
                    <ul id="accountlinks">
                    <?php $this->echoAccountsMenu(); ?>
                    </ul>
                </div>
                <div id="header">
                    <div id="search">
<?php $this->echoSearchForm(); ?>
                    </div>
                    <h1>
<?php echo $this->getSiteTitle(); ?>
                    </h1>
                </div>
                <div id="hornav">
                    <ul>
<?php $this->echoTopMenu(); ?>
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
                    <div id="homepage_sidebar_1">
                    <?php $this->echoWidgets(2); ?>
                        &nbsp;
                    </div>
                    <div id="homepage_sidebar_2">
    <?php $this->echoWidgets(3); ?>
                        &nbsp;
                    </div>
<?php } ?>

                <div id="footer">
<?php $this->echoCopyright(); ?>
                </div>
            </div><!-- id="containerwrapper" -->
        </div><!-- id="container" -->
    </body>
</html>	

<?php
// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

use Rcms\Page\Page;

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" >

        <link href="<?php echo $this->getUrlTheme() . "main.css" ?>" rel="stylesheet" type="text/css" />
        <script src="<?php echo $this->getUrlJavaScripts() ?>tooltip.js"></script>
        <!--[if lte IE 8]>
            <script src="<?php echo $this->getUrlJavaScripts() ?>html5.js"></script>
        <![endif]-->
        <title><?php echo $this->getHeaderTitle(); ?></title>
    </head>
    <body>
        <div id="container">
            <div id="header">
                <h1>
<?php echo $this->getHeaderTitle(); ?>
                </h1>
                <div id="search">
<?php $this->echoSearchForm(); ?>
                </div>
                    <?php if ($this->isLoggedIn()) { ?>
                    <div id="account_label">
                    <?php $this->echoAccountLabel(); ?>
                        <div id="account_box">
                        <?php $this->echoAccountBox(80); ?>
                            <div style="clear:both"></div>
                        </div>
                    </div>
<?php } ?>
            </div> <!-- id="header" -->
            <div id="hornav">
                <ul>
<?php $this->echoTopMenu(); ?>
                </ul>
                    <?php if (!$this->isLoggedIn()) { ?>
                    <ul id="accountlinks">
                    <?php $this->echoAccountsMenu(); ?>
                    </ul>
                    <?php } ?>
            </div> <!-- id="hornav" -->
            <div <?php
                    if ($this->getPageType() === Page::TYPE_HOME) {
                        echo 'id="content"';
                    } elseif ($this->getPageType() === Page::TYPE_BACKSTAGE) {
                        echo 'id="contentadmin"';
                    } else { // dus $this->get_page_type() === Page::TYPE_NORMAL
                        echo 'id="contentwide"';
                    }
                    ?> >
                <!-- Einde header -->

<?php $this->echoPageContent(); ?>

                <!-- Begin footer -->

            </div><!-- id="content"/"contentwide" -->

<?php if ($this->getPageType() == Page::TYPE_HOME) { ?>
                <div id="sidebar">
                <?php $this->echoWidgets(2); ?>
                </div>
                <div id="nav">
<?php $this->echoWidgets(3); ?>
                </div>
                <?php } ?>

            <div id="footer">
<?php $this->echoCopyright(); ?>
            </div>
        </div><!-- id="container" -->
    </body>
</html>	

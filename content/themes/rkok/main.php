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
 
        <!-- Viewport that supports orientation changes -->
        <script>
            if (navigator.userAgent.indexOf("Trident") >= 0) {
                document.write('<meta name="viewport" content="width=device-width, user-scalable=no">');
            } else {
                document.write('<meta name="viewport" content="initial-scale=1">');
            }
        </script>
        <noscript>
            <meta name="viewport" content="initial-scale=1">
        </noscript>

        <link href="<?php echo $this->getUrlTheme() . "main.css" ?>" rel="stylesheet" type="text/css" />
        <script src="<?php echo $this->getUrlJavaScripts() ?>tooltip.js"></script>
        <!--[if lte IE 8]>
            <script src="<?php echo $this->getUrlJavaScripts() ?>html5.js"></script>
        <![endif]-->
        <title><?php echo $this->getSiteTitle(); ?></title>
    </head>
    <body <?php
if ($this->isLoggedIn()) {
    echo 'class="logged_in"';
}
?>>
        <div id="container">
            <div id="header">
                <h1> <?php echo $this->getSiteTitle(); ?> </h1>
                <ul id="main_menu">
                    <?php $this->echoTopMenu(); ?>
                </ul>
                <div id="search">
                    <?php $this->echoSearchForm(); ?>
                </div>
                <div id="account_label">
                    <?php $this->echoAccountLabel(); ?>
                    <div id="account_box">
                        <?php $this->echoAccountBox(); ?>
                        <div style="clear:both"></div>
                    </div>
                </div>
            </div>
            <div <?php
                        if ($this->getPageType() == "BACKSTAGE") {
                            echo 'id="contentadmin"';
                        } else {
                            echo 'id="content"';
                        }
                        ?>>
            <?php $this->echoPageContent(); ?>
            </div>

                <?php if ($this->getPageType() != "BACKSTAGE") { ?>
                <div id="sidebar">
    <?php $this->echoWidgets(2); ?>
                </div>
            <?php } ?>

            <div id="footer">
            <?php $this->echoCopyright(); ?>
            </div>
        </div>
    </body>
</html>	

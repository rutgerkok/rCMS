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
    <body <?php
    if ($this->isLoggedIn()) {
        echo 'class="logged_in"';
    }
    ?>>
        <header id="site_header">
            <div class="site_container">
                <h1> <?php echo $this->getHeaderTitle(); ?> </h1>
                <nav>
                    <ul id="main_menu">
                        <?php $this->echoTopMenu(); ?>
                    </ul>
                </nav>
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
        </header>
        <div class="site_container">
            <div <?php
            if ($this->getPageType() === Page::TYPE_HOME) {
                echo 'id="contenthome"';
            } else {
                echo 'id="content"';
            }
            ?>>

                <?php $this->echoPageContent(); ?>
            </div>
            <?php if ($this->getPageType() == Page::TYPE_HOME) { ?>
                <div id="sidebar">
                    <?php $this->echoWidgets(2); ?>
                </div>
            <?php } ?>
            <div style="clear:both"></div>
        </div>


        <footer id="site_footer">
            <div class="site_container">
                <?php $this->echoCopyright(); ?>
            </div>
        </footer>
    </body>
</html>	

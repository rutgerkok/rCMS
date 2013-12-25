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
        <meta name="viewport" content="width=device-width, user-scalable=no">
        <link href="<?php echo $this->get_url_theme() . "main.css" ?>" rel="stylesheet" type="text/css" />
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
            <?php $this->echo_copyright(); ?>
            </div>
        </div>
    </body>
</html>	

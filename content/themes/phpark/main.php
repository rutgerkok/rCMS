<?php

namespace Rcms\Extend\Theme;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

use Psr\Http\Message\StreamInterface;
use Rcms\Page\Page;
use Rcms\Theme\Theme;
use Rcms\Theme\ThemeElements;

class PhpTheme extends Theme {
    
     public function render(StreamInterface $stream, ThemeElements $elements) {
        $contentId = 'content';
        if ($elements->getPageType() === Page::TYPE_BACKSTAGE) {
            $contentId = 'contentadmin';
        } else if ($elements->getPageType() === Page::TYPE_NORMAL) {
            $contentId = 'contentwide';
        }

        $stream->write('
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" >

        <link href="' . $elements->getUrlTheme() . 'main.css" rel="stylesheet" type="text/css" />
        <script src="' . $elements->getUrlJavaScripts() . 'tooltip.js"></script>
        <!--[if lte IE 8]>
            <script src="' . $elements->getUrlJavaScripts() . 'html5.js"></script>
        <![endif]-->
        <title>' . $elements->getHeaderTitle() . '</title>
    </head>
    <body>
        <div id="container">
            <div id="header">
                <h1>' . $elements->getHeaderTitle() . '</h1>
                <div id="search">
                    '); $elements->writeSearchForm($stream); $stream->write('
                </div>

                ');
                if ($elements->isLoggedIn()) {
                    $stream->write('<div id="account_label">');
                    $elements->writeAccountLabel($stream);
                    $stream->write('<div id="account_box">');
                    $elements->writeAccountBox($stream, 80); 
                    $stream->write('<div style="clear:both"></div>');
                    $stream->write('</div>');
                    $stream->write('</div>');
                }
                $stream->write('
            </div> <!-- id="header" -->
            <div id="hornav">
                <ul>
                    '); $elements->writeTopMenu($stream); $stream->write('
                </ul>
                ');
                if (!$elements->isLoggedIn()) {
                    $stream->write('<ul id="accountlinks">');
                    $elements->writeAccountsMenu($stream);
                    $stream->write('</ul>');
                }
                $stream->write('
            </div> <!-- id="hornav" -->
            <div id="' . $contentId . '" >
                <!-- Einde header -->
                
                '); $elements->writePageContent($stream); $stream->write('

                <!-- Begin footer -->

            </div><!-- id="content"/"contentwide" -->
            ');
            if ($elements->getPageType() == Page::TYPE_HOME) {
                $stream->write('
                <div id="sidebar">
                    '); $elements->writeWidgets($stream, 2); $stream->write('
                </div>
                <div id="nav">
                    '); $elements->writeWidgets($stream, 3); $stream->write('
                </div>
                ');
            }

            $stream->write('
            <div id="footer">
                ' . $elements->getCopyright() . '
            </div>
        </div><!-- id="container" -->
    </body>
</html>
        ');
    }
}

return new PhpTheme();

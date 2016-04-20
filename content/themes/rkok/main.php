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

class RkokTheme extends Theme {

    private function renderHead(StreamInterface $stream, ThemeElements $elements) {
        $stream->write(<<<HTML
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" >

                <link href="{$elements->getUrlTheme()}main.css" rel="stylesheet" type="text/css" />
                <script src="{$elements->getUrlJavaScripts()}tooltip.js"></script>
                <title>{$elements->getHeaderTitle()}</title>
            </head>
HTML
        );
    }

    private function renderBody(StreamInterface $stream, ThemeElements $elements) {
        $bodyClass = "";
        if ($elements->isLoggedIn()) {
            $bodyClass.= " logged_in";
        }
        if ($elements->getPageType() == Page::TYPE_BACKSTAGE) {
            $bodyClass.= " backstage";
        }

        $stream->write('<body class="' . $bodyClass . '">');

        $this->renderHeader($stream, $elements);

        $stream->write('<div class="site_container">');
        $this->renderMainContent($stream, $elements);
        $this->renderWidgetsSidebar($stream, $elements);
        $stream->write('<div style="clear:both"></div>');
        $stream->write('</div>');

        $this->renderFooter($stream, $elements);

        $stream->write('</body>');
    }

    private function renderHeader(StreamInterface $stream,
            ThemeElements $elements) {
        $stream->write('
            <header id="site_header">
                <div class="site_container">
                    <h1>' . $elements->getHeaderTitle() . '</h1>
                    <nav>
                        <ul id="main_menu">
                            ');
                            $elements->writeTopMenu($stream);
                            $stream->write('
                        </ul>
                    </nav>
                    <div id="after_menu"></div>
                    <div id="search">
                        ');
                        $elements->writeSearchForm($stream);
                        $stream->write('
                    </div>
                    <div id="account_label">
                        ');
                        $elements->writeAccountLabel($stream);
                        $stream->write('
                        <div id="account_box">
                            ');
                            $elements->writeAccountBox($stream);
                            $stream->write('
                            <div style="clear:both"></div>
                        </div>
                    </div>
                </div>
            </header>
        ');
    }

    private function renderFooter(StreamInterface $stream,
            ThemeElements $elements) {
        $stream->write(<<<HTML
            <footer id="site_footer">
                <div class="site_container">
                    {$elements->getCopyright()}
                </div>
            </footer>
HTML
        );
    }

    private function renderMainContent(StreamInterface $stream,
            ThemeElements $elements) {
        $stream->write('<div id="content">');
        $elements->writePageContent($stream);
        $stream->write("</div>");
    }

    private function renderWidgetsSidebar(StreamInterface $stream,
            ThemeElements $elements) {
        if ($elements->getPageType() == Page::TYPE_BACKSTAGE) {
            return;
        }
        $stream->write('<aside id="sidebar">');
        $elements->writeWidgets($stream, 2);
        $stream->write('</aside>');
    }

    public function render(StreamInterface $stream, ThemeElements $elements) {
        $stream->write('<!DOCTYPE html><html>');
        $this->renderHead($stream, $elements);
        $this->renderBody($stream, $elements);
        $stream->write('</html>');
    }
}

return new RkokTheme();

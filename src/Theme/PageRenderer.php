<?php

namespace Rcms\Theme;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Config;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Page\Page;

/**
 * Context in which a theme operates. All methods that a theme can use for
 * outputting things are located here.
 */
class PageRenderer {

    /** @var Website The website instance. */
    private $website;

    /** @var string The theme used to render the page. */
    private $themeDirectoryName;

    /** @var Page The renderer of the page. */
    private $page;

    /** @var Request The request. */
    private $request;

    public function __construct(Website $website, Request $request, Page $page) {
        $this->website = $website;
        $this->request = $request;
        $this->themeDirectoryName = $website->getConfig()->get(Config::OPTION_THEME);
        $this->page = $page;
    }

    /**
     * Renders the page.
     * @param StreamInterface $stream The stream to render the page to.
     */
    public function render(StreamInterface $stream) {
        $themeUrl = $this->website->getThemeManager()->getUrlTheme($this->themeDirectoryName);
        $themeElements = new ThemeElements($this->website, $this->page, $this->request, $themeUrl);
        
        $theme = $this->website->getThemeManager()->getTheme($this->themeDirectoryName);
        $theme->render($stream, $themeElements);
    }

}

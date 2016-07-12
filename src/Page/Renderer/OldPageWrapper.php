<?php

namespace Rcms\Page\Renderer;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Page\Page;
use Rcms\Core\Website;

/**
 * Class intented to wrap legacy pages.
 */
class OldPageWrapper extends Page {

    /** @var string Title of the page. */
    private $title;

    /** @var string Path to the .inc file */
    private $file;

    /**
     * Creates a new page wrapper. Doesn't validate its arguments.
     * @param string $title Title of the page.
     * @param string $file .inc file of the page.
     */
    public function __construct($title, $file) {
        $this->title = $title;
        $this->file = $file;
    }

    public function getPageTitle(Text $text) {
        // Empty, as old pages already output their name as part of getPageContent
        return "";
    }

    public function getShortPageTitle(Text $text) {
        return $this->title;
    }

    public function getPageContent(Website $website, Request $request) {
        // Emulate some old superglobals
        $_GET["id"] = $request->getParamString(0);
        $_GET["p"] = $request->getPageName();
        $_POST["id"] = $_GET["id"];
        $_POST["p"] = $_GET["p"];
        $_REQUEST["id"] = $_GET["id"];
        $_REQUEST["p"] = $_GET["p"];
 
        ob_start();
        $website->execute($this->file);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        // The old pages do their own access checks
        return Authentication::RANK_LOGGED_OUT;
    }

}

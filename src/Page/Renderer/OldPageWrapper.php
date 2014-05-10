<?php

namespace Rcms\Page\Renderer;

use Rcms\Core\Request;
use Rcms\Page\Page;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

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

    public function getPageTitle(Request $request) {
        // Empty, as old pages already output their name as part of getPageContent
        return "";
    }

    public function getShortPageTitle(Request $request) {
        return $this->title;
    }

    public function getPageContent(Request $request) {
        ob_start();
        $request->getWebsite()->execute($this->file);
        return ob_get_clean();
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

}

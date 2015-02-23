<?php

namespace Rcms\Core\Widget;

use Rcms\Core\InfoFile;

/**
 * Stores metadata about a widget definition, comes from a widget info file.
 */
class WidgetInfoFile extends InfoFile {

    private $widgetName;

    public function __construct($widgetName, $infoFile) {
        parent::__construct($infoFile);
        $this->widgetName = $widgetName;
    }

    public function getName() {
        return $this->getString("name", $this->widgetName);
    }

    public function getDescription() {
        return $this->getString("description", "No description given");
    }

    public function getDirectoryName() {
        return $this->widgetName;
    }

    public function getVersion() {
        return $this->getString("version", "0.0.1");
    }

    public function getAuthor() {
        return $this->getString("author", "Unknown");
    }

    public function getAuthorWebsite() {
        return $this->getString("author.website");
    }

    public function getWidgetWebsite() {
        return $this->getString("website");
    }

}

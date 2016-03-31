<?php

namespace Rcms\Core\Widget;

use Rcms\Core\InfoFile;

/**
 * Stores metadata about a widget definition, comes from a widget info file.
 */
final class WidgetInfoFile {

    private $widgetName;
    /**
     * @var InfoFile File with metadata of the widget.
     */
    private $infoFile;

    public function __construct($widgetName, InfoFile $infoFile) {
        $this->widgetName = $widgetName;
        $this->infoFile = $infoFile;
    }

    public function getName() {
        return $this->infoFile->getString("name", $this->widgetName);
    }

    public function getDescription() {
        return $this->infoFile->getString("description", "No description given");
    }

    public function getDirectoryName() {
        return $this->widgetName;
    }

    public function getVersion() {
        return $this->infoFile->getString("version", "0.0.1");
    }

    public function getAuthor() {
        return $this->getString("author", "Unknown");
    }

    public function getAuthorWebsite() {
        return $this->infoFile->getString("author.website");
    }

    public function getWidgetWebsite() {
        return $this->infoFile->getString("website");
    }

}

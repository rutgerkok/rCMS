<?php

/**
 * Stores info about a widget. All methods return strings.
 */
class WidgetInfoFile extends InfoFile {

    private $directoryName;

    public function __construct($directoryName, $infoFile) {
        parent::__construct($infoFile);
        $this->directoryName = $directoryName;
        $this->infoFile = $infoFile;
    }

    public function getName() {
        return $this->getString("name", $this->directoryName);
    }

    public function getDescription() {
        return $this->getString("description", "No description given");
    }

    public function getDirectoryName() {
        return $this->directoryName;
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

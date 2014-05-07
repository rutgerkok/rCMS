<?php

/**
 * An InfoFile is a file that contains information about a piece of software on
 * the site, like a file describing a widget.
 */
abstract class InfoFile {

    private $inited;
    protected $infoFile;
    protected $settingsMap;

    protected function __construct($file) {
        $this->infoFile = $file;
    }

    /**
     * Gets the setting with the given key (name). If the setting isn't found,
     * the default value is returned.
     * @param string $key Key of the setting.
     * @param string $default Default value of the setting.
     * @return string The value of the setting, or the default.
     */
    protected function getString($key, $default = "") {
        $this->readFile();
        if (isSet($this->settingsMap[$key])) {
            return $this->settingsMap[$key];
        } else {
            return $default;
        }
    }

    /**
     * Gets the integer with the given key (name). If the integer is not found,
     * or isn't actually an integer, the default value is returned.
     * @param string $key Key of the setting.
     * @param int $default Default value of the setting.
     * @return int The integer, or the default value.
     */
    protected function getInteger($key, $default = 0) {
        $string = $this->getString($key, $default);
        $number = (int) $string;
        if ($string != $number) {
            // Information was lost when casting
            // So value is not an int
            return $default;
        }
        return $number;
    }

    /**
     * Reads all settings in the file. If the settings have already been read,
     * this method does nothing.
     */
    protected function readFile() {
        // Only read once
        if (($this->inited)) {
            return;
        }

        // Check for file
        if (!file_exists($this->infoFile)) {
            $this->name = "Missing file";
            $this->description = $this->infoFile . " not found.";
            return;
        }

        // Initialise settings map
        $this->settingsMap = array();

        // Read all lines
        $lines = file($this->infoFile);
        foreach ($lines as $line) {
            $split = explode("=", $line, 2);
            if (count($split) != 2) {
                continue;
            }
            $key = trim($split[0]);
            $value = trim($split[1]);
            $this->settingsMap[$key] = $value;
        }
    }

}

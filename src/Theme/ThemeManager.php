<?php

namespace Rcms\Theme;

use BadMethodCallException;
use Rcms\Core\BulkFileSystem;
use Rcms\Core\Config;
use Rcms\Core\InfoFile;
use Rcms\Core\Website;
use RuntimeException;

class ThemeManager {

    const THEME_INFO_FILE_NAME = "info.txt";
    const TEMPORARY_THEME = "temp";

    /**
     * @var Website The website object.
     */
    private $website;

    public function __construct(Website $website) {
        $this->website = $website;
    }

    /**
     * Gets the ThemeMeta of all themes that exist. In other words, this
     * method returns all possible values for which self::themeExists returns
     * true.
     * @return string[] All theme directory names.
     */
    public function getAllThemes() {
        $themesDir = $this->website->getUriThemes();

        $rawFiles = scanDir($themesDir);
        $allThemeDirs = array_filter($rawFiles, function ($fileName) use ($themesDir) {
            if ($fileName[0] === '.') {
                // Directories starting with . should be ignored. This includes
                //  "./", "../", ".somehiddentheme/"
                return false;
            }
            if (!$this->themeExists($fileName)) {
                // Only include theme directories, not files
                return false;
            }

            return true;
        });

        return array_map(function($themeName) {
            return $this->getThemeMeta($themeName);
        }, $allThemeDirs);
    }

    /**
     * Loads the theme with the given directory name.
     * @param string $themeName The directory name, like "my_theme".
     * @return ThemeMeta The loaded theme.
     * @throws BadMethodCallException If no theme with that name exists.
     */
    private function getThemeMeta($themeName) {
        $themeDirectory = $this->website->getUriThemes() . $themeName . "/";
        if (!is_dir($themeDirectory)) {
            throw new BadMethodCallException("Theme {$themeName} doesn't exist");
        }
        $themeInfoFile = new InfoFile($themeDirectory . self::THEME_INFO_FILE_NAME);
        return new ThemeMeta($themeName, $themeInfoFile);
    }

    /**
     * Gets whether a theme with that name exists on this site.
     * @param string $directoryName Name of the directory of the theme, like
     *  "my_theme".
     * @return boolean Whether that theme exists.
     */
    public function themeExists($directoryName) {
        if (!is_string($directoryName)
            || !preg_match("/^[a-z0-9_\\-]+$/", $directoryName)) {
            // Path is not a string or contains special characters
            // So it is surely not a theme, and checking it wouldn't
            // even be safe
            return false;
        }
        if (strToLower($directoryName) == self::TEMPORARY_THEME) {
            // Ignore the temporary installation theme
            return false;
        }

        return is_dir($this->getUriTheme($directoryName));
    }

    /**
     * Gets the {@link Theme} instance of the theme with the given name.
     * @param string $themeDirectoryName The theme directory name.
     * @return Theme The theme file.
     * @throws RuntimeException If no valid theme with that name exists.
     */
    public function getTheme($themeDirectoryName) {
        if (!$this->themeExists($themeDirectoryName) && $themeDirectoryName != self::TEMPORARY_THEME) {
            throw new RuntimeException("Theme directory does not exist");
        }
        $themeFile = $this->getUriTheme($themeDirectoryName) . "main.php";
        if (!file_exists($themeFile)) {
            throw new RuntimeException("Theme file does not exist");
        }

        $theme = require $themeFile;
        if (!($theme instanceof Theme)) {
            throw new RuntimeException("Theme file doesn't return a Theme instance");
        }
 
        return $theme;
    }

    /**
     * Gets the theme currently used on the site.
     * @return ThemeMeta The theme.
     */
    public function getCurrentThemeMeta() {
        return $this->getThemeMeta($this->website->getConfig()->get(Config::OPTION_THEME));
    }

    /**
     * Checks if we can switch to another theme. When the directory containing
     * the active theme is not writeable, we cannot switch themes.
     * cannot switch to another theme.
     * @return bool True if we can switch to another theme, false otherwise.
     */
    public function canSwitchThemes() {
        return is_writeable($this->website->getUriActiveTheme());
    }
    
    /**
     * Changes the active theme to the given theme.
     * @param string $themeDirectoryName The name of the new theme, like "forest".
     * @throws BadMethodCallException If the theme name is invalid.
     * @throws RuntimeException If the file system is read-only.
     */
    public function setActiveTheme($themeDirectoryName) {
        if (!$this->themeExists($themeDirectoryName)) {
            throw new BadMethodCallException("Given theme does not exist");
        }
        if (!$this->canSwitchThemes()) {
            throw new RuntimeException("Cannot change theme, lacking file permissions");
        }

        $publicThemeDir = $this->website->getUriActiveTheme();
        $internalThemeDir = $this->getUriTheme($themeDirectoryName) . "web/";
        if (!file_exists($internalThemeDir)) {

        }

        $fileSystem = new BulkFileSystem();
        $fileSystem->clearDirectory($publicThemeDir);
        $fileSystem->copyFiles($internalThemeDir, $publicThemeDir);
        $this->website->getConfig()->set($this->website->getDatabase(), Config::OPTION_THEME, $themeDirectoryName);
    }

    /**
     * Gets the uri of theme directory.
     * @param string $themeDirectoryName The theme directory name.
     * @return string The uri.
     */
    private function getUriTheme($themeDirectoryName) {
        return $this->website->getUriThemes() . $themeDirectoryName . "/";
    }

}

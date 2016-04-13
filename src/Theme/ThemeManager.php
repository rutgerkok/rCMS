<?php

namespace Rcms\Theme;

use Rcms\Core\InfoFile;
use Rcms\Core\Website;

use BadMethodCallException;

class ThemeManager {

    const THEME_INFO_FILE_NAME = "info.txt";

    /**
     * @var Website The website object.
     */
    private $website;

    public function __construct(Website $website) {
        $this->website = $website;
    }

    /**
     * Gets the directory name of all themes that exist. In other words, this
     * method returns all possible values for which self::themeExists returns
     * true.
     * @return string[] All theme directory names.
     */
    public function getAllThemeNames() {
        $themesDir = $this->website->getUriThemes();

        $rawFiles = scanDir($themesDir);
        return array_filter($rawFiles, function ($fileName) use ($themesDir) {
            if ($fileName[0] === '.') {
                // Directories starting with . should be ignored. This includes
                //  "./", "../", ".somehiddentheme/"
                return false;
            }
            if (!is_dir($themesDir . $fileName)) {
                // Only include directories
                return false;
            }

            return true;
        });
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
        return is_dir($this->website->getUriThemes() . $directoryName);
    }

    /**
     * Gets the main theme file.
     * @param string $themeDirectoryName The theme directory name.
     */
    public function getMainFile($themeDirectoryName) {
        return $this->getUriTheme($themeDirectoryName) . "main.php";
    }

    /**
     * Gets the theme currently used on the site.
     * @return ThemeMeta The theme.
     */
    public function getCurrentTheme() {
        return $this->getThemeMeta($website->getConfig()->get(Config::OPTION_THEME));
    }

    /**
     * Gets the uri of theme directory.
     * @param string $themeDirectoryName The theme directory name.
     * @return string The uri.
     */
    private function getUriTheme($themeDirectoryName) {
        return $this->website->getUriThemes() . $themeDirectoryName . "/";
    }

    /**
     * Gets the url of theme directory.
     * @param string $themeDirectoryName The theme directory name.
     * @return string The url.
     */
    public function getUrlTheme($themeDirectoryName) {
        return $this->website->getUrlThemes() . $themeDirectoryName . "/";
    }

}

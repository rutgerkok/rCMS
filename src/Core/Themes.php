<?php

namespace Rcms\Core;

class Themes {

    const THEME_INFO_FILE_NAME = "info.txt";

    private $websiteObject; //bewaart het website-object
    private $theme;

    public function __construct(Website $oWebsite) {
        $this->websiteObject = $oWebsite;

        // Load theme info file
        $this->theme = $this->loadTheme($oWebsite->getConfig()->get("theme"));
    }

    /**
     * Loads the theme with the given directory name.
     * @param string $themeName The directory name, like "my_theme".
     * @return Theme The loaded theme.
     * @throws BadMethodCallException If no theme with that name exists.
     */
    private function loadTheme($themeName) {
        $themeDirectory = $this->websiteObject->getUriThemes() . $themeName . "/";
        $themeInfoFile = $themeDirectory . self::THEME_INFO_FILE_NAME;
        return new Theme($themeName, $themeInfoFile);
    }

    /**
     * Gets the theme with the given name.
     * @param string $directoryName Name of the directory of the theme,
     *  like "my_theme".
     * @return Theme|null The theme, or null if not found.
     */
    public function getTheme($directoryName) {
        if ($this->theme->getName() == $directoryName) {
            return $this->theme;
        }
        if ($this->themeExists($directoryName)) {
            return loadTheme($directoryName);
        }
        return null;
    }

    /**
     * Gets whether a theme with that name exists on this site.
     * @param string $directoryName Name of the directory of the theme, like
     *  "my_theme".
     * @return boolean Whether that theme exists.
     */
    public function themeExists($directoryName) {
        return is_dir($this->websiteObject->getUriThemes() . $directoryName);
    }

    /**
     * Gets the main.php file that echoes the whole page.
     * @param Theme $theme The theme.
     */
    public function getThemeFile(Theme $theme) {
        return $this->getUriTheme($theme) . "main.php";
    }

    /**
     * Gets the theme currently used on the site.
     * @return Theme The theme.
     */
    public function getCurrentTheme() {
        return $this->theme;
    }

    /**
     * Gets the uri of theme directory.
     * @param Theme $theme The theme to get the uri for, use null for the
     *  current theme.
     * @return string The uri.
     */
    public function getUriTheme($theme = null) {
        if ($theme == null) {
            $theme = $this->theme;
        }
        return $this->websiteObject->getUriThemes() . $theme->getName() . "/";
    }

    /**
     * Gets the url of theme directory.
     * @param Theme $theme The theme to get the url for, use null for the
     *  current theme.
     * @return string The uri.
     */
    public function getUrlTheme($theme = null) {
        if ($theme == null) {
            $theme = $this->theme;
        }
        return $this->websiteObject->getUrlThemes() . $theme->getName() . "/";
    }

}

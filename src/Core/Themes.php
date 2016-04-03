<?php

namespace Rcms\Core;

class Themes {

    const THEME_INFO_FILE_NAME = "info.txt";

    private $website; //bewaart het website-object
    private $theme;

    public function __construct(Website $website) {
        $this->website = $website;

        // Load theme info file
        $this->theme = $this->getThemeMeta($website->getConfig()->get("theme"));
    }

    /**
     * Loads the theme with the given directory name.
     * @param string $themeName The directory name, like "my_theme".
     * @return ThemeMeta The loaded theme.
     * @throws BadMethodCallException If no theme with that name exists.
     */
    private function getThemeMeta($themeName) {
        $themeDirectory = $this->website->getUriThemes() . $themeName . "/";
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
     * Gets the main.php file that echoes the whole page.
     * @param ThemeMeta $theme The theme.
     */
    public function getThemeFile(ThemeMeta $theme) {
        return $this->getUriTheme($theme) . "main.php";
    }

    /**
     * Gets the theme currently used on the site.
     * @return ThemeMeta The theme.
     */
    public function getCurrentTheme() {
        return $this->theme;
    }

    /**
     * Gets the uri of theme directory.
     * @param ThemeMeta $theme The theme to get the uri for, use null for the
     *  current theme.
     * @return string The uri.
     */
    public function getUriTheme($theme = null) {
        if ($theme == null) {
            $theme = $this->theme;
        }
        return $this->website->getUriThemes() . $theme->getName() . "/";
    }

    /**
     * Gets the url of theme directory.
     * @param ThemeMeta $theme The theme to get the url for, use null for the
     *  current theme.
     * @return string The uri.
     */
    public function getUrlTheme($theme = null) {
        if ($theme == null) {
            $theme = $this->theme;
        }
        return $this->website->getUrlThemes() . $theme->getName() . "/";
    }

}

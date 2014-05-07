<?php

class Theme extends InfoFile {

    private $name;

    /**
     * Loads a theme.
     * @param string $name Name of the (directory of the) theme.
     * @param string $file Path to the info.txt file of the theme.
     * @throws BadMethodCallException If the info.txt file doesn't exist.
     */
    public function __construct($name, $file) {
        parent::__construct($file);
        if (!file_exists($file)) {
            throw new BadMethodCallException("The file " . htmlSpecialChars($file) . " doesn't exist.");
        }
        $this->name = $name;
    }

    /**
     * Gets the (directory) name of this theme.
     * @return string The name of this theme.
     */
    public function getName() {
        return $this->name;
    }

    /**
     * The function should return an array (id=>name) with all places for widgets.
     * Ids must be a whole number larger than 1. (Id 0 is unused, id 1 is used on homepage)
     * @param Website $oWebsite The website object, used for translations.
     */
    public function getWidgetAreas(Website $oWebsite) {
        // Get the number of widgets
        $areas = $this->getInteger("widget_areas", 1);

        if ($areas == 0) {
            // No widgets in this theme
            return array();
        } elseif ($areas == 1) {
            // One widget area on position 2
            return array(
                2 => $oWebsite->t("widgets.sidebar")
            );
        } else {
            // More widget areas, starting on position 2
            $widgetAreas = array();
            for ($i = 0; $i < $areas; $i++) {
                $widgetAreas[$i + 2] = $oWebsite->t("widgets.sidebar") . " " . ($i + 1);
            }
            return $widgetAreas;
        }
    }

    /**
     * Gets the color of the CKEditor menu bar.
     * @return string The color, in the format #aaaaaa.
     */
    public function getTextEditorColor() {
        return $this->getString("editor_color", "#cccccc");
    }

    /**
     * Gets the relative url to the stylesheet containing all the rules for 
     * text styling. This is used for the text editor. The url is relative
     * to the directory of this theme.
     */
    public function getTextStylesheet() {
        return $this->getString("styles.text", "main.css");
    }

    /**
     * When the user doesn't have access to the site for whatever reason
     * (no access key, site is down, etc.) a simple page is shown. When it is
     * still possible to load the theme, this stylesheet is used for that page.
     */
    public function getErrorPageStylesheet() {
        return $this->getString("styles.error_page", "main.css");
    }

}

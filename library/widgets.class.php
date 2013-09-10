<?php

/**
 * Widget manager
 */
class Widgets {

    // Reference to the Website.
    private $websiteObject;
    // Cache of all loaded widgets for this page
    private static $loadedWidgets = array();
    // Temporary variable to store the directory name when echoeing the widgets
    private $widgetDirectoryName;

    public function __construct(Website $oWebsite) {
        $this->websiteObject = $oWebsite;
    }

    /**
     * Gets a list of all installed widgets.
     * @return \WidgetInfo List of all installed widgets.
     */
    public function getInstalledWidgets() {
        $widgets = array();
        $directoryToScan = $this->websiteObject->getUriWidgets();
        if (is_dir($directoryToScan)) {
            $files = scanDir($directoryToScan);
            foreach ($files as $file) {
                if ($file{0} != '.') {
                    // Ignore hidden files and directories above this one
                    if (is_dir($directoryToScan . $file)) {
                        $widgets[] = new WidgetInfo($file, $directoryToScan . $file . "/info.txt");
                    }
                }
            }
        }
        return $widgets;
    }

    /**
     * Shortcut to retrieve the widget areas. Also includes the home page as
     * an option.
     * @return array Array of widget, (numeric) id => name
     */
    public function getWidgetAreas() {
        $areas = $this->websiteObject->getThemeManager()->get_theme()->getWidgetAreas($this->websiteObject);
        $areas[1] = $this->websiteObject->t("widgets.homepage");
        return $areas;
    }

    /**
     * Returns a list of PlacedWidgets for the given sidebar.
     * @param int $sidebar_id The id of the sidebar.
     * @return \PlacedWidget List of placed widgets.
     */
    public function getPlacedWidgetsFromSidebar($sidebar_id) {
        $oWebsite = $this->websiteObject;
        $oDB = $oWebsite->getDatabase();

        $sidebar_id = (int) $sidebar_id;
        $widgets_directory = $oWebsite->getUriWidgets();

        $widgets = array();

        $result = $oDB->query("SELECT `widget_id`, `widget_naam`, `widget_data`, `widget_priority` FROM `widgets` WHERE `sidebar_id` = $sidebar_id ORDER BY `widget_priority` DESC");

        while (list($id, $name, $data, $priority) = $oDB->fetchNumeric($result)) {
            $widgets[] = new PlacedWidget($id, $sidebar_id, $name, $data, $priority, $widgets_directory . "/" . $name);
        }

        return $widgets;
    }

    /**
     * Searches the database for the widget with the given id.
     * @param int $widget_id The id of the widget.
     * @return PlacedWidget|null The placed widget, or null if it isn't found.
     */
    public function getPlacedWidget($widget_id) {
        $widget_id = (int) $widget_id;
        $oWebsite = $this->websiteObject;
        $oDB = $oWebsite->getDatabase();
        $result = $oDB->query("SELECT `widget_naam`, `widget_data`, `widget_priority`, `sidebar_id` FROM `widgets` WHERE `widget_id` = $widget_id");
        if ($result && $oDB->rows($result) > 0) {
            list($name, $data, $priority, $sidebar_id) = $oDB->fetchNumeric($result);
            return new PlacedWidget($widget_id, $sidebar_id, $name, $data, $priority, $oWebsite->getUriWidgets() . "/" . $name);
        } else {
            return null;
        }
    }

    /**
     * Returns the widget the give directory.
     * @param string $widgetDirectoryName Directory name. Do not include the full path.
     * @return WidgetDefinition The widget, or null if not found.
     */
    public function getWidgetDefinition($widgetDirectoryName) {
        if (!isSet(self::$loadedWidgets[$widgetDirectoryName])) {
            $this->widgetDirectoryName = $widgetDirectoryName;
            $file = $this->websiteObject->getUriWidgets() . "/" . $widgetDirectoryName . "/main.php";
            if (file_exists($file)) {
                require($file);
            }
        }
        if (isSet(self::$loadedWidgets[$widgetDirectoryName])) {
            return self::$loadedWidgets[$widgetDirectoryName];
        } else {
            return null;
        }
    }

    // Echoes all widgets in the specified sidebar
    public function getWidgetsSidebar($sidebarId) {
        $oWebsite = $this->websiteObject;
        $oDB = $oWebsite->getDatabase();
        $loggedInAsAdmin = $oWebsite->isLoggedInAsStaff(true);
        $output = "";
        $sidebarId = (int) $sidebarId;

        // Get all widgets that should be displayed
        $result = $oDB->query("SELECT `widget_id`, `widget_naam`, `widget_data` FROM `widgets` WHERE `sidebar_id` = $sidebarId  ORDER BY `widget_priority` DESC");

        while (list($id, $directory_name, $data) = $oDB->fetchNumeric($result)) {
            $this->widgetDirectoryName = $directory_name;

            // Try to load it if it isn't loaded yet
            if (!isSet(Widgets::$loadedWidgets[$directory_name])) {
                $file = $oWebsite->getUriWidgets() . $directory_name . "/main.php";
                if (file_exists($file)) {
                    require($file);
                } else {
                    $oWebsite->addError("The widget $directory_name (id=$id) was not found. File <code>$file</code> was missing.", "A widget was missing.");
                }
            }

            // Check if load was succesfull. Display widget or display error.
            if (isSet(Widgets::$loadedWidgets[$directory_name])) {
                $output.= Widgets::$loadedWidgets[$directory_name]->getWidget($this->websiteObject, $id, JSONHelper::stringToArray($data));
                if ($loggedInAsAdmin) {
                    // Links for editing and deleting
                    $output.= "<p>\n";
                    $output.= '<a class="arrow" href="' . $oWebsite->getUrlPage("edit_widget", $id) . '">' . $oWebsite->t("main.edit") . " " . $oWebsite->t("main.widget") . '</a> ';
                    $output.= '<a class="arrow" href="' . $oWebsite->getUrlPage("delete_widget", $id) . '">' . $oWebsite->t("main.delete") . " " . $oWebsite->t("main.widget") . '</a> ';
                    $output.= "</p>\n";
                }
            } else {
                $oWebsite->addError("The widget $directory_name (id=$id) could not be loaded. File <code>$file</code> is incorrect.", "A widget was missing.");
            }
        }

        // Link to manage widgets
        if ($loggedInAsAdmin) {
            $output.= '<p><a class="arrow" href="' . $oWebsite->getUrlPage("widgets") . '">';
            $output.= $oWebsite->t("main.manage") . " " . strToLower($oWebsite->t("main.widgets"));
            $output.= "</a></p>\n";
        }
        
        return $output;
    }

    /**
     * Registers a widget. Widget files should call this.
     * @param WidgetDefinition $widget The widget to register.
     */
    public function registerWidget(WidgetDefinition $widget) {
        self::$loadedWidgets[$this->widgetDirectoryName] = $widget;
    }

}

/**
 * Holds the code of a widget.
 */
abstract class WidgetDefinition {

    /**
     * Gets the text of the widget.
     * @param Website $oWebsite The currently used website.
     * @param int $id The unique id of the widget.
     * @param array $data All data attached to the widget, key->value pairs.
     * @return string The text.
     */
    public abstract function getWidget(Website $oWebsite, $id, $data);

    /**
     * Gets the widget's editor. The data is either the saved data, or the data
     * just returned from parse_data (even if that was marked as invalid!)
     * @return string The editor.
     */
    public abstract function getEditor(Website $oWebsite, $id, $data);

    /**
     * Parses all input created by get_editor. You'll have to use the $_REQUEST
     * array. Make sure to sanitize your input, but don't escape it, that will
     * be done for your!
     * 
     * If the data is invalid set $return_array["valid"] to false. If you want
     * to give any feedback to the user, use $oWebsite->add_error(message).
     * 
     * @param Website $oWebsite The currently used website.
     * @param int $id The unique id of the widget.
     * @return array Array of all data.
     */
    public abstract function parseData(Website $oWebsite, $id);
}

/**
 * Stores info about a widget. All methods return strings.
 */
class WidgetInfo {

    private $name;
    private $description;
    private $version;
    private $author;
    private $authorWebsite;
    private $website;
    private $directoryName;
    private $infoFile;

    public function __construct($directoryName, $infoFile) {
        $this->directoryName = $directoryName;
        $this->infoFile = $infoFile;
    }

    public function getName() {
        $this->init();
        return $this->name;
    }

    public function getDescription() {
        $this->init();
        return $this->description;
    }

    public function getDirectoryName() {
        return $this->directoryName;
    }

    public function getVersion() {
        $this->init();
        return $this->version;
    }

    public function getAuthor() {
        $this->init();
        return $this->author;
    }

    public function getAuthorWebsite() {
        $this->init();
        return $this->authorWebsite;
    }

    public function getWidgetWebsite() {
        $this->init();
        return $this->website;
    }

    private function init() {
        if (isSet($this->name)) {
            return;
        }

        if (file_exists($this->infoFile)) {
            $lines = file($this->infoFile);
            foreach ($lines as $line) {
                $split = explode("=", $line, 2);
                if (count($split) < 2) {
                    continue;
                }
                if ($split[0] == "author") {
                    $this->author = $split[1];
                }
                if ($split[0] == "author.website") {
                    $this->authorWebsite = $split[1];
                }
                if ($split[0] == "name") {
                    $this->name = $split[1];
                }
                if ($split[0] == "description") {
                    $this->description = $split[1];
                }
                if ($split[0] == "version") {
                    $this->version = $split[1];
                }
                if ($split[0] == "website") {
                    $this->website = $split[1];
                }
            }
        } else {
            $this->name = $this->directoryName;
            $this->description = "info.txt not found.";
        }
    }

}

?>
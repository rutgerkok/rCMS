<?php

namespace Rcms\Core;

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
     * @return WidgetInfoFile List of all installed widgets.
     */
    public function getInstalledWidgets() {
        $widgets = array();
        $directoryToScan = $this->websiteObject->getUriWidgets();

        // Check directory
        if (!is_dir($directoryToScan)) {
            return;
        }

        // Scan it
        $files = scanDir($directoryToScan);
        foreach ($files as $file) {
            if ($file{0} != '.') {
                // Ignore hidden files and directories above this one
                if (is_dir($directoryToScan . $file)) {
                    $widgets[] = new WidgetInfoFile($file, $directoryToScan . $file . "/info.txt");
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
        $areas = $this->websiteObject->getThemeManager()->getCurrentTheme()->getWidgetAreas($this->websiteObject);
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
    public function getWidgetsHTML($sidebarId) {
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
                $output.= Widgets::$loadedWidgets[$directory_name]->getWidget($this->websiteObject, $id, JsonHelper::stringToArray($data));
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

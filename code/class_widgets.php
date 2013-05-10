<?php

/**
 * Widget manager
 */
class Widgets {

    // Reference to the Website.
    private $website_object;
    // Cache of all loaded widgets for this page
    private static $loaded_widgets = array();
    // Temporary variable to store the directory name when echoeing the widgets
    private $widget_directory_name;

    public function __construct(Website $oWebsite) {
        $this->website_object = $oWebsite;
    }

    /**
     * Gets a list of all installed widgets.
     * @return \WidgetInfo List of all installed widgets.
     */
    public function get_widgets_installed() {
        $widgets = array();
        $directory_to_scan = $this->website_object->get_uri_widgets();
        if (is_dir($directory_to_scan)) {
            $files = scandir($directory_to_scan);
            foreach ($files as $file) {
                if ($file{0} != '.') {
                    // Ignore hidden files and directories above this one
                    if (is_dir($directory_to_scan . $file)) {
                        $widgets[] = new WidgetInfo($file, $directory_to_scan . $file . "/info.txt");
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
    public function get_widget_areas() {
        $areas = $this->website_object->get_theme_manager()->get_theme()->get_widget_areas($this->website_object);
        $areas[1] = $this->website_object->t("widgets.homepage");
        return $areas;
    }

    /**
     * Returns a list of PlacedWidgets for the given sidebar.
     * @param int $sidebar_id The id of the sidebar.
     * @return \PlacedWidget List of placed widgets.
     */
    public function get_placed_widgets_from_sidebar($sidebar_id) {
        $oWebsite = $this->website_object;
        $oDB = $oWebsite->get_database();

        $sidebar_id = (int) $sidebar_id;
        $widgets_directory = $oWebsite->get_uri_widgets();

        $widgets = array();

        $result = $oDB->query("SELECT `widget_id`, `widget_naam`, `widget_data`, `widget_priority` FROM `widgets` WHERE `sidebar_id` = $sidebar_id ORDER BY `widget_priority` DESC");

        while (list($id, $name, $data, $priority) = $oDB->fetch($result)) {
            $widgets[] = new PlacedWidget($id, $sidebar_id, $name, $data, $priority, $widgets_directory . "/" . $name);
        }

        return $widgets;
    }

    /**
     * Searches the database for the widget with the given id.
     * @param int $widget_id The id of the widget.
     * @return PlacedWidget|null The placed widget, or null if it isn't found.
     */
    public function get_placed_widget($widget_id) {
        $widget_id = (int) $widget_id;
        $oWebsite = $this->website_object;
        $oDB = $oWebsite->get_database();
        $result = $oDB->query("SELECT `widget_naam`, `widget_data`, `widget_priority`, `sidebar_id` FROM `widgets` WHERE `widget_id` = $widget_id");
        if ($result && $oDB->rows($result) > 0) {
            list($name, $data, $priority, $sidebar_id) = $oDB->fetch($result);
            return new PlacedWidget($widget_id, $sidebar_id, $name, $data, $priority, $oWebsite->get_uri_widgets() . "/" . $name);
        } else {
            return null;
        }
    }

    /**
     * Returns the widget the give directory.
     * @param string $widget_directory_name Directory name. Do not include the full path.
     * @return WidgetDefinition The widget, or null if not found.
     */
    public function get_widget_definition($widget_directory_name) {
        if (!isset(self::$loaded_widgets[$widget_directory_name])) {
            $this->widget_directory_name = $widget_directory_name;
            $file = $this->website_object->get_uri_widgets() . "/" . $widget_directory_name . "/main.php";
            if (file_exists($file)) {
                require($file);
            }
        }
        if (isset(self::$loaded_widgets[$widget_directory_name])) {
            return self::$loaded_widgets[$widget_directory_name];
        } else {
            return null;
        }
    }

    // Echoes all widgets in the specified sidebar
    public function echo_widgets_sidebar($sidebar_id) {
        $oWebsite = $this->website_object;
        $oDB = $oWebsite->get_database();
        $logged_in_admin = $oWebsite->logged_in_staff(true);

        $sidebar_id = (int) $sidebar_id;

        // Get all widgets that should be displayed
        $result = $oDB->query("SELECT `widget_id`, `widget_naam`, `widget_data` FROM `widgets` WHERE `sidebar_id` = $sidebar_id  ORDER BY `widget_priority` DESC");

        while (list($id, $directory_name, $data) = $oDB->fetch($result)) {
            $this->widget_directory_name = $directory_name;

            // Try to load it if it isn't loaded yet
            if (!isset(Widgets::$loaded_widgets[$directory_name])) {
                $file = $oWebsite->get_uri_widgets() . $directory_name . "/main.php";
                if (file_exists($file)) {
                    require($file);
                } else {
                    $oWebsite->add_error("The widget $directory_name (id=$id) was not found. File <code>$file</code> was missing.", "A widget was missing.");
                }
            }

            // Check if load was succesfull. Display widget or display error.
            if (isset(Widgets::$loaded_widgets[$directory_name])) {
                echo Widgets::$loaded_widgets[$directory_name]->get_widget($this->website_object, $id, json_decode($data, true));
                if ($logged_in_admin) {
                    // Links for editing and deleting
                    echo "<p>\n";
                    echo '<a class="arrow" href="' . $oWebsite->get_url_page("edit_widget", $id) . '">' . $oWebsite->t("main.edit") . " " . $oWebsite->t("main.widget") . '</a> ';
                    echo '<a class="arrow" href="' . $oWebsite->get_url_page("delete_widget", $id) . '">' . $oWebsite->t("main.delete") . " " . $oWebsite->t("main.widget") . '</a> ';
                    echo "</p>\n";
                }
            } else {
                $oWebsite->add_error("The widget $directory_name (id=$id) could not be loaded. File <code>$file</code> is incorrect.", "A widget was missing.");
            }
        }

        // Link to manage widgets
        if ($logged_in_admin) {
            echo '<p><a class="arrow" href="' . $oWebsite->get_url_page("widgets") . '">';
            echo $oWebsite->t("main.manage") . " " . strtolower($oWebsite->t("main.widgets"));
            echo "</a></p>\n";
        }
    }

    /**
     * Registers a widget. Widget files should call this.
     * @param WidgetDefinition $widget The widget to register.
     */
    public function register_widget(WidgetDefinition $widget) {
        self::$loaded_widgets[$this->widget_directory_name] = $widget;
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
     */
    public abstract function get_widget(Website $oWebsite, $id, $data);

    /**
     * Gets the widget's editor. The data is either the saved data, or the data
     * just returned from parse_data (even if that was marked as invalid!)
     */
    public abstract function get_editor(Website $oWebsite, $id, $data);

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
    public abstract function parse_data(Website $oWebsite, $id);
}

/**
 * Stores info about a widget. All methods return strings.
 */
class WidgetInfo {

    private $name;
    private $description;
    private $version;
    private $author;
    private $author_website;
    private $website;
    private $directory_name;
    private $info_file;

    public function __construct($directory_name, $info_file) {
        $this->directory_name = $directory_name;
        $this->info_file = $info_file;
    }

    public function get_name() {
        $this->init();
        return $this->name;
    }

    public function get_description() {
        $this->init();
        return $this->description;
    }

    public function get_directory_name() {
        return $this->directory_name;
    }

    public function get_version() {
        $this->init();
        return $this->version;
    }

    public function get_author() {
        $this->init();
        return $this->author;
    }

    public function get_author_website() {
        $this->init();
        return $this->author_website;
    }

    public function get_website() {
        $this->init();
        return $this->website;
    }

    private function init() {
        if (isset($this->name)) {
            return;
        }

        if (file_exists($this->info_file)) {
            $lines = file($this->info_file);
            foreach ($lines as $line) {
                $split = explode("=", $line, 2);
                if (count($split) < 2) {
                    continue;
                }
                if ($split[0] == "author") {
                    $this->author = $split[1];
                }
                if ($split[0] == "author.website") {
                    $this->author_website = $split[1];
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
            $this->name = $this->directory_name;
            $this->description = "info.txt not found.";
        }
    }

}

?>
<?php

namespace Rcms\Core;

use PDOException;

use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

/**
 * Widget manager
 */
class WidgetRepository extends Repository {

    const TABLE_NAME = "widgets";

    // Reference to the Website.
    private $website;
    // Cache of all loaded widgets for this page
    private $loadedWidgets = array();
    // Temporary variable to store the directory name when echoeing the widgets
    private $widgetDirectoryName;
    private $sidebarIdField;
    private $widgetDataField;
    private $widgetIdField;
    private $widgetNameField;
    private $widgetPriorityField;

    public function __construct(Website $website) {
        parent::__construct($website->getDatabase());
        $this->website = $website;

        $this->sidebarIdField = new Field(Field::TYPE_INT, "sidebarId", "sidebar_id");
        $this->widgetDataField = new Field(Field::TYPE_JSON, "widgetData", "widget_data");
        $this->widgetIdField = new Field(Field::TYPE_PRIMARY_KEY, "id", "widget_id");
        $this->widgetNameField = new Field(Field::TYPE_STRING, "widgetName", "widget_naam");
        $this->widgetPriorityField = new Field(Field::TYPE_INT, "priority", "widget_priority");
    }

    public function createEmptyObject() {
        return new PlacedWidget($this->website->getUriWidgets());
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getPrimaryKey() {
        return $this->widgetIdField;
    }

    public function getAllFields() {
        return array($this->widgetIdField, $this->widgetDataField,
            $this->widgetNameField, $this->widgetPriorityField,
            $this->sidebarIdField);
    }

    /**
     * Gets a list of all installed widgets.
     * @return WidgetInfoFile[] List of all installed widgets.
     */
    public function getInstalledWidgets() {
        $widgets = array();
        $directoryToScan = $this->website->getUriWidgets();

        // Check directory
        if (!is_dir($directoryToScan)) {
            return;
        }

        // Scan it
        $files = scanDir($directoryToScan);
        foreach ($files as $file) {
            if ($file[0] != '.') {
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
        $areas = $this->website->getThemeManager()->getCurrentTheme()->getWidgetAreas($this->website);
        $areas[1] = $this->website->t("widgets.homepage");
        return $areas;
    }

    /**
     * Returns a list of PlacedWidgets for the given sidebar.
     * @param int $sidebar_id The id of the sidebar.
     * @return PlacedWidget[] List of placed widgets.
     */
    public function getPlacedWidgetsFromSidebar($sidebar_id) {
        return $this->where($this->sidebarIdField, '=', $sidebar_id)->orderDescending($this->widgetPriorityField)->select();
    }

    /**
     * Searches the database for the widget with the given id.
     * @param int $widget_id The id of the widget.
     * @return PlacedWidget|null The placed widget, or null if it isn't found.
     */
    public function getPlacedWidget($widget_id) {
        try {
            return $this->where($this->getPrimaryKey(), '=', $widget_id)->selectOneOrFail();
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Returns the widget in the given directory.
     * @param string $widgetDirectoryName Directory name. Do not include the full path.
     * @return WidgetDefinition The widget, or null if not found.
     */
    public function getWidgetDefinition($widgetDirectoryName) {
        if (!isSet($this->loadedWidgets[$widgetDirectoryName])) {
            $this->widgetDirectoryName = $widgetDirectoryName;
            $file = $this->website->getUriWidgets() . "/" . $widgetDirectoryName . "/main.php";
            if (file_exists($file)) {
                require($file);
            }
        }
        if (isSet($this->loadedWidgets[$widgetDirectoryName])) {
            return $this->loadedWidgets[$widgetDirectoryName];
        } else {
            return null;
        }
    }

    // Echoes all widgets in the specified sidebar
    public function getWidgetsHTML($sidebarId) {
        $website = $this->website;
        $oDB = $website->getDatabase();
        if (!$oDB->isInstalled() || !$oDB->isUpToDate()) {
            return $this->getSiteInstallError($sidebarId);
        }
        $loggedInAsAdmin = $website->isLoggedInAsStaff(true);
        $output = "";

        // Get all widgets that should be displayed
        $result = $this->getPlacedWidgetsFromSidebar($sidebarId);

        foreach ($result as $widget) {
            $id = $widget->getId();
            $directory_name = $widget->getDirectoryName();
            $this->widgetDirectoryName = $directory_name;

            // Try to load it if it isn't loaded yet
            if (!isSet($this->loadedWidgets[$directory_name])) {
                $file = $website->getUriWidgets() . $directory_name . "/main.php";
                if (file_exists($file)) {
                    require($file);
                } else {
                    $website->getText()->logError("The widget $directory_name (id=$id) was not found. File <code>$file</code> was missing.");
                    $website->addError("A widget was missing.");
                    continue;
                }
            }

            // Check if load was succesfull. Display widget or display error.
            if (isSet($this->loadedWidgets[$directory_name])) {
                $output.= $this->loadedWidgets[$directory_name]->getText($this->website, $id, $widget->getData());
                if ($loggedInAsAdmin) {
                    // Links for editing and deleting
                    $output.= "<p>\n";
                    $output.= '<a class="arrow" href="' . $website->getUrlPage("edit_widget", $id) . '">' . $website->t("main.edit") . " " . $website->t("main.widget") . '</a> ';
                    $output.= '<a class="arrow" href="' . $website->getUrlPage("delete_widget", $id) . '">' . $website->t("main.delete") . " " . $website->t("main.widget") . '</a> ';
                    $output.= "</p>\n";
                }
            } else {
                $website->getText()->logError("The widget $directory_name (id=$id) could not be loaded. File <code>$file</code> is incorrect.");
                $website->addError("A widget was missing.");
            }
        }

        // Link to manage widgets
        if ($loggedInAsAdmin) {
            $output.= '<p><a class="arrow" href="' . $website->getUrlPage("widgets") . '">';
            $output.= $website->t("main.manage") . " " . strToLower($website->t("main.widgets"));
            $output.= "</a></p>\n";
        }

        return $output;
    }

    private function getSiteInstallError($sidebarId) {
        $website = $this->website;
        if ($sidebarId != 1) {
            return "";
        }
        if (!$website->getDatabase()->isInstalled()) {
            return <<<ERROR
                <p>{$website->t("errors.site.not_installed")}</p>
                <p>
                    <a class="button primary_button" href="{$website->getUrlPage("installing_database")}">
                        {$website->t("errors.site.install")}
                    </a>
                </p>
ERROR;
        }
        return <<<ERROR
            <p>{$website->t("errors.site.not_updated")}</p>
            <p>
                <a class="button primary_button" href="{$website->getUrlPage("installing_database")}">
                    {$website->t("errors.site.update")}
                </a>
            </p>
ERROR;
    }

    /**
     * Registers a widget. Widget files should call this.
     * @param WidgetDefinition $widget The widget to register.
     */
    public function registerWidget(WidgetDefinition $widget) {
        $this->loadedWidgets[$this->widgetDirectoryName] = $widget;
    }

    /**
     * Saves the given placed widget to the database.
     * @param PlacedWidget $placedWidget The placed widget.
     * @throws PDOException If saving fails.
     */
    public function savePlacedWidget(PlacedWidget $placedWidget) {
        $this->saveEntity($placedWidget);
    }
    
    /**
     * Deletes the placed widget from the database.
     * @param PlacedWidget $placedWidget The placed widget.
     * @throws NotFoundException If the placed widget doesn't appear in the
     * database.
     * @throws PDOException If a database error occurs.
     */
    public function deletePlacedWidget(PlacedWidget $placedWidget) {
        $this->where($this->widgetIdField, '=', $placedWidget->getId())->deleteOneOrFail();
    }

}

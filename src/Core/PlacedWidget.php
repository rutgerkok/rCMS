<?php

namespace Rcms\Core;

use Rcms\Core\Repository\Entity;

class PlacedWidget extends Entity {

    protected $id;
    protected $sidebarId;
    protected $widgetData;
    protected $priority;
    protected $widgetName;
    protected $baseDirectory;

    /**
     * Represents a widget. You'll need to fill out all parameters. The easiest
     * way to get this object is by calling {@link Widgets#get_placed_widget(int)}.
     *
     * @param string $baseDirectory The base directory where all widgets are
     * installed in.
     */
    public function __construct($baseDirectory) {
        $this->baseDirectory = $baseDirectory;
    }

    public function getWidgetDefinition(WidgetRepository $widget_loader) {
        return $widget_loader->getWidgetDefinition($this->baseDirectory . $this->widgetName . '/');
    }

    /**
     * Returns an array with key=>value pairs of data. Will never be null, but
     * can be empty.
     * @return array The array.
     */
    public function getData() {
        return $this->widgetData;
    }

    /**
     * Sets the internal data array to the new value. Can be null. Silently
     * fails if the data is invalidated by setting $data["valid"] to false.
     * @param mixed $data The new data.
     */
    public function setData($data) {
        if ($data == null) {
            $this->widgetData = array();
        } else {
            if (!isSet($data["valid"]) || $data["valid"]) {
                $this->widgetData = $data;
            }
        }
    }

    public function getPriority() {
        return $this->priority;
    }

    public function setPriority($priority) {
        $this->priority = (int) $priority;
    }

    public function getSidebarId() {
        return $this->sidebarId;
    }

    public function setSidebarId($sidebar_id) {
        $this->sidebarId = (int) $sidebar_id;
    }

    /**
     * Returns the unique numeric id of this placed widget. Returns 0 if the
     * widget is not yet placed.
     * @return int The unique id of this placed widget.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns info about this widget provided by the author.
     * @return WidgetInfoFile Info about the widget.
     */
    public function getWidgetInfo() {
        return new WidgetInfoFile($this->widgetName, $this->baseDirectory . '/' . $this->widgetName . "/info.txt");
    }

    /**
     * Returns the name of the directory this widget is in, like "text".
     * @return string The name of the directory this widget is in.
     */
    public function getDirectoryName() {
        return $this->widgetName;
    }

    /**
     * Saves all changes.
     * @param Database $oDatabase The database to save to.
     * @return boolean Whether the save was successfull. On failure, an error
     *     message is printed automatically.
     */
    public function save(Database $oDatabase) {
        if ($this->id > 0) {
            // Update
            $sql = "UPDATE `widgets` SET ";
            $sql.= '`widget_data` = "' . $oDatabase->escapeData($this->dataString) . '", ';
            $sql.= '`sidebar_id` = ' . $this->sidebarId . ', ';
            $sql.= '`widget_priority` = ' . $this->priority . ' ';
            $sql.= "WHERE `widget_id` = " . $this->id;
            if ($oDatabase->query($sql)) {
                return true;
            } else {
                return false;
            }
        } else {
            // Add
            $sql = "INSERT INTO `widgets` (`widget_naam`, `widget_data`, ";
            $sql.= "`sidebar_id`, `widget_priority`) VALUES (";
            $sql.= '"' . $oDatabase->escapeData($this->directoryName) . '", ';
            $sql.= '"' . $oDatabase->escapeData($this->dataString) . '", ';
            $sql.= $this->sidebarId . ', ';
            $sql.= $this->priority . ')';
            if ($oDatabase->query($sql)) {
                $this->id = $oDatabase->lastInsertId();
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Deletes this widget from the database.
     * @param Database $oDatabase The database to delete from.
     * @return boolean Whether the deletion was successfull.
     */
    public function delete(Database $oDatabase) {
        if ($oDatabase->query("DELETE FROM `widgets` WHERE `widget_id`= " . $this->id)) {
            $this->id = 0; // Reset
            return true;
        } else {
            return false;
        }
    }

}

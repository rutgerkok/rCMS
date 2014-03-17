<?php

class PlacedWidget {

    private $id;
    private $sidebarId;
    private $dataString;
    private $priority;
    private $directoryName;
    private $pathToDirectory;

    /**
     * Represents a widget. You'll need to fill out all parameters. The easiest
     * way to get this object is by calling {@link Widgets#get_placed_widget(int)}.
     * 
     * @param int $id The id of the widget. Set it to 0 to place a new widget.
     * @param int $sidebarId The id of the sidebar of the widget.
     * @param string $directoryName The name of the directory of the widget. 
     *    Do not include the path.
     * @param string $dataString JSON representation of the data of this widget. Can be null.
     * @param int $priority Priority of the widget.
     * @param string $directory The full directory of where this widget is
     *    installed in.
     */
    public function __construct($id, $sidebarId, $directoryName, $dataString,
            $priority, $directory) {
        $this->id = (int) $id;
        $this->sidebarId = (int) $sidebarId;
        $this->directoryName = $directoryName;
        $this->dataString = $dataString;
        if (!$this->dataString) {
            $this->dataString = "{}";
        }
        $this->priority = (int) $priority;
        $this->pathToDirectory = $directory;
    }

    public function getWidgetDefinition(Widgets $widget_loader) {
        return $widget_loader->getWidgetDefinition($this->directoryName);
    }

    /**
     * Returns an array with key=>value pairs of data. Will never be null, but
     * can be empty.
     * @return mixed The array.
     */
    public function getData() {
        return JSONHelper::stringToArray($this->dataString);
    }

    /**
     * Sets the internal data array to the new value. Can be null. Silently
     * fails if the data is invalidated by setting $data["valid"] to false.
     * @param mixed $data The new data.
     */
    public function setData($data) {
        if ($data == null) {
            $this->dataString = "{}";
        } else {
            if (!isSet($data["valid"]) || $data["valid"]) {
                $this->dataString = JSONHelper::arrayToString($data);
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
        return new WidgetInfoFile($this->directoryName, $this->pathToDirectory . "/info.txt");
    }

    /**
     * Returns the name of the directory this widget is in, like "text".
     * @return string The name of the directory this widget is in.
     */
    public function getDirectoryName() {
        return $this->directoryName;
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
                $this->id = $oDatabase->getLastInsertedId();
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

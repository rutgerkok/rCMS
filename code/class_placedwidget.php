<?php

class PlacedWidget {

// Notes about prevention of SQL injection:
// - integer values are always casted in the constructor and in the setters
// - json_encode takes care of the data setting, but the JSON itself also needs
//     to be escaped.
// - directory_name is not saved on update, and cleaned when creating the widget
//     for the first time

    private $id;
    private $sidebar_id;
    private $data_string;
    private $priority;
    private $directory_name;
    private $directory_complete;

    /**
     * Represents a widget. You'll need to fill out all parameters. The easiest
     * way to get this object is by calling {@link Widgets#get_placed_widget(int)}.
     * 
     * @param int $id The id of the widget. Set it to 0 to place a new widget.
     * @param int $sidebar_id The id of the sidebar of the widget.
     * @param string $directory_name The name of the directory of the widget. 
     *    Do not include the path.
     * @param string $data_string JSON representation of the data of this widget. Can be null.
     * @param int $priority Priority of the widget.
     * @param string $directory The full directory of where this widget is
     *    installed in.
     */
    public function __construct($id, $sidebar_id, $directory_name, $data_string, $priority, $directory) {
        $this->id = (int) $id;
        $this->sidebar_id = (int) $sidebar_id;
        $this->directory_name = $directory_name;
        $this->data_string = $data_string;
        if ($this->data_string == null) {
            $this->data_string = "{}";
        }
        $this->priority = (int) $priority;
        $this->directory_complete = $directory;
    }

    public function get_widget_definition(Widgets $widget_loader) {
        return $widget_loader->get_widget_definition($this->directory_name);
    }

    /**
     * Returns an array with key=>value pairs of data. Will never be null, but
     * can be empty.
     * @return mixed The array.
     */
    public function get_data() {
        return json_decode($this->data_string, true);
    }

    /**
     * Sets the internal data array to the new value. Can be null. Silently
     * fails if the data is invalidated by setting $data["valid"] to false.
     * @param mixed $data The new data.
     */
    public function set_data($data) {
        if ($data == null) {
            $this->data_string = "{}";
        } else {
            if (!isset($data["valid"]) || $data["valid"]) {
                $this->data_string = json_encode($data);
            }
        }
    }

    public function get_priority() {
        return $this->priority;
    }

    public function set_priority($priority) {
        $this->priority = (int) $priority;
    }

    public function get_sidebar_id() {
        return $this->sidebar_id;
    }

    public function set_sidebar_id($sidebar_id) {
        $this->sidebar_id = (int) $sidebar_id;
    }

    /**
     * Returns the unique numeric id of this placed widget. Returns 0 if the
     * widget is not yet placed.
     * @return int The unique id of this placed widget.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns info about this widget provided by the author.
     * @return WidgetInfo Info about the widget.
     */
    public function get_widget_info() {
        return new WidgetInfo($this->directory_name, $this->directory_complete . "/info.txt");
    }

    /**
     * Returns the name of the directory this widget is in, like "text".
     * @return string The name of the directory this widget is in.
     */
    public function get_directory_name() {
        return $this->directory_name;
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
            $sql.= '`widget_data` = "' . $oDatabase->escape_data($this->data_string) . '", ';
            $sql.= '`sidebar_id` = ' . $this->sidebar_id . ', ';
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
            $sql.= '"' . $oDatabase->escape_data($this->directory_name) . '", ';
            $sql.= '"' . $oDatabase->escape_data($this->data_string) . '", ';
            $sql.= $this->sidebar_id . ', ';
            $sql.= $this->priority . ')';
            if ($oDatabase->query($sql)) {
                $this->id = $oDatabase->inserted_id();
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

?>

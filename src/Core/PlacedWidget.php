<?php

namespace Rcms\Core;

use InvalidArgumentException;

use Rcms\Core\Repository\Entity;

/**
 * Represents a widget that has been placed somewhere. It consists of the
 * {@link WidgetDefinition} that is used, as well as the settings for the widget.
 * Together this information can be used to render the widget.
 */
class PlacedWidget extends Entity {

    protected $id;
    protected $sidebarId;
    protected $widgetData = array();
    protected $priority = 0;
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

    /**
     * Creates a new placed widget. Won't be saved automatically, save it to a
     * widget repository.
     * @param string $baseDirectory Base directory of all widgets.
     * @param string $widgetName Name of the widget.
     * @param int $sidebarId Id of the sidebar the widget is placed in.
     * @return PlacedWidget The placed widget.
     */
    public static function newPlacedWidget($baseDirectory, $widgetName, $sidebarId) {
        $placedWidget = new PlacedWidget($baseDirectory);
        $placedWidget->setSidebarId($sidebarId);
        $placedWidget->widgetName = (string) $widgetName;
        return $placedWidget;
    }

    public function getWidgetDefinition(WidgetRepository $widget_loader) {
        return $widget_loader->getWidgetDefinition($this->widgetName);
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
     * @param array|null $data The new data.
     */
    public function setData($data) {
        if ($data == null) {
            $this->widgetData = array();
        } else if (is_array($data)) {
            if (!isSet($data["valid"]) || $data["valid"]) {
                $this->widgetData = $data;
            }
        } else {
            throw new InvalidArgumentException("data must be array or null, $data given");
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

}

<?php

namespace Rcms\Core\Widget;

use InvalidArgumentException;
use PDOException;

use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;
use Rcms\Core\Website;

/**
 * Repository for {@link PlacedWidget}s.
 */
class WidgetRepository extends Repository {

    const TABLE_NAME = "widgets";

    // Reference to the Website.
    private $website;
    private $documentIdField;
    private $widgetDataField;
    private $widgetIdField;
    private $widgetNameField;
    private $widgetPriorityField;

    public function __construct(Website $website) {
        parent::__construct($website->getDatabase());
        $this->website = $website;

        $this->documentIdField = new Field(Field::TYPE_INT, "documentId", "sidebar_id");
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
            $this->documentIdField);
    }

    /**
     * Gets a list of all installed widgets.
     * @return WidgetInfoFile[] List of all installed widgets.
     * @deprecated Use the method on InstalledWidgets instead.
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
     * Returns a list of PlacedWidgets for the given document.
     * @param int $documentId The id of the document.
     * @return PlacedWidget[] List of placed widgets.
     */
    public function getWidgetsInDocumentWithId($documentId) {
        return $this->where($this->documentIdField, '=', $documentId)->orderDescending($this->widgetPriorityField)->select();
    }

    /**
     * Searches the database for the widget with the given id.
     * @param int $widget_id The id of the widget.
     * @return PlacedWidget The placed widget, or null if it isn't found.
     * @throws NotFoundException If no widget exists with that id.
     */
    public function getPlacedWidget($widget_id) {
        return $this->where($this->getPrimaryKey(), '=', $widget_id)->selectOneOrFail();
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
    
    /**
     * Deletes all widgets of a document. If the given document has no widgets,
     * then this method will have no effect.
     * @param int $documentId The document id.
     * @throws InvalidArgumentException If documentId is not an int.
     * @throws PDOException If a database error occurs.
     */
    public function deleteAllPlacedWidgetsInDocument($documentId) {
        if (!is_int($documentId)) {
            throw new InvalidArgumentException("documentId must be an int");
        }
        $this->where($this->documentIdField, '=', $documentId)->delete();
    }

}

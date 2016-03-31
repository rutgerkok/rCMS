<?php

namespace Rcms\Core\Widget;

use InvalidArgumentException;
use Rcms\Core\Document\Document;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\InfoFile;
use Rcms\Core\Repository\Entity;

/**
 * Represents a widget that has been placed somewhere. It consists of the
 * {@link WidgetDefinition} that is used, as well as the settings for the widget.
 * Together this information can be used to render the widget.
 */
class PlacedWidget extends Entity {

    protected $id;
    protected $documentId;
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
     * @param string $dirName Name of the widget.
     * @param Document $document The document the widget is placed in.
     * @return PlacedWidget The placed widget.
     * @throws NotFoundException If no widget exists with the given dirName.
     */
    public static function newPlacedWidget($baseDirectory, $dirName,
            Document $document) {
        $placedWidget = new PlacedWidget($baseDirectory);
        $placedWidget->setDocumentId($document->getId());
        $placedWidget->widgetName = (string) $dirName;
        $placedWidget->id = 0;
        if (!file_exists($placedWidget->getWidgetCodeFile())) {
            throw new NotFoundException();
        }
        return $placedWidget;
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

    public function getDocumentId() {
        return $this->documentId;
    }

    public function setDocumentId($documentId) {
        $this->documentId = (int) $documentId;
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
     * Gets the file where the widget metadata is stored.
     * @return InfoFile The file.
     */
    public function getWidgetInfoFile() {
        return new InfoFile($this->baseDirectory . '/' . $this->widgetName . "/info.txt");
    }

    /**
     * Gets the PHP file with the main entry point of the code of the widget.
     * @return string The file.
     */
    public function getWidgetCodeFile() {
        return $this->baseDirectory . '/' . $this->widgetName . "/main.php";
    }

    /**
     * Returns info about this widget provided by the author.
     * @return WidgetInfoFile Info about the widget.
     */
    public function getWidgetInfo() {
        return new WidgetInfoFile($this->widgetName, $this->getWidgetInfoFile());
    }

    /**
     * Returns the name of the directory this widget is in, like "text".
     * @return string The name of the directory this widget is in.
     */
    public function getDirectoryName() {
        return $this->widgetName;
    }

}

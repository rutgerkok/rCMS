<?php

namespace Rcms\Core\Widget;

use LogicException;

use Rcms\Core\Website;

/**
 * Holds all widgets currently loaded. This class avoids that widgets can be
 * loaded twice, which would cause a fatal error.
 */
class InstalledWidgets {
    
    /**
     * @var Website The website instance, for running the widget code.
     */
    private $website;

    /**
     * @var WidgetDefinition[] All widget defintions, to avoid loading the code
     * twice.
     */
    private $loadedWidgets = array();

    /**
     * @var string Name of the widget that is currently being loaded. Variable
     * needed for `$this->registerWidget()`.
     */
    private $currentlyLoadingWidgetName = "";

    /**
     * Creates a new instance of `LoadedWidgets`.
     * @param Website $website The website, for running widget code.
     */
    public function __construct(Website $website) {
        $this->website = $website;
    }

    /**
     * Gets a list of all installed widgets.
     * @return WidgetInfoFile[] List of all installed widgets.
     */
    public function getInstalledWidgets() {
        $widgets = array();
        $directoryToScan = $this->website->getUriWidgets();

        // Check directory
        if (!is_dir($this->widgetDirectory)) {
            return;
        }

        // Scan it
        $files = scanDir($this->widgetDirectory);
        foreach ($files as $file) {
            // Ignore hidden files
            if ($file[0] === '.') {
                continue;
            }

            // Ignore anything that's not a directory
            if (!is_dir($this->widgetDirectory . $file)) {
                continue;
            }

            $widgets[] = new WidgetInfoFile($file, $directoryToScan . $file . "/info.txt");
        }

        return $widgets;
    }

    /**
     * Gets the widget definition (containing the actual behaviour of the widget)
     * from the placed widget.
     * @param PlacedWidget $placedWidget The placed widget.
     * @return WidgetDefinition The widget definition.
     * @throws LogicException If the widget code contains an error.
     */
    public function getDefinition(PlacedWidget $placedWidget) {
        $dirName = $placedWidget->getDirectoryName();

        // Load the widget
        if (!isSet($this->loadedWidgets[$dirName])) {
            $this->currentlyLoadingWidgetName = $dirName;
            require($placedWidget->getWidgetCodeFile());
            $this->currentlyLoadingWidgetName = "";
        }

        // Check if loaded
        if (!isSet($this->loadedWidgets[$dirName])) {
            throw new LogicException("Widget {$dirName} contains an error: it didn't register itself.");
        }

        return $this->loadedWidgets[$dirName];
    }

    /**
     * Gets the HTML output of the widget.
     * @param PlacedWidget $placedWidget The widget.
     * @return string The HTML output.
     */
    public function getOutput(PlacedWidget $placedWidget) {
        $widgetDefinition = $this->getDefinition($placedWidget);
        return $widgetDefinition->getText($this->website, $placedWidget->getId(), $placedWidget->getData());
    }

    /**
     * Registers a widget. Widget files must call this method.
     * @param WidgetDefinition $widget The widget to register.
     */
    public function registerWidget(WidgetDefinition $widget) {
        if (empty($this->currentlyLoadingWidgetName)) {
            throw new LogicException("Widget registering currently not active");
        }
        $this->loadedWidgets[$this->currentlyLoadingWidgetName] = $widget;
    }

}

<?php

namespace Rcms\Core\Widget;

use LogicException;
use Psr\Http\Message\StreamInterface;
use Rcms\Core\InfoFile;
use Rcms\Core\Website;

/**
 * Provides access to the definitions of all widgets installed on the site. This
 * class avoids that widgets can be loaded twice, which would cause a fatal error.
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
    private $loadedWidgets = [];

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
     * @return WidgetMeta[] List of all installed widgets.
     */
    public function getInstalledWidgets() {
        $widgets = [];
        $directoryToScan = $this->website->getUriWidgets();

        // Check directory
        if (!is_dir($directoryToScan)) {
            return;
        }

        // Scan it
        $files = scanDir($directoryToScan);
        foreach ($files as $file) {
            // Ignore hidden files
            if ($file[0] === '.') {
                continue;
            }

            // Ignore anything that's not a directory
            if (!is_dir($directoryToScan . $file)) {
                continue;
            }

            $widgets[] = new WidgetMeta($file, new InfoFile($directoryToScan . $file . "/info.txt"));
        }

        return $widgets;
    }

    /**
     * Gets the widget definition (containing the actual behaviour of the widget)
     * from the placed widget.
     *
     * If the widget code has been uninstalled, an instance of `NullWidget` is
     * returned.
     * @param PlacedWidget $placedWidget The placed widget.
     * @return WidgetDefinition The widget definition.
     * @throws LogicException If the widget code contains an error.
     */
    public function getDefinition(PlacedWidget $placedWidget) {
        $dirName = $placedWidget->getDirectoryName();

        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $dirName)) {
            // Not a valid name
            return new NullWidget("invalidName");
        }
        // Load the widget
        if (!isSet($this->loadedWidgets[$dirName])) {
            $this->currentlyLoadingWidgetName = $dirName;
            $file = $placedWidget->getWidgetCodeFile();
            if (!file_exists($file)) {
                return new NullWidget($dirName);
            }
            require($file);
            $this->currentlyLoadingWidgetName = "";
        }

        // Check if loaded
        if (!isSet($this->loadedWidgets[$dirName])) {
            throw new LogicException("Widget {$dirName} contains an error: it didn't register itself.");
        }

        return $this->loadedWidgets[$dirName];
    }

    /**
     * Gets all widget areas on the site. This is both the area on the home
     * page, as well as any theme-defined locations.
     * @return string[] All widget areas, indexed by their id.
     */
    public function getWidgetAreas() {
        $areas = $this->website->getThemeManager()->getCurrentThemeMeta()->getWidgetAreas($this->website);
        $areas[1] = $this->website->t("widgets.the_homepage");
        return $areas;
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

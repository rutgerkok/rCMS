<?php

namespace Rcms\Core;

/**
 * Holds the code of a widget.
 */
abstract class WidgetDefinition {

    /**
     * Gets the text of the widget.
     * @param Website $oWebsite The currently used website.
     * @param int $id The unique id of the widget.
     * @param array $data All data attached to the widget, key->value pairs.
     * @return string The text.
     */
    public abstract function getText(Website $oWebsite, $id, $data);

    /**
     * Gets the widget's editor. The data is either the saved data, or the data
     * just returned from parse_data (even if that was marked as invalid!)
     * @return string The editor.
     */
    public abstract function getEditor(Website $oWebsite, $id, $data);

    /**
     * Parses all input created by getEditor. You'll have to use the $_REQUEST
     * array. Make sure to sanitize your input, but don't escape it, that will
     * be done for you.
     * 
     * If the data is invalid set $return_array["valid"] to false. If you want
     * to give any feedback to the user, use $oWebsite->addError(message).
     * 
     * @param Website $oWebsite The currently used website.
     * @param int $id The unique id of the widget.
     * @return array Array of all data.
     */
    public abstract function parseData(Website $oWebsite, $id);
}

<h2><?php echo $this->t("editor.widget.edit") ?></h2>
<?php
// AUTHENTICATION
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(Authentication::$ADMIN_RANK, true);

// CONTENTS
$oWidgets = new Widgets($this);
$textToDisplay = '';

if ($loggedIn) {
    $widget_id = isSet($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

    $widget = null;
    if ($widget_id == 0) {
        // New widget
        $sidebar_id = isSet($_REQUEST['sidebar_id']) ? (int) $_REQUEST['sidebar_id'] : -1;
        if ($sidebar_id > 0 && $sidebar_id <= $this->getThemeWidgetAreaCount()) {
            $directory_name = isSet($_REQUEST['directory_name']) ? $_REQUEST['directory_name'] : "";
            $directory = $this->getUriWidgets() . '/' . $_REQUEST['directory_name'];
            if ($directory_name != "" && is_dir($directory)) {
                $widget = new PlacedWidget(0, $sidebar_id, $directory_name, "", 0, $directory);
            }
        }
    } elseif ($widget_id > 0) {
        // Existing widget
        $widget = $oWidgets->getPlacedWidget($widget_id);
    }
    if ($widget == null) {
        // Tried to edit non-existing widget or tried to create invalid widget
        // Show meaningless error
        $this->addError($this->t("main.widget") . ' ' . $this->t("errors.not_found"));
    } else {
        // Get data from widget
        $widget_info = $widget->getWidgetInfo();
        $widget_definition = $widget->getWidgetDefinition($oWidgets);
        $widget_data = $widget->getData();

        // Show header
        $textToDisplay.= '<h3>' . $widget_info->getName() . "</h3>\n";

        // If sent, parse results
        if (isSet($_REQUEST["sent"])) {
            // Set sidebar id if filled in
            $valid_sidebar_id = true; // Not mandatory, so no id is also good
            if (isSet($_REQUEST["sidebar_id"])) {
                $sidebar_id = (int) $_REQUEST["sidebar_id"];
                if ($sidebar_id > 0 && $sidebar_id <= $this->getThemeWidgetAreaCount()) {
                    $widget->setSidebarId($sidebar_id);
                } else {
                    $this->addError($this->tReplacedKey(""));
                    $valid_sidebar_id = false;
                }
            }
            // Set priority if filled in
            if (isSet($_REQUEST["priority"])) {
                $widget->setPriority((int) $_REQUEST["priority"]);
            }
            // Set data
            $widget_data = $widget_definition->parseData($this, $widget->getId());
            if (!isSet($widget_data["valid"]) || $widget_data["valid"]) {
                // Only continue if there is tag about validation, or if the
                // tag has been set to true
                $widget->setData($widget_data);
                // Get whether this is a new widget (id will be changed to a real
                // one after it has saved, so check has to come before that)
                $is_new_widget = ($widget->getId() == 0);
                if ($widget->save($this->getDatabase())) {
                    if ($is_new_widget) {
                        // Created
                        $textToDisplay.= "<p><em>" . $this->t("main.widget") . " " . $this->t("editor.is_created") . "</em>\n";
                        $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("widgets") . '">' . $this->t("main.widgets") . "</a>\n";
                        $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("home") . '">' . $this->t("main.home") . "</a></p>\n";
                    } else {
                        // Updated
                        $textToDisplay.= "<p><em>" . $this->t("main.widget") . " " . $this->t("editor.is_edited") . "</em>\n";
                        $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("widgets") . '">' . $this->t("main.widgets") . "</a>\n";
                        $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("home") . '">' . $this->t("main.home") . "</a></p>\n";
                    }
                } else {
                    // Database error, not saved
                    $textToDisplay.= "<p><em>" . $this->t("main.widget") . " " . $this->t("errors.not_saved") . "</em></p>\n";
                }
            } else {
                // User error, not saved
                $textToDisplay.= "<p><em>" . $this->t("main.widget") . " " . $this->t("errors.not_saved") . "</em></p>\n";
            }
        }

        // Start form  
        $textToDisplay.= '<p>' . $this->t("main.fields_required") . "</p>\n";
        $textToDisplay.= '<form action="' . $this->getUrlMain() . '" method="post">' . "\n";

        // Sidebar id
        $textToDisplay.= '<p><label for="sidebar_id">' . $this->t("widgets.sidebar") . ":</label>";
        $textToDisplay.= '<span class="required">*</span><br />' . "\n";
        $textToDisplay.= '<select name="sidebar_id" id="sidebar_id">' . "\n";
        $sidebarCount = $this->getThemeWidgetAreaCount();
        foreach ($oWidgets->getWidgetAreas() as $i => $name) {
            $textToDisplay.= '<option ';
            if ($i == $widget->getSidebarId()) {
                $textToDisplay.= 'selected="selected" ';
            }
            $textToDisplay.= 'value="' . $i . '">' . $name . "</option>\n";
        }
        $textToDisplay.= "</select></p>\n";

        // Priority
        $textToDisplay.= '<p><label for="priority">' . $this->t("widgets.priority") . ":</label>";
        $textToDisplay.= '<span class="required">*</span><br />' . "\n";
        $textToDisplay.= '<input type="number" step="1" name="priority" id="priority" value="' . $widget->getPriority() . '" />';
        $textToDisplay.= '</p>';

        // Widget settings
        $textToDisplay.= $widget_definition->getEditor($this, $widget->getId(), $widget_data);

        // Save button
        $textToDisplay.= '<input type="submit" class="button primary_button" value="';
        $textToDisplay.= $this->t("editor.save") . '" name="sent" />' . "\n";

        // "Invisble"  data
        $textToDisplay.= '<input type="hidden" name="p" value="edit_widget" />' . "\n";
        $textToDisplay.= '<input type="hidden" name="id" value="' . $widget->getId() . '" />' . "\n";
        $textToDisplay.= '<input type="hidden" name="directory_name" value="' . $widget->getDirectoryName() . '" />' . "\n";

        // Close form
        $textToDisplay.= "</form>";
    }

    // Links
    $textToDisplay.= "<p>\n";
    $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("widgets") . '">' . $this->t("main.widgets") . "</a><br />\n";
    $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("admin") . '">' . $this->t("main.admin") . "</a>\n";
    $textToDisplay.= "</p>\n";
}

// ERROR HANDLING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// DISPLAY
echo $textToDisplay;

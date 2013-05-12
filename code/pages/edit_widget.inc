<h2><?php echo $this->t("editor.widget.edit") ?></h2>
<?php
// AUTHENTICATION
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK, true);

// CONTENTS
$oWidgets = new Widgets($this);
$text_to_display = '';

if ($logged_in) {
    $widget_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

    $widget = null;
    if ($widget_id == 0) {
        // New widget
        $sidebar_id = isset($_REQUEST['sidebar_id']) ? (int) $_REQUEST['sidebar_id'] : -1;
        if ($sidebar_id > 0 && $sidebar_id <= $this->get_theme_widget_area_count()) {
            $directory_name = isset($_REQUEST['directory_name']) ? $_REQUEST['directory_name'] : "";
            $directory = $this->get_uri_widgets() . '/' . $_REQUEST['directory_name'];
            if ($directory_name != "" && is_dir($directory)) {
                $widget = new PlacedWidget(0, $sidebar_id, $directory_name, "", 0, $directory);
            }
        }
    } elseif ($widget_id > 0) {
        // Existing widget
        $widget = $oWidgets->get_placed_widget($widget_id);
    }
    if ($widget == null) {
        // Tried to edit non-existing widget or tried to create invalid widget
        // Show meaningless error
        $this->add_error($this->t("main.widget") . ' ' . $this->t("errors.not_found"));
    } else {
        // Get data from widget
        $widget_info = $widget->get_widget_info();
        $widget_definition = $widget->get_widget_definition($oWidgets);
        $widget_data = $widget->get_data();

        // Show header
        $text_to_display.= '<h3>' . $widget_info->get_name() . "</h3>\n";

        // If sent, parse results
        if (isset($_REQUEST["sent"])) {
            // Set sidebar id if filled in
            $valid_sidebar_id = true; // Not mandatory, so no id is also good
            if (isset($_REQUEST["sidebar_id"])) {
                $sidebar_id = (int) $_REQUEST["sidebar_id"];
                if ($sidebar_id > 0 && $sidebar_id <= $this->get_theme_widget_area_count()) {
                    $widget->set_sidebar_id($sidebar_id);
                } else {
                    $this->add_error($this->t_replaced_key(""));
                    $valid_sidebar_id = false;
                }
            }
            // Set priority if filled in
            if (isset($_REQUEST["priority"])) {
                $widget->set_priority((int) $_REQUEST["priority"]);
            }
            // Set data
            $widget_data = $widget_definition->parse_data($this, $widget->get_id());
            if (!isset($widget_data["valid"]) || $widget_data["valid"]) {
                // Only continue if there is tag about validation, or if the
                // tag has been set to true
                $widget->set_data($widget_data);
                // Get whether this is a new widget (id will be changed to a real
                // one after it has saved, so check has to come before that)
                $is_new_widget = ($widget->get_id() == 0);
                if ($widget->save($this->get_database())) {
                    if ($is_new_widget) {
                        // Created
                        $text_to_display.= "<p><em>" . $this->t("main.widget") . " " . $this->t("editor.is_created") . "</em>\n";
                        $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("widgets") . '">' . $this->t("main.widgets") . "</a>\n";
                        $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("home") . '">' . $this->t("main.home") . "</a></p>\n";
                    } else {
                        // Updated
                        $text_to_display.= "<p><em>" . $this->t("main.widget") . " " . $this->t("editor.is_edited") . "</em>\n";
                        $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("widgets") . '">' . $this->t("main.widgets") . "</a>\n";
                        $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("home") . '">' . $this->t("main.home") . "</a></p>\n";
                    }
                } else {
                    // Database error, not saved
                    $text_to_display.= "<p><em>" . $this->t("main.widget") . " " . $this->t("errors.not_saved") . "</em></p>\n";
                }
            } else {
                // User error, not saved
                $text_to_display.= "<p><em>" . $this->t("main.widget") . " " . $this->t("errors.not_saved") . "</em></p>\n";
            }
        }

        // Start form  
        $text_to_display.= '<p>' . $this->t("main.fields_required") . "</p>\n";
        $text_to_display.= '<form action="' . $this->get_url_main() . '" method="post">' . "\n";

        // Sidebar id
        $text_to_display.= '<p><label for="sidebar_id">' . $this->t("widgets.sidebar") . ":</label>";
        $text_to_display.= '<span class="required">*</span><br />' . "\n";
        $text_to_display.= '<select name="sidebar_id" id="sidebar_id">' . "\n";
        $sidebar_count = $this->get_theme_widget_area_count();
        foreach ($oWidgets->get_widget_areas() as $i => $name) {
            $text_to_display.= '<option ';
            if ($i == $widget->get_sidebar_id()) {
                $text_to_display.= 'selected="selected" ';
            }
            $text_to_display.= 'value="' . $i . '">' . $name . "</option>\n";
        }
        $text_to_display.= "</select></p>\n";

        // Priority
        $text_to_display.= '<p><label for="priority">' . $this->t("widgets.priority") . ":</label>";
        $text_to_display.= '<span class="required">*</span><br />' . "\n";
        $text_to_display.= '<input type="number" step="1" name="priority" id="priority" value="' . $widget->get_priority() . '" />';
        $text_to_display.= '</p>';

        // Widget settings
        $text_to_display.= $widget_definition->get_editor($this, $widget->get_id(), $widget_data);

        // Save button
        $text_to_display.= '<input type="submit" class="button" value="';
        $text_to_display.= $this->t("editor.save") . '" name="sent" />' . "\n";

        // "Invisble"  data
        $text_to_display.= '<input type="hidden" name="p" value="edit_widget" />' . "\n";
        $text_to_display.= '<input type="hidden" name="id" value="' . $widget->get_id() . '" />' . "\n";
        $text_to_display.= '<input type="hidden" name="directory_name" value="' . $widget->get_directory_name() . '" />' . "\n";

        // Close form
        $text_to_display.= "</form>";
    }

    // Links
    $text_to_display.= "<p>\n";
    $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("widgets") . '">' . $this->t("main.widgets") . "</a><br />\n";
    $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("admin") . '">' . $this->t("main.admin") . "</a>\n";
    $text_to_display.= "</p>\n";
}

// ERROR HANDLING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// DISPLAY
echo $text_to_display;

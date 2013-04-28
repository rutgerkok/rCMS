<h2><?php echo $this->t("editor.widget.delete") ?></h2>
<?php
// AUTHENTICATION
$oAuth = new Authentication($this);

$logged_in = $oAuth->check(Authentication::$MODERATOR_RANK, true);

// CONTENTS
$text_to_display = '';

if ($logged_in) {
    $widget_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    $widget = null;
    if ($widget_id > 0) {
        $oWidgets = new Widgets($this);
        $widget = $oWidgets->get_placed_widget($widget_id);
        unset($oWidgets);
    }
    if ($widget) {
        if (isset($_REQUEST['confirm'])) {
            // Confirmed
            if ($widget->delete($this->get_database())) {
                $text_to_display.= '<p>' . $this->t("main.widget") . ' ' . $this->t("editor.is_deleted") . '</p>';
                $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("widgets") . '">' . $this->t("main.ok") . '</a></p>';
            } else {
                $text_to_display.= '<p>' . $this->t("main.widget") . ' ' . $this->t("errors.is_not_removed") . '</p>';
                $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("widgets") . '">' . $this->t("main.ok") . '</a></p>';
            }
        } else {
            // Show confirmation message
            $text_to_display.= '<p>' . $this->t("editor.widget.delete.confirm") . '</p>';

            //en laat daarbij de comment zien
            $widget_info = $widget->get_widget_info();
            $text_to_display.= '<h3>' . $widget_info->get_name() . "</h3>\n";
            $text_to_display.= '<p>' . $widget_info->get_description() . "</h3>\n";
            $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("delete_widget", $widget_id, array("confirm" => 1)) . '">' . $this->t("main.yes") . "</a> | \n";
            $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("widgets") . '">' . $this->t("main.no") . '</a></p>';
        }
    } else {
        // Widget not found
        $this->add_error($this->t("main.widget") . ' ' . $this->t("errors.not_found"));
        $text_to_display = '';
    }
}

// ERROR HANDLING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// DISPLAY
echo $text_to_display;

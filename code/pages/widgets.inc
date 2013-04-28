<h2><?php echo $this->t("main.widgets") ?></h2>
<?php
// LOGIC
$oAuth = new Authentication($this);
$text_to_display = "";
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK);

if ($logged_in) {
    $oWidgets = new Widgets($this);
    $sidebar_count = $this->get_theme_sidebar_count();

    // Get installed widgets
    $text_to_display.= '<h3 class="notable">' . $this->t("widgets.installed_widgets") . "</h3>\n";
    $widgets = $oWidgets->get_widgets_installed();
    if (count($widgets) === 0) {
        $text_to_display.= "<p><em>" . $this->t("widgets.no_widgets_found") . "</em></p>";
    } else {
        // @var $widget WidgetInfo
        foreach ($widgets as $widget) {
            // Show a widget
            $text_to_display.= '<div class="installed_widget">';
            $text_to_display.= "<h3>" . $widget->get_name() . " v" . $widget->get_version() . "</h3>";
            $text_to_display.= "<p>" . $widget->get_description() . "</p>\n";
            $text_to_display.= "<p>\n";
            $text_to_display.= '<a class="arrow" href="' . $widget->get_website() . '">' . $this->t("widgets.view_website") . "</a><br />\n";
            $text_to_display.= '<a class="arrow" href="' . $widget->get_author_website() . '">';
            $text_to_display.= $this->t("widgets.created_by") . " " . $widget->get_author() . "</a>\n";
            $text_to_display.= "</p>\n";
            // Link to add it to sidebar
            $text_to_display.= "<p>\n";
            for ($i = 1; $i <= $sidebar_count; $i++) {
                $text_to_display.= '<a class="arrow" href="';
                $text_to_display.= $this->get_url_page("edit_widget", 0, array(
                    "directory_name" => $widget->get_directory_name(),
                    "sidebar_id" => $i)
                );
                $text_to_display.= '">' . $this->t_replaced("widgets.add_to_sidebar", $i);
                $text_to_display.= "</a> ";
            }
            $text_to_display.= "</p>\n";
            $text_to_display.= "</div>\n";
        }
    }
    unset($widgets);
    
    // Echo sidebars
    for($i = 1; $i <= $sidebar_count; $i++) {
        $text_to_display.= '<div class="widgets_in_sidebar">';
        $text_to_display.= '<h3 class="notable">';
        $text_to_display.= $this->t_replaced("widgets.widgets_in_sidebar", $i);
        $text_to_display.= "</h3>\n";
        $widgets_in_sidebar = $oWidgets->get_placed_widgets_from_sidebar($i);
        foreach ($widgets_in_sidebar as $widget_in_sidebar) {
            $text_to_display.= "<h3>\n";
            $text_to_display.= $widget_in_sidebar->get_widget_info()->get_name();
            $text_to_display.= "</h3><p>\n";
            $text_to_display.= '<a class="arrow" href="'.$this->get_url_page("edit_widget",$widget_in_sidebar->get_id()).'">';
            $text_to_display.= $this->t("main.edit");
            $text_to_display.= "</a> ";
            $text_to_display.= '<a class="arrow" href="'.$this->get_url_page("delete_widget",$widget_in_sidebar->get_id()).'">';
            $text_to_display.= $this->t("main.delete");
            $text_to_display.= "</a>";
            $text_to_display.= "</p>\n";
        }
        
        $text_to_display.= "</div>\n";
    }
    
    // Back to admin
    $text_to_display.= "<p>\n";
    $text_to_display.= '<a class="arrow" href="'.$this->get_url_page("admin").'">' . $this->t("main.admin") . "</a>\n";
    $text_to_display.= "</p>\n";
}


// ERROR HANDLING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// DISPLAYING
echo $text_to_display;
?>
<?php

namespace Rcms\Core;

use Rcms\Core\Widget\WidgetRepository;

?>

<h2><?php echo $this->t("main.widgets") ?></h2>
<?php
// LOGIC
$oAuth = new Authentication($this);
$textToDisplay = "";
$loggedIn = $oAuth->check(Authentication::RANK_ADMIN);

if ($loggedIn) {
    $oWidgets = new WidgetRepository($this);
    $widget_areas = $this->getWidgets()->getWidgetAreas();
    $sidebarCount = $this->getThemeWidgetAreaCount();

    // Echo sidebars
    $textToDisplay.= '<div id="sidebar_page_sidebar">' . "\n";
    foreach ($widget_areas as $i => $name) {
        $textToDisplay.= '<h3 class="notable">';
        $textToDisplay.= $this->tReplaced("widgets.placed_on", strToLower($name));
        $textToDisplay.= "</h3>\n";
        $widgets_in_sidebar = $oWidgets->getWidgetsInDocumentWithId($i);
        foreach ($widgets_in_sidebar as $widget_in_sidebar) {
            $textToDisplay.= "<h3>\n";
            $textToDisplay.= $widget_in_sidebar->getWidgetInfo()->getName();
            $textToDisplay.= "</h3><p>\n";
            $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("edit_widget", $widget_in_sidebar->getId()) . '">';
            $textToDisplay.= $this->t("main.edit");
            $textToDisplay.= "</a> ";
            $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("delete_widget", $widget_in_sidebar->getId()) . '">';
            $textToDisplay.= $this->t("main.delete");
            $textToDisplay.= "</a>";
            $textToDisplay.= "</p>\n";
        }
        if (count($widgets_in_sidebar) == 0) {
            // No widgets found in this sidebar
            $textToDisplay.= "<p><em>" . $this->t("errors.nothing_found") . "</em></p>";
        }
    }
    $textToDisplay.= "</div>\n";
    
     // Get installed widgets
    $textToDisplay.= '<div id="sidebar_page_content">' . "\n";
    $textToDisplay.= '<h3 class="notable">' . $this->t("widgets.installed_widgets") . "</h3>\n";
    $widgets = $oWidgets->getInstalledWidgets();
    if (count($widgets) === 0) {
        $textToDisplay.= "<p><em>" . $this->t("widgets.no_widgets_found") . "</em></p>";
    } else {
        // @var $widget WidgetInfo
        foreach ($widgets as $widget) {
            // Show a widget
            $textToDisplay.= '<div class="installed_widget">';
            $textToDisplay.= "<h3>" . $widget->getName() . " v" . $widget->getVersion() . "</h3>";
            $textToDisplay.= "<p>" . $widget->getDescription() . "</p>\n";
            $textToDisplay.= "<p>\n";
            $textToDisplay.= '<a class="arrow" href="' . $widget->getWidgetWebsite() . '">' . $this->t("widgets.view_website") . "</a><br />\n";
            $textToDisplay.= '<a class="arrow" href="' . $widget->getAuthorWebsite() . '">';
            $textToDisplay.= $this->t("widgets.created_by") . " " . $widget->getAuthor() . "</a>\n";
            $textToDisplay.= "</p>\n";
            // Link to add it to sidebar
            $textToDisplay.= "<p>\n";
            foreach ($widget_areas as $i => $name) {
                $textToDisplay.= '<a class="arrow" href="';
                $textToDisplay.= $this->getUrlPage("edit_widget", 0, array(
                    "directory_name" => $widget->getDirectoryName(),
                    "document_id" => $i)
                );
                $textToDisplay.= '">' . $this->tReplaced("widgets.add_to", strToLower($name));
                $textToDisplay.= "</a><br />\n";
            }
            $textToDisplay.= "</p>\n";
            $textToDisplay.= "</div>\n";
        }
    }
    unset($widgets);

    // Back to admin
    $textToDisplay.= "<p>\n";
    $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("admin") . '">' . $this->t("main.admin") . "</a>\n";
    $textToDisplay.= "</p>\n";

    $textToDisplay.= "</div>\n";
}


// ERROR HANDLING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// DISPLAYING
echo $textToDisplay;
?>
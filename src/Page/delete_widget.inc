<?php namespace Rcms\Core; ?>

<h2><?php echo $this->t("editor.widget.delete") ?></h2>
<?php
// AUTHENTICATION
$oAuth = new Authentication($this);

$loggedIn = $oAuth->check(Authentication::$MODERATOR_RANK, true);

// CONTENTS
$textToDisplay = '';

if ($loggedIn) {
    $widget_id = isSet($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    $widget = null;
    if ($widget_id > 0) {
        $oWidgets = new WidgetRepository($this);
        $widget = $oWidgets->getPlacedWidget($widget_id);
        unset($oWidgets);
    }
    if ($widget) {
        if (isSet($_REQUEST['confirm'])) {
            // Confirmed
            if ($widget->delete($this->getDatabase())) {
                $textToDisplay.= '<p>' . $this->t("main.widget") . ' ' . $this->t("editor.is_deleted") . '</p>';
                $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("widgets") . '">' . $this->t("main.ok") . '</a></p>';
            } else {
                $textToDisplay.= '<p>' . $this->t("main.widget") . ' ' . $this->t("errors.is_not_removed") . '</p>';
                $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("widgets") . '">' . $this->t("main.ok") . '</a></p>';
            }
        } else {
            // Show confirmation message
            $textToDisplay.= '<p>' . $this->t("editor.widget.delete.confirm") . '</p>';

            //en laat daarbij de comment zien
            $widget_info = $widget->getWidgetInfo();
            $textToDisplay.= '<h3>' . $widget_info->getName() . "</h3>\n";
            $textToDisplay.= '<p>' . $widget_info->getDescription() . "</h3>\n";
            $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("delete_widget", $widget_id, array("confirm" => 1)) . '">' . $this->t("main.yes") . "</a> | \n";
            $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("widgets") . '">' . $this->t("main.no") . '</a></p>';
        }
    } else {
        // Widget not found
        $this->addError($this->t("main.widget") . ' ' . $this->t("errors.not_found"));
        $textToDisplay = '';
    }
}

// ERROR HANDLING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// DISPLAY
echo $textToDisplay;

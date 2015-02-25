<?php

namespace Rcms\Core\Widget;

use Rcms\Core\Website;

/**
 * When a widget is uninstalled, an instance of this class is used as the widget
 * definition.
 */
class NullWidget extends WidgetDefinition {

    /**
     * @var string The directory name.
     */
    private $directoryName;

    public function __construct($directoryName) {
        $this->directoryName = (string) $directoryName;
    }

    public function getEditor(Website $website, $id, $data) {
        return $this->getNotice($website);
    }

    public function parseData(Website $website, $id) {
        $website->addError($website->t("widgets.missing_definition.edit"));
        return array("valid" => false);
    }

    public function getText(Website $website, $id, $data) {
        return $this->getNotice($website);
    }

    private function getNotice(Website $website) {
        return <<<WIDGET
            <p><em>
                {$website->tReplaced("widgets.missing_definition", $this->directoryName)}
            </em></p>
WIDGET;
    }

}

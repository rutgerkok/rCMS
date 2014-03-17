<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Used on empty pages. There should be an error on the top, created using
 * Website->addError(..).
 */
class EmptyView extends View {

    public function getText() {
        return "";
    }

}

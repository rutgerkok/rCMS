<?php

namespace Rcms\Page\View;

/**
 * Used on empty pages. There should be an error on the top, created using
 * Website->addError(..).
 */
class EmptyView extends View {

    public function getText() {
        return "";
    }

}

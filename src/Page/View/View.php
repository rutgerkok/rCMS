<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * Represents a view. This class produces just an empty page.
 */
abstract class View {

    /** 
     * @var Text $oMessages Used for translations and
     * error/success messages. 
     */
    protected $text;

    public function __construct(Text $text) {
        $this->text = $text;
    }

    /**
     * Renders this view.
     */
    public abstract function getText();
}

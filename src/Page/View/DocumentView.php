<?php

namespace Rcms\Page\View;

use Rcms\Core\Document\Document;
use Rcms\Core\Text;

/**
 * The HTML view of a single document. Only includes the intro of the document,
 * the widgets are not shown. Use `WidgetsView` for that.
 */
class DocumentView extends View {
    
    /**
     * @var Document The document.
     */
    private $document;
    
    public function __construct(Text $text, Document $document) {
        parent::__construct($text);
        $this->document = $document;
    }
    
    public function getText() {
        $introHtml = nl2br(htmlSpecialChars($this->document->getIntro()), true);
        return <<<INTRO
            <p class="intro">
                $introHtml
            </p>
INTRO;
    }
}

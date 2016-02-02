<?php

namespace Rcms\Page\View;

use Rcms\Core\Link;
use Rcms\Core\Text;

/**
 * View of a collection of links.
 */
final class MenuView extends View{
    
    /**
     * @var Link[] Links to display.
     */
    private $links;
    /**
     * @var boolean Whether edit and delete links are displayed.
     */
    private $editLinks;
    /**
     * @var boolean Whether links open in a new window.
     */
    private $openInNewWindow;

    public function __construct(Text $text, array $links, $editLinks = false, $openInNewWindow = false) {
        parent::__construct($text);
        
        $this->links = $links;
        $this->editLinks = (boolean) $editLinks;
        $this->openInNewWindow = (boolean) $openInNewWindow;
    }

    public function getText() {
        $text = $this->text;
        $returnValue = "";

        foreach ($this->links as $link) {
            $returnValue.= '<li><a href="' . $text->e($link->getUrl()) . '"';
            if ($this->openInNewWindow) {
                $returnValue.= ' target="_blank"';
            }
            $returnValue.= ">" . $text->e($link->getText()) . "</a>";
            if ($this->editLinks) {
                $returnValue.=' <a class="arrow" href="' . $text->e($text->getUrlPage("edit_link", $link->getId())) . '">' . $text->t("main.edit") . "</a>";
                $returnValue.=' <a class="arrow" href="' . $text->e($text->getUrlPage("delete_link", $link->getId())) . '">' . $text->t("main.delete") . "</a>";
            }
            $returnValue.= "</li>\n";
        }
        return $returnValue;
    }
}

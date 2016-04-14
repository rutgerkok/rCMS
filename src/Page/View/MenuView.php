<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
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

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        foreach ($this->links as $link) {
            $stream->write('<li><a href="' . $text->e($link->getUrl()) . '"');
            if ($this->openInNewWindow) {
                $stream->write(' target="_blank"');
            }
            $stream->write(">" . $text->e($link->getText()) . "</a>");
            if ($this->editLinks) {
                $stream->write(' <a class="arrow" href="' . $text->e($text->getUrlPage("edit_link", $link->getId())) . '">' . $text->t("main.edit") . "</a>");
                $stream->write(' <a class="arrow" href="' . $text->e($text->getUrlPage("delete_link", $link->getId())) . '">' . $text->t("main.delete") . "</a>");
            }
            $stream->write("</li>\n");
        }
    }

}

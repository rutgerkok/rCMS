<?php
/**
 * Created by PhpStorm.
 * User: Rutger
 * Date: 29-7-2016
 * Time: 18:31
 */

namespace Rcms\Template;


use Psr\Http\Message\StreamInterface;
use Rcms\Core\Link;
use Rcms\Core\Text;

/**
 * Displays a list of links
 */
final class LinkListTemplate extends Template  {

    /**
     * @var Link[] The links that are displayed.
     */
    private $links;

    /**
     * @var bool Whether the edit and delete links are shown.
     */
    private $showEditLinks;

    public function __construct(Text $text, array $links, $showEditLinks) {
        parent::__construct($text);

        $this->links = $links;
        $this->showEditLinks = (boolean) $showEditLinks;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        $stream->write('<ul class="linklist">');
        foreach ($this->links as $link) {
            $stream->write('<li><a href="' . $text->e($link->getUrl()) . '" target="_blank">'
                    . $text->e($link->getText()) . "</a>");
            if ($this->showEditLinks) {
                $stream->write(' <a class="arrow" href="' . $text->getUrlPage("edit_link", $link->getId()) . '">' . $text->t("main.edit") . "</a>");
                $stream->write(' <a class="arrow" href="' . $text->getUrlPage("delete_link", $link->getId()) . '">' . $text->t("main.delete") . "</a>");
            }
            $stream->write("</li>\n");
        }
        $stream->write("</ul>");
    }
}
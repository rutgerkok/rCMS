<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

/**
 * Template for deleting a widget.
 */
final class WidgetDeleteTemplate extends Template {

    /**
     *
     * @var InstalledWidgets Used for rendering.
     */
    private $installedWidgets;

    /**
     * @var PlacedWidget The widget being deleted.
     */
    private $placedWidget;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    public function __construct(Text $text, InstalledWidgets $installedWidgets, PlacedWidget $placedWidget, RequestToken $requestToken) {
        parent::__construct($text);

        $this->installedWidgets = $installedWidgets;
        $this->placedWidget = $placedWidget;
        $this->requestToken = $requestToken;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        $stream->write("<p>{$text->t("widgets.delete.confirm")}</p>");

        $stream->write("<blockquote>");
        $this->installedWidgets->writeOutput($stream, $this->placedWidget);
        $stream->write("</blockquote>");


        $stream->write(<<<HTML
            <form method="post" action="{$text->url("delete_widget", $this->placedWidget->getId())}">
                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}"
                        value="{$text->e($this->requestToken->getTokenString())}" />
                    <input type="submit" value="{$text->t("main.delete")}" class="button dangerous_button" />
                    <a class="button" href="{$text->url("edit_document", $this->placedWidget->getDocumentId())}">
                        {$text->t("main.cancel")}
                    </a>
                </p>
            </form>
HTML
        );
    }

}

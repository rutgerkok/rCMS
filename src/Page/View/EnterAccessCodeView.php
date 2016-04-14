<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * View for the 404 page.
 */
class EnterAccessCodeView extends View {

    public function __construct(Text $text) {
        parent::__construct($text);
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<PAGE
        <div id="login">
            <form action="" method="post">
                <p>
                    {$text->t("access_key.key_request")}
                </p>
                <p>
                    <input type="password" name="key" id="key" autofocus="autofocus" />
                </p>
                <p>
                    <input type="submit" class="button" value="{$text->t("main.log_in")}" />
                </p>
            </form>
        </div>
PAGE
        );
    }

}

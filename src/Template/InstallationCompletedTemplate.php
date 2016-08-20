<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Page displayed when the database has not yet been set up.
 */
final class InstallationCompletedTemplate extends Template {
    
    public function __construct(Text $text) {
        parent::__construct($text);
    }
    
    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<HTML
            <p>{$text->t("install.completed")}</p>
            <table>
                <tr>
                    <th>{$text->t("users.username")}</th>
                    <td>admin</td>
                </tr>
                <tr>
                    <th>{$text->t("users.password")}</th>
                    <td>admin</td>
                </tr>
            </table>
            <p><a class="arrow" href="{$text->e($text->getUrlPage("login"))}">{$text->t("main.log_in")}</p>
HTML
        );
    }
}

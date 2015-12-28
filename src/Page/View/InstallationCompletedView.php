<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * Page displayed when the database has not yet been set up.
 */
final class InstallationCompletedView extends View {
    
    public function __construct(Text $text) {
        parent::__construct($text);
    }
    
    public function getText() {
        return <<<HTML
            <p>{$this->text->t("install.completed")}</p>
            <table>
                <tr>
                    <th>{$this->text->t("users.username")}</th>
                    <td>admin</td>
                </tr>
                <tr>
                    <th>{$this->text->t("users.password")}</th>
                    <td>admin</td>
                </tr>
            </table>
            <p><a class="arrow" href="{$this->text->getUrlPage("login")}">{$this->text->t("main.log_in")}</p>
HTML;
    }
}

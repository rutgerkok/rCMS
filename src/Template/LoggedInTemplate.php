<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Used on the login page when an user has successfully logged in.
 */
class LoggedInTemplate extends Template {

    private $showAdminLinks;

    /**
     * Creates the view.
     * @param Text $text The Text instance.
     * @param boolean $showAdminLinks True if the links to the admin pages
     * must be shown.
     */
    public function __construct(Text $text, $showAdminLinks) {
        parent::__construct($text);
        $this->showAdminLinks = (boolean) $showAdminLinks;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $adminLinks = "";
        if ($this->showAdminLinks) {
            $adminLinks = <<<EOT
                    <br />
                    <a href="{$text->e($text->getUrlPage("account_management"))}" class="arrow">{$text->t("main.account_management")}</a>
                    <br />
                    <a href="{$text->e($text->getUrlPage("admin"))}" class="arrow">{$text->t("main.admin")}</a>
EOT;
        }

        $stream->write(<<<EOT
                <h3>{$text->t('users.loggedIn')}</h3>
                <p>{$text->t('users.succesfully_loggedIn')}</p>
                <p>
                    <a href="{$text->e($text->getUrlMain())}" class="arrow">{$text->t("main.home")}</a>
                    <br />
                    <a href="{$text->e($text->getUrlPage("account"))}" class="arrow">{$text->t("main.my_account")}</a>
                    $adminLinks
                </p>
EOT
        );
    }

}

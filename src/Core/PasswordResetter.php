<?php

namespace Rcms\Core;

use InvalidArgumentException;
use PHPMailer\PHPMailer\PHPMailer;
use Rcms\Core\Config;
use Rcms\Core\Request;
use Rcms\Core\Website;

/**
 * For resetting the password of a user.
 */
final class PasswordResetter {

    /**
     *
     * @var Website The website
     */
    private $website;

    /**
     *
     * @var Request The request that triggered sending the mail.
     */
    private $request;

    private function composeMail(PHPMailer $mailer) {
        $text = $this->website->getText();
        $siteName = $this->website->getConfig()->get(Config::OPTION_SITE_TITLE);
        $resetUrl = (string) $this->getResetUrl();
        $mailer->isHTML(true);
        $mailer->Subject = $text->tReplaced("mail.password_reset.subject", $siteName);
        $mailer->Body = <<<BODY
            <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
            <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                    <title>{$text->e($mailer->Subject)}</title>
                </head>
                <body>
                    <h1 style="font-family:sans-serif">
                        {$text->e($text->tReplaced("mail.password_reset.header", $siteName))}
                    </h1>
                    <p style="font-family:sans-serif">
                        {$text->e($text->tReplaced("mail.password_reset.body", $siteName))}
                    </p>
                    <p style="text-align:center;font-family:sans-serif">
                        <a href="{$text->e($resetUrl)}"
                           style="font-size:large">
                            {$text->e($text->t("users.password.edit"))}
                        </a>
                    </p>
                </body>
            </html>
BODY;
        $mailer->AltBody = $text->t("mail.password_reset.header")
                . "\r\n \r\n"
                . $text->t("mail.password_reset.body")
                . "\r\n \r\n"
                . $resetUrl;
    }

    private function getResetUrl() {
        $requestUrl = $this->request->toPsr()->getUri();
        return $this->website->getText()->getUrlPage("reset_password")
                ->withHost($requestUrl->getHost())
                ->withScheme($requestUrl->getScheme() === "https"? "https" : "http");
    }
}

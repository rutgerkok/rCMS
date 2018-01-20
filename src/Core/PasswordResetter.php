<?php

namespace Rcms\Core;

use DateTime;
use PHPMailer\PHPMailer\PHPMailer;
use Rcms\Core\Config;
use Rcms\Core\Request;
use Rcms\Core\Website;

/**
 * For resetting the password of a user.
 */
final class PasswordResetter {

    /**
     * Checks whether this user is allowed to reset his/her password.
     * @param User $user The user to reset the password for.
     * @param string $givenToken Token provided by the user (usually: by a link
     * the user clicked)
     * @return bool True if the user is allowed to reset his/her password.
     */
    public static function verify(User $user, $givenToken) {
        $expirationTime = $user->getExtraData(User::DATA_PASSWORD_RESET_EXPIRATION, 0);
        if (time() > $expirationTime) {
            return false;
        }
        $storedToken = $user->getExtraData(User::DATA_PASSWORD_RESET_TOKEN, "");
        return $storedToken === $givenToken;
    }

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

    public function __construct(Website $website, Request $request) {
        $this->website = $website;
        $this->request = $request;
    }

    /**
     * Sends a password reset for the specified user. The user will be able to
     * reset his/her password for the upcoming 7 days.
     * @param User $user The user.
     */
    public function sendPasswordReset(User $user) {
        $user->setExtraData(User::DATA_PASSWORD_RESET_TOKEN, base64_encode(random_bytes(40)));
        $user->setExtraData(User::DATA_PASSWORD_RESET_EXPIRATION, strToTime("+1 week"));
        $this->website->getUserRepository()->save($user);

        $this->sendPasswordMail($user);
    }

    private function sendPasswordMail(User $user) {
        $mailSettings = new MailSettings($this->website->getConfig());
        $mailer = $mailSettings->getMailer();
        $this->composeMail($mailer, $user);
        $mailer->send();
    }

    private function composeMail(PHPMailer $mailer, User $user) {
        $text = $this->website->getText();
        $siteName = $this->website->getConfig()->get(Config::OPTION_SITE_TITLE);
        $resetUrl = (string) $this->getResetUrl($user);
        $mailer->isHTML(true);
        $mailer->Subject = $text->tReplaced("mail.password_reset.subject", $siteName);
        $mailer->addAddress($user->getEmail(), $user->getDisplayName());
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

    private function getResetUrl(User $user) {
        $token = $user->getExtraData(User::DATA_PASSWORD_RESET_TOKEN, "");

        $requestUrl = $this->request->toPsr()->getUri();
        return $this->website->getText()->getUrlPage("reset_password", $user->getId())
                ->withHost($requestUrl->getHost())
                ->withScheme($requestUrl->getScheme() === "https"? "https" : "http")
                ->withQuery("token=" . urlEncode($token));
    }

}

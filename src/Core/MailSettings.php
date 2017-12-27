<?php

namespace Rcms\Core;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * MailSettings
 */
final class MailSettings {

    const CONNECT_PHP = "php";
    const CONNECT_SMTP = "smtp";
    const CONNECT_SENDMAIL = "sendmail";
    const CONNECT_QMAIL = "qmail";

    /**
     * Gets all recognized connection types.
     * @return string[] All connection types.
     */
    public static function getConnectionTypes() {
        return [self::CONNECT_PHP, self::CONNECT_SMTP, self::CONNECT_SENDMAIL, self::CONNECT_QMAIL];
    }

    public static function getSmtpEncryptionTypes() {
        return ["", "ssl", "tls"];
    }

    /**
     * @var Config The configuration object of the website.
     */
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * Gets a PHPMailer instance based on the current mail settings. Connection
     * settings and From address will be filled in.
     */
    public function getMailer() {
        $mail = new PHPMailer(true);
        $this->setFrom($mail);
        $connectType = $this->config->get(Config::OPTION_MAIL_TYPE, self::CONNECT_PHP);
        switch ($connectType) {
            case self::CONNECT_SMTP:
                $this->getSmtpMailer($mail);
                break;
            case self::CONNECT_SENDMAIL:
                $mail->isSendmail();
                break;
            case self::CONNECT_QMAIL:
                $mail->isQmail();
                break;
            case self::CONNECT_PHP:
            default:
                $mail->isMail();
                break;
        }
        return $mail;
    }

    private function setFrom(PHPMailer $mail) {
        $from = $this->config->get(Config::OPTION_MAIL_FROM, "");
        if (!empty($from)) {
            $mail->setFrom($from);
        }
    }

    private function getSmtpMailer(PHPMailer $mailer) {
        $config = $this->config;
        $mailer->isSMTP();
        $mailer->Host = $config->get(Config::OPTION_MAIL_HOST, "localhost");
        $mailer->Port = $config->get(Config::OPTION_MAIL_PORT, 587);
        $mailer->SMTPSecure = $config->get(Config::OPTION_MAIL_ENCRYPTION, "tls");
        $username = $config->get(Config::OPTION_MAIL_USERNAME, "");
        if (!empty($username)) {
            $mailer->SMTPAuth = true;
            $mailer->Username = $username;
            $mailer->Password = $config->get(Config::OPTION_MAIL_PASSWORD);
        }
    }
}

<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Config;
use Rcms\Core\MailSettings;
use Rcms\Core\Text;
use Rcms\Core\RequestToken;

/**
 * A form for editing the mail settings.
 */
final class MailSettingsTemplate extends Template {

    private $token;
    private $settings;

    public function __construct(Text $text, RequestToken $token, array $settings) {
        parent::__construct($text);
        $this->token = $token;
        $this->settings = array_merge([
            Config::OPTION_MAIL_FROM => "",
            Config::OPTION_MAIL_TYPE => MailSettings::CONNECT_PHP,
            Config::OPTION_MAIL_ENCRYPTION => "",
            Config::OPTION_MAIL_HOST => "",
            Config::OPTION_MAIL_PORT => 65,
            Config::OPTION_MAIL_USERNAME => "",
            Config::OPTION_MAIL_PASSWORD => ""
            ], $settings);
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $settings = $this->settings;
        $stream->write(<<<FORM
            <form method="post" action="{$text->url("mail_settings")}">
                <p>
                    {$text->t("mail.settings.explained")}
                </p>
                <p>
                    <label for="mail_settings__from">
                        {$text->t("mail.settings.from")}<span class="required">*</span>:
                    </label><br />
                    <input type="email" name="mail_from" id="mail_settings__from" value="{$text->e($settings[Config::OPTION_MAIL_FROM])}" />
                </p>
                <p>
                    <label for="mail_settings__type">
                        {$text->t("mail.settings.type")}<span class="required">*</span>:
                    </label><br />
                    <select name="mail_type" id="mail_settings__type">
                        <option value="{$text->e(MailSettings::CONNECT_PHP)}" {$this->typeSelected(MailSettings::CONNECT_PHP)}>{$text->t("mail.settings.type.php")}</option>
                        <option value="{$text->e(MailSettings::CONNECT_SMTP)}" {$this->typeSelected(MailSettings::CONNECT_SMTP)}>{$text->t("mail.settings.type.smtp")}</option>
                        <option value="{$text->e(MailSettings::CONNECT_QMAIL)}" {$this->typeSelected(MailSettings::CONNECT_QMAIL)}>{$text->t("mail.settings.type.qmail")}</option>
                        <option value="{$text->e(MailSettings::CONNECT_SENDMAIL)}" {$this->typeSelected(MailSettings::CONNECT_SENDMAIL)}>{$text->t("mail.settings.type.sendmail")}</option>
                    </select>
                </p>
                <fieldset>
                    <legend>{$text->t("mail.settings.smtp_section")}</legend>
                    <p>
                        <label for="mail_settings__host">
                            {$text->t("mail.settings.host")}<span class="required">*</span>:
                        </label><br />
                        <input type="text" name="mail_host" id="mail_settings__host" value="{$text->e($settings[Config::OPTION_MAIL_HOST])}" />
                    </p>
                    <p>
                        <label for="mail_settings__port">
                            {$text->t("mail.settings.port")}<span class="required">*</span>:
                        </label><br />
                        <input type="text" name="mail_port" id="mail_settings__port" value="{$text->e($settings[Config::OPTION_MAIL_PORT])}" />
                    </p>
                    <p>
                        <label for="mail_settings__encryption">
                            {$text->t("mail.settings.encryption")}<span class="required">*</span>:
                        </label><br />
                        <select name="mail_encryption" id="mail_settings__encryption">
                            <option value="none" {$this->encryptionSelected("")}>{$text->t("mail.settings.encryption.none")}</option>
                            <option value="tls" {$this->encryptionSelected("tls")}>{$text->t("mail.settings.encryption.tls")}</option>
                            <option value="ssl" {$this->encryptionSelected("ssl")}>{$text->t("mail.settings.encryption.ssl")}</option>
                        </select>
                    </p>
                    <p>
                        <label for="mail_settings__user">
                            {$text->t("mail.settings.user")}:
                        </label><br />
                        <input type="text" name="mail_user" id="mail_settings__user" value="{$text->e($settings[Config::OPTION_MAIL_USERNAME])}" />
                    </p>
                    <p>
                        <label for="mail_settings__pass">
                            {$text->t("mail.settings.pass")}:
                        </label><br />
                        <input type="text" name="mail_pass" id="mail_settings__pass" value="{$text->e($settings[Config::OPTION_MAIL_PASSWORD])}" />
                    </p>
                </fieldset>
                <p>
                    <input type="submit" class="button primary_button" value="{$text->t("editor.save")}" />
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->token->getTokenString())}" />
                </p>
            </form>
FORM
        );
    }

    private function encryptionSelected($encryption) {
        $currentEncryption = $this->settings[Config::OPTION_MAIL_ENCRYPTION];
        if ($currentEncryption === $encryption) {
            return 'selected="selected"';
        }
        return '';
    }

    private function typeSelected($type) {
        $currentType = $this->settings[Config::OPTION_MAIL_TYPE];
        if ($currentType === $type) {
            return 'selected="selected"';
        }
        return '';
    }

}

<?php

namespace Rcms\Page;

use PDO;
use Rcms\Core\Config;
use Rcms\Core\Link;
use Rcms\Core\MailSettings;
use Rcms\Core\Ranks;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Template\MailSettingsTemplate;

/**
 * A page where admins can change the mailing settings of the site.
 */
final class MailSettingsPage extends Page {

    private $requestToken;
    private $settings = [];
    private $settingNames = [Config::OPTION_MAIL_ENCRYPTION,
        Config::OPTION_MAIL_FROM, Config::OPTION_MAIL_HOST,
        Config::OPTION_MAIL_PASSWORD, Config::OPTION_MAIL_PORT,
        Config::OPTION_MAIL_TYPE, Config::OPTION_MAIL_USERNAME];

    public function init(Website $website, Request $request) {
        $this->loadDatabaseSettings($website->getConfig());
        if (Validate::requestToken($request)) {
            $text = $website->getText();
            if ($this->loadSuppliedSettings($text, $request)) {
                $this->saveSettings($website->getConfig(), $website->getDatabase());
                $text->addMessage($text->t("mail.settings.saved"),
                        Link::of($text->getUrlPage("admin"), $text->t("main.admin")));
            }
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function loadDatabaseSettings(Config $config) {
        foreach ($this->settingNames as $settingName) {
            $this->settings[$settingName] = $config->get($settingName, "");
        }
    }

    private function loadSuppliedSettings(Text $text, Request $request) {
        $valid = true;

        $this->settings[Config::OPTION_MAIL_FROM] = $request->getRequestString("mail_from", "");
        if (!Validate::email($this->settings[Config::OPTION_MAIL_FROM])) {
            $text->addError($text->t("mail.settings.from") . ' ' . Validate::getLastError($text));
            $valid = false;
        }

        $type = $request->getRequestString("mail_type", "");
        if (!in_array($type, MailSettings::getConnectionTypes())) {
            $text->addError($text->t("mail.settings.type") . ' ' . $text->t("errors.is_invalid"));
            $valid = false;
        }
        $this->settings[Config::OPTION_MAIL_TYPE] = $type;

        if (!$this->loadSuppliedSmtpSettings($text, $request)) {
            $valid = false;
        }

        return $valid;
    }

    public function getMinimumRank() {
        return Ranks::ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("mail.settings.edit");
    }

    public function getTemplate(Text $text) {
        return new MailSettingsTemplate($text, $this->requestToken, $this->settings);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    private function saveSettings(Config $config, PDO $database) {
        foreach ($this->settingNames as $settingName) {
            $config->set($database, $settingName, $this->settings[$settingName]);
        }
    }

    public function loadSuppliedSmtpSettings(Text $text, Request $request) {
        $this->settings[Config::OPTION_MAIL_HOST] = $request->getRequestString("mail_host", "");
        $this->settings[Config::OPTION_MAIL_PORT] = $request->getRequestInt("mail_port", 0);
        $this->settings[Config::OPTION_MAIL_USERNAME] = $request->getRequestString("mail_user", "");
        $this->settings[Config::OPTION_MAIL_PASSWORD] = $request->getRequestString("mail_pass", "");

        $encryption = $request->getRequestString("mail_encryption", "");
        if ($encryption === "none") {
            $encryption = "";
        }
        $this->settings[Config::OPTION_MAIL_ENCRYPTION] = $encryption;
        if (!in_array($encryption, MailSettings::getSmtpEncryptionTypes())) {
            $text->addError($text->t("mail.settings.encryption") . ' ' . $text->t("errors.is_invalid"));
            return false;
        }
        return true;
    }

}

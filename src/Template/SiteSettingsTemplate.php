<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;

/**
 * A template for the site settings.
 */
final class SiteSettingsTemplate extends Template {
    
    
    
    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        
        $tokenName = RequestToken::FIELD_NAME;
        $tokenHtml = htmlSpecialChars($this->token->getTokenString());

        if ($this->saved) {
            $top_message = <<<EOT
                <em>{$text->t("site_settings.site_settings")} {$text->t("editor.are_changed")}</em>
                <a class="arrow" href="{$text->getUrlPage("admin")}">
                    {$text->t("main.admin")}
                </a>
EOT;
        }

        return <<<EOT
            <p>
                {$text->t("site_settings.editing_site_settings.explained")}
            </p>
            <p>
                {$text->t("main.fields_required")}
            </p>
            <form action="{$text->url("site_settings")}" method="post">
                <p>
                    <label for="option_title">{$text->t("site_settings.title")}</label>:<span class="required">*</span>
                    <br />
                    <input type="text" name="option_title" id="option_title" value="{$this->title}" />
                    <br />
                    <em>{$text->t("site_settings.title.explained")}</em>
                </p>
                <p>
                    <label for="option_copyright">{$text->t("site_settings.copyright")}</label>:
                    <br />
                    <input type="text" name="option_copyright" id="option_copyright" value="{$this->copyright}" />
                    <br />
                    <em>{$text->t("site_settings.copyright.explained")}</em>
                </p>
                <p>
                    <label for="option_password">{$text->t("site_settings.password")}</label>:
                    <br />
                    <input type="text" name="option_password" id="option_password" value="{$this->password}" />
                    <br />
                    <em>{$text->t("site_settings.password.explained")}</em>
                </p>
                <p>
                    <label for="option_language">{$text->t("site_settings.language")}</label>:<span class="required">*</span>
                    <br />
                    {$this->get_dropdown_list("option_language", $languages, $this->language, true)}
                    <br />
                    <em>{$text->t("site_settings.language.explained")}</em>
                </p>
                <p>
                    <label for="option_theme">{$text->t("site_settings.theme")}</label>:<span class="required">*</span>
                    <br />
                    {$this->get_dropdown_list("option_theme", $themes, $this->theme, true)}
                    <br />
                    <em>{$text->t("site_settings.theme.explained")}</em>
                </p>
                <p>
                    <label for="option_user_account_creation">
                        <input class="checkbox" type="checkbox" name="option_user_account_creation" id="option_user_account_creation" $user_account_creation_checked />
                        {$text->t("site_settings.user_account_creation")}
                    </label>
                    <br />
                    <em>{$text->t("site_settings.user_account_creation.explained")}</em>
                </p>
                <p>
                    <input type="hidden" name="$tokenName" value="$tokenHtml" />
                    <input type="submit" name="submit" class="button primary_button" value="{$text->t("editor.save")}" />
                    <a href="{$text->url("admin")} class="button">{$text->t("editor.save")}</a>
                </p>
            </form>
EOT;
    }

}

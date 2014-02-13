<?php

/**
 * Renders the admin page.
 */
class AdminPageView extends View {
    
    public function getText() {
        $oWebsite = $this->oWebsite;
        return <<<EOT
                
            <p>
                <a href="{$oWebsite->getUrlPage("site_settings")}" class="arrow">{$oWebsite->t("main.site_settings")}</a><br />
                <a href="{$oWebsite->getUrlPage("widgets")}" class="arrow">{$oWebsite->t("main.edit")} {$oWebsite->t("main.widgets")}</a>
            </p>

            <h3>{$oWebsite->t("main.articles")}</h3>
            <p>
                <a href="{$oWebsite->getUrlPage("edit_article")}" class="arrow">{$oWebsite->t("articles.create")}</a><br />
                <a href="{$oWebsite->getUrlPage("comments")}" class="arrow">{$oWebsite->t("comments.comments")}</a>
            </p>

            <h3>{$oWebsite->t("users.account_management")}</h3>
            <p>
                <a href="{$oWebsite->getUrlPage("create_account")}" class="arrow">{$oWebsite->t("users.create")}</a><br /> 
                <a class="arrow" href="{$oWebsite->getUrlPage("edit_password")}">{$oWebsite->t("editor.password.edit")}</a><br />
                <a class="arrow" href="{$oWebsite->getUrlPage("edit_email")}">{$oWebsite->t("editor.email.edit")}</a><br />
                <a class="arrow" href="{$oWebsite->getUrlPage("edit_display_name")}">{$oWebsite->t("editor.display_name.edit")}</a><br />
                <a class="arrow" href="{$oWebsite->getUrlPage("account_management")}">{$oWebsite->t("users.account_management")}</a><br />
            </p>

            <h3>{$oWebsite->t("main.links")}</h3>
            <p>
                <a href="{$oWebsite->getUrlPage("create_link")}" class="arrow">{$oWebsite->t("links.create")}</a><br />
                <a href="{$oWebsite->getUrlPage("links")}" class="arrow">{$oWebsite->t("links.edit_or_delete")}</a><br />
            </p>

            <h3>{$oWebsite->t("main.categories")}</h3>
            <p>
                <a href="{$oWebsite->getUrlPage("create_category")}" class="arrow">{$oWebsite->t("categories.create")}</a><br />
                <a href="{$oWebsite->getUrlPage("rename_category")}" class="arrow">{$oWebsite->t("categories.rename")}</a><br />
                <a href="{$oWebsite->getUrlPage("delete_category")}" class="arrow">{$oWebsite->t("categories.delete")}</a>
            </p>   
EOT;
    }
}

<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;

/**
 * Renders the admin page.
 */
class AdminPageView extends View {

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<EOT
                
            <p>
                <a href="{$text->e($text->getUrlPage("site_settings"))}" class="arrow">{$text->t("main.site_settings")}</a><br />
                <a href="{$text->e($text->getUrlPage("widgets"))}" class="arrow">{$text->t("main.edit")} {$text->t("main.widgets")}</a>
            </p>

            <h3>{$text->t("main.articles")}</h3>
            <p>
                <a href="{$text->e($text->getUrlPage("edit_article"))}" class="arrow">{$text->t("articles.create")}</a><br />
                <a href="{$text->e($text->getUrlPage("archive"))}" class="arrow">{$text->t("articles.archive")}</a><br />
                <a href="{$text->e($text->getUrlPage("comments"))}" class="arrow">{$text->t("comments.comments")}</a>
            </p>
                
            <h3>{$text->t("main.documents")}</h3>
            <p>
                <a href="{$text->e($text->getUrlPage("edit_document"))}" class="arrow">{$text->t("documents.create")}</a><br />
                <a href="{$text->e($text->getUrlPage("document_list"))}" class="arrow">{$text->t("documents.list.title")}</a>
            </p>

            <h3>{$text->t("users.account_management")}</h3>
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("create_account"))}" >{$text->t("users.create")}</a><br /> 
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_password"))}">{$text->t("users.password.edit")}</a><br />
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_email"))}">{$text->t("users.email.edit")}</a><br />
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_display_name"))}">{$text->t("users.display_name.edit")}</a><br />
                <a class="arrow" href="{$text->e($text->getUrlPage("account_management"))}">{$text->t("users.account_management")}</a><br />
            </p>

            <h3>{$text->t("main.links")}</h3>
            <p>
                <a href="{$text->e($text->getUrlPage("create_link"))}" class="arrow">{$text->t("links.create")}</a><br />
                <a href="{$text->e($text->getUrlPage("links"))}" class="arrow">{$text->t("links.edit_or_delete")}</a><br />
            </p>

            <h3>{$text->t("main.categories")}</h3>
            <p>
                <a href="{$text->e($text->getUrlPage("create_category"))}" class="arrow">{$text->t("categories.create")}</a><br />
                <a href="{$text->e($text->getUrlPage("rename_category"))}" class="arrow">{$text->t("categories.rename")}</a><br />
                <a href="{$text->e($text->getUrlPage("delete_category"))}" class="arrow">{$text->t("categories.delete")}</a>
            </p>   
EOT
        );
    }

}

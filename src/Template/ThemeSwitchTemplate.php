<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Theme\ThemeMeta;

/**
 * A view for changing the theme of the website.
 */
final class ThemeSwitchTemplate extends Template {

    /**
     * @var ThemeMeta[] Available themes.
     */
    private $themeInfos;

    /**
     * @var RequestToken Token for updating the theme.
     */
    private $requestToken;

    /**
     * @var bool True if the active theme can be changed, false otherwise.
     */
    private $editLinks;

    /**
     * Creates the template.
     * @param Text $text The text object.
     * @param RequestToken $requestToken Token for protecting the request.
     * @param ThemeMeta[] $themeInfos The available themes.
     */
    public function __construct(Text $text, RequestToken $requestToken, array $themeInfos) {
        parent::__construct($text);

        $this->requestToken = $requestToken;
        $this->themeInfos = $themeInfos;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        foreach ($this->themeInfos as $themeInfo) {
            $stream->write(<<<HTML
                <h3>{$text->e($themeInfo->getDisplayName())}</h3>
                <p>
                    {$text->e($themeInfo->getDescription())}
                    {$text->tReplaced("themes.created_by", '<a href="' . $text->e($themeInfo->getAuthorWebsite()) . '">'
                            . $text->e($themeInfo->getAuthor()) . '</a>')}.
                    <a href="{$text->e($themeInfo->getThemeWebsite())}" class="arrow">{$this->text->t("themes.view_more_information")}</a>
                </p>
                <p>
                    <form method="post" action="{$text->url("switch_theme")}">
                        <input type="hidden" name="theme" value="{$text->e($themeInfo->getDirectoryName())}" />
                        <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                        <input type="submit" class="button" value="{$text->t("themes.switch_to_this")}" />
                    </form>
                    </a>
                </p>
HTML
            );
        }

        $stream->write(<<<HTML
            <p>
                <a class="arrow" href="{$text->url("admin")}">{$text->t("main.admin")}</a>         
            </p>
HTML
        );

    }

}

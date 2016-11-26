<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Template\ThemeSwitchTemplate;
use Rcms\Theme\ThemeManager;
use Rcms\Theme\ThemeMeta;

/**
 * A page that allows the user to switch to another theme on the website.
 */
final class SwitchThemePage extends Page {

    /**
     * @var ThemeMeta[] All installed themes.
     */
    private $availableThemes;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;
    
    public function init(Website $website, Request $request) {
        parent::init($website, $request);
        
        $themeManager = $website->getThemeManager();
        if (!$themeManager->canSwitchThemes()) {
            $this->sendThemeSwitchError($website->getText());
        } else if (Validate::requestToken($request)) {
            $this->trySwitchTheme($themeManager, $website->getText(), $request);
        }
        $this->availableThemes = $themeManager->getAllThemes();

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("themes.switch.title");
    }
    
    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }
    
    public function getTemplate(Text $text) {
        return new ThemeSwitchTemplate($text, $this->requestToken, $this->availableThemes);
    }

    private function sendThemeSwitchError(Text $text) {
        $text->addError($text->t("themes.switching_disabled"));
    }

    private function trySwitchTheme(ThemeManager $themeManager, Text $text, Request $request) {
        $themeDirectory = $request->getRequestString("theme", "");
        if (!$themeManager->themeExists($themeDirectory)) {
            $text->addError($text->t("themes.does_not_exist"));
            return false;
        }
        $themeManager->setActiveTheme($themeDirectory);
        $text->addMessage($text->t("themes.successfully_switched"));
        return true;
    }

}

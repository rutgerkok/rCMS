<?php

namespace Rcms\Template\Support;

use Rcms\Core\Config;
use Rcms\Core\Text;
use Rcms\Theme\ThemeManager;

/**
 * Rich editor based on CKEditor and CKFinder.
 */
class CKEditor implements RichEditor {

    private $includedCkEditorScript = false;
    private $includedCkFinderScript = false;
    private $boundCkFinderToCkEditor = false;
    private $boundCkFinderToUrlField = false;
    
    /** @var Text The text object. */
    private $text;

    /** @var Config The website configuration. */
    private $config;

    /** @var ThemeManager The theme manager of the website. */
    private $themes;

    public function __construct(Text $text, Config $config, ThemeManager $themes) {
        $this->text = $text;
        $this->config = $config;
        $this->themes = $themes;
    }

    public function getImageChooser($name, $value) {
        $text = $this->text;
        
        $nameHtml = htmlSpecialChars($name);
        $valueHtml = htmlSpecialChars($value);
        return <<<IMAGE_CHOOSER
            {$this->getBindCKFinderToUrlFieldOnce()}
            <input name="{$nameHtml}" id="{$nameHtml}" type="url" class="full_width" value="{$valueHtml}" onblur="updateImage(this.value)" />
            <a onclick="browseServer()" class="arrow">{$text->t("main.edit")}</a>
            <a onclick="clearImage()" class="arrow">{$text->t("main.delete")}</a>
IMAGE_CHOOSER;
    }

    public function getEditor($name, $value) {
        $ckEditorUrl = $this->config->get(Config::OPTION_CKEDITOR_URL, null);
        $ckFinderUrl = $this->config->get(Config::OPTION_CKFINDER_URL, null);

        if ($ckEditorUrl === null) {
            return $this->getFallbackTextarea($name, $value);
        }

        $returnValue = "";

        // Load JavaScript stuff if needed
        $returnValue.= $this->getCKEditorScriptOnce();

        // Include editor
        $returnValue.= $this->getCKEditorTextarea($name, $value);

        // Attach CKFinder
        if ($ckFinderUrl !== null) {
            $returnValue.= $this->getCKFinderScriptOnce();
            $returnValue.= $this->getBindCKFinderToCkEditorOnce();
        }

        return $returnValue;
    }

    private function getCKFinderScriptOnce() {
        if (!$this->includedCkFinderScript) {
            $this->includedCkFinderScript = true;
            $ckFinderUrl = $this->config->get(Config::OPTION_CKFINDER_URL);
            return <<<SCRIPT
                <script type="text/javascript" src="{$ckFinderUrl}ckfinder.js"></script>
SCRIPT;
        }
        return "";
    }

    private function getCKEditorScriptOnce() {
        if (!$this->includedCkEditorScript) {
            $this->includedCkEditorScript = true;
            $ckFinderUrl = $this->config->get(Config::OPTION_CKEDITOR_URL);
            return <<<SCRIPT
                <script type="text/javascript" src="{$ckFinderUrl}ckeditor.js"></script>
SCRIPT;
        }
        return "";
    }
    
    private function getBindCKFinderToUrlFieldOnce() {
        if (!$this->boundCkFinderToUrlField) {
            $this->boundCkFinderToUrlField = true;
            $ckFinderUrl = $this->config->get(Config::OPTION_CKEDITOR_URL);
            return <<<SCRIPT
                <script type="text/javascript" src="{$this->text->getUrlJavascript("image_chooser")}"></script>
                <script type="text/javascript">
                    initializeCkFinder("{$ckFinderUrl}");
                </script>
SCRIPT;
        }
    }

    private function getBindCKFinderToCkEditorOnce() {
        if (!$this->boundCkFinderToCkEditor) {
            $this->boundCkFinderToCkEditor = true;
            $ckFinderUrl = $this->config->get(Config::OPTION_CKFINDER_URL);
            return <<<SCRIPT
                        <script type="text/javascript">
                            CKFinder.setupCKEditor(null, '$ckFinderUrl');
                        </script>
SCRIPT;
        }
    }

    private function getFallbackTextarea($name, $value) {
        $nameHtml = htmlSpecialChars($name);
        $valueHtml = htmlSpecialChars($value);

        return <<<FORM
            <textarea name="{$nameHtml}"
                id="{$nameHtml}"
                rows="30"
                cols="40"
                style="width:95%">{$valueHtml}</textarea>
FORM;
    }

    /**
     * Gets the CKEditor text area. Assumes all Javascript is already loaded.
     * @param string $name Name and id of the text area.
     * @param string $value Default value for the text area.
     * @return string The HTML.
     */
    private function getCKEditorTextarea($name, $value) {
        $nameHtml = htmlSpecialChars($name);
        $valueHtml = htmlSpecialChars($value);

        $currentTheme = $this->themes->getCurrentTheme();
        $editorColor = $currentTheme->getTextEditorColor();
        return <<<EDITOR
            <textarea id="$nameHtml" name="$nameHtml" rows="30" cols="40" style="width:95%">$valueHtml</textarea>
            <script type="text/javascript">
                CKEDITOR.inline( '$nameHtml', {
                    uiColor: '$editorColor',
                    format_tags : 'p;h3;pre',
                    allowedContent : true,
                    image2_alignClasses : ['image-align-left', 'image-align-center', 'image-align-right']
                });
            </script>
EDITOR;
    }

}

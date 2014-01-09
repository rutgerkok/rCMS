<?php

/**
 * Class that currently wraps CKEditor and CKFinder, but it can be adapted
 * for any editor.
 */
class Editor {

    private $websiteObject;
    private $included_ckeditor_js;
    private $included_ckfinder_js;
    private $launched_ckfinder_for_ckeditor;

    public function __construct(Website $oWebsite) {
        $this->websiteObject = $oWebsite;
    }

    /**
     * Includes CKEditor, or a simple text box if CKEditor is not installed.
     * @param string $field_name Field name. Should only contain letters, numbers and underscores.
     * @param string $field_value Field value. Will be escaped with htmlSpecialChars.
     * @return string The correct HTML output.
     */
    public function get_text_editor($field_name, $field_value) {
        $oWebsite = $this->websiteObject;

        $returnValue = "";
        $field_value = htmlSpecialChars($field_value);
        $editor_color = $oWebsite->getThemeManager()->get_theme()->getTextEditorColor();
        if ($oWebsite->getConfig()->get("ckeditor_url")) {
            // Load JavaScript stuff if needed
            if (!$this->included_ckeditor_js) {
                $returnValue .= '<script type="text/javascript" src="' . $oWebsite->getConfig()->get("ckeditor_url") . 'ckeditor.js"></script>';
                $this->included_ckeditor_js = true;
            }

            $returnValue .= <<<EOT
            <textarea id="$field_name" name="$field_name" rows="30" cols="40" style="width:95%">$field_value</textarea>
            <script type="text/javascript">
                CKEDITOR.replace( '$field_name', {
                    uiColor: '$editor_color',
                    format_tags : 'p;h3;pre',
                    contentsCss : ['{$oWebsite->getThemeManager()->get_url_theme()}main.css', '{$oWebsite->getUrlContent()}whitebackground.css']
                });
            </script>
EOT;
            if ($oWebsite->getConfig()->get("ckfinder_url")) {
                // CKFinder stuff
                if (!$this->included_ckfinder_js) {
                    $returnValue .= '<script type="text/javascript" src="' . $oWebsite->getConfig()->get("ckfinder_url") . 'ckfinder.js"></script>';
                    $this->included_ckfinder_js = true;
                }
                if (!$this->launched_ckfinder_for_ckeditor) {
                    $returnValue.= <<<EOT
                        <script type="text/javascript">
                            CKFinder.setupCKEditor(null, '{$oWebsite->getConfig()->get("ckfinder_url")}');
                        </script>
EOT;
                    $this->launched_ckfinder_for_ckeditor = true;
                }
            }
        } else {
            // Don't put CKEditor in
            $returnValue .= '<textarea name="' . $field_name . '" id="' . $field_name . '" rows="30" cols="40" style="width:95%">' . $field_value . '</textarea>';
        }
        return $returnValue;
    }

    /**
     * Gets the script necessary for the image selector to work. You can then
     * create a button with onclick="browseServer_$field_name" to laucnh the
     * image selector for the field with the name and id $field_name.
     * @param string $field_name The id and name of the field with the image URL.
     * @return string The necessary scripts.
     */
    public function get_image_selector_script($field_name) {
        $oWebsite = $this->websiteObject;
        $returnValue = "";

        if (!$this->included_ckfinder_js) {
            // Include CKFinder if needed
            $returnValue .= '<script type="text/javascript" src="' . $oWebsite->getConfig()->get("ckfinder_url") . 'ckfinder.js"></script>';
            $this->included_ckfinder_js = true;
        }

        // JavaScript stuff for this selector (please excuse the function names)
        $returnValue .= <<<EOT
            <script type="text/javascript">
                function browseServer_$field_name()
                {
                    var finder = new CKFinder();
                    finder.basePath = '{$oWebsite->getConfig()->get("ckfinder_url")}';
                    finder.selectActionFunction = setFileField_$field_name;
                    finder.popup();
                }

                function setFileField_$field_name(fileUrl)
                {
                    document.getElementById('$field_name').value = fileUrl;
                }
            </script>
EOT;
        return $returnValue;
    }

}

?>

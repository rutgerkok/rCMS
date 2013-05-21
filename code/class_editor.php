<?php

/**
 * Class that currently wraps CKEditor and CKFinder, but it can be adapted
 * for any editor.
 */
class Editor {

    private $website_object;
    private $included_ckeditor_js;
    private $included_ckfinder_js;
    private $launched_ckfinder_for_ckeditor;

    public function __construct(Website $oWebsite) {
        $this->website_object = $oWebsite;
    }

    /**
     * Includes CKEditor, or a simple text box if CKEditor is not installed.
     * @param string $field_name Field name. Should only contain letters, numbers and underscores.
     * @param string $field_value Field value. Will be escaped with htmlspecialchars.
     * @return string The correct HTML output.
     */
    public function get_text_editor($field_name, $field_value) {
        $oWebsite = $this->website_object;

        $return_value = "";
        $field_value = htmlspecialchars($field_value);
        $editor_color = $oWebsite->get_theme_manager()->get_theme()->get_text_editor_menu_color();
        if ($oWebsite->get_sitevar("ckeditor_url")) {
            // Load JavaScript stuff if needed
            if (!$this->included_ckeditor_js) {
                $return_value .= '<script type="text/javascript" src="' . $oWebsite->get_sitevar("ckeditor_url") . 'ckeditor.js"></script>';
                $this->included_ckeditor_js = true;
            }

            $return_value .= <<<EOT
            <textarea id="$field_name" name="$field_name" rows="30" cols="40" style="width:95%">$field_value</textarea>
            <script type="text/javascript">
                CKEDITOR.replace( '$field_name', {
                    uiColor: '$editor_color',
                    format_tags : 'p;h3;pre',
                    contentsCss : ['{$oWebsite->get_theme_manager()->get_url_theme()}main.css', '{$oWebsite->get_url_scripts()}whitebackground.css']
                });
            </script>
EOT;
            if ($oWebsite->get_sitevar("ckfinder_url")) {
                // CKFinder stuff
                if (!$this->included_ckfinder_js) {
                    $return_value .= '<script type="text/javascript" src="' . $oWebsite->get_sitevar("ckfinder_url") . 'ckfinder.js"></script>';
                    $this->included_ckfinder_js = true;
                }
                if (!$this->launched_ckfinder_for_ckeditor) {
                    $return_value.= <<<EOT
                        <script type="text/javascript">
                            CKFinder.setupCKEditor(null, '{$oWebsite->get_sitevar("ckfinder_url")}');
                        </script>
EOT;
                    $this->launched_ckfinder_for_ckeditor = true;
                }
            }
        } else {
            // Don't put CKEditor in
            $return_value .= '<textarea name="' . $field_name . '" id="' . $field_name . '" rows="30" cols="40" style="width:95%">' . $field_value . '</textarea>';
        }
        return $return_value;
    }

    /**
     * Gets the script necessary for the image selector to work. You can then
     * create a button with onclick="browseServer_$field_name" to laucnh the
     * image selector for the field with the name and id $field_name.
     * @param string $field_name The id and name of the field with the image URL.
     * @return string The necessary scripts.
     */
    public function get_image_selector_script($field_name) {
        $oWebsite = $this->website_object;
        $return_value = "";

        if (!$this->included_ckfinder_js) {
            // Include CKFinder if needed
            $return_value .= '<script type="text/javascript" src="' . $oWebsite->get_sitevar("ckfinder_url") . 'ckfinder.js"></script>';
            $this->included_ckfinder_js = true;
        }

        // JavaScript stuff for this selector (please excuse the function names)
        $return_value .= <<<EOT
            <script type="text/javascript">
                function browseServer_$field_name()
                {
                    var finder = new CKFinder();
                    finder.basePath = '{$oWebsite->get_sitevar("ckfinder_url")}';
                    finder.selectActionFunction = setFileField_$field_name;
                    finder.popup();
                }

                function setFileField_$field_name(fileUrl)
                {
                    document.getElementById('$field_name').value = fileUrl;
                }
            </script>
EOT;
        return $return_value;     
    }

}

?>

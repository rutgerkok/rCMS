<?php

namespace Rcms\Page\View\Support;

/**
 * A rich editor allows you to input HTML. In its simplest form this could be a
 * &lt;textarea&gt; that allows someone to type out the HTML. More sophisticated
 * editors have a WYSIWYG interface and allow to upload images.
 */
interface RichEditor {

    /**
     * Gets an HTML editor. Simply returning a &lt;textarea&gt; is allowed.
     * @param string $name Name of the field.
     * @param string $value Default value of the field.
     * @return string The HTML editor.
     */
    public function getEditor($name, $value);

    /**
     * Gets an image chooser. Simply returning a text field for URLs is
     * allowed. More sophisticated editors allow you to upload images.
     * @param string $name Name of the file uploader.
     * @param string $value Default value for the file uploader.
     * @return string The URL editor.
     */
    public function getImageChooser($name, $value);
}

<?php

/**
 * Represents a view. This class produces just an empty page.
 */
abstract class View {

    /** @var Website $oWebsite The website object. */
    protected $oWebsite;

    public function __construct(Website $oWebsite) {
        $this->oWebsite = $oWebsite;
    }

    /**
     * Renders this view.
     */
    public abstract function getText();
}

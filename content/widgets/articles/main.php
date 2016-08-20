<?php

namespace Rcms\Extend\Widget;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\ArticleRepository;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetDefinition;
use Rcms\Template\ArticleListTemplate;
use Rcms\Template\ArticleSmallListTemplate;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetArticles extends WidgetDefinition {

    const TYPE_WITHOUT_METADATA = 0;
    const TYPE_WITH_METADATA = 1;
    const TYPE_LIST = 2;
    const TYPE_LIST_WITH_IMAGES = 3;
    const SORT_NEWEST_TOP = 1;
    const SORT_OLDEST_TOP = 0;

    public function writeText(StreamInterface $stream, Website $website, $id, $data) {
        // Check variables
        if (!isSet($data["title"]) || !isSet($data["count"])
                || !isSet($data["display_type"]) || !isSet($data["categories"])) {
            // The order variable is not checked, as older configurations may
            // not have it. The default value will be used instead.
            return;
        }

        // Title
        if (strLen($data["title"]) > 0) {
            $stream->write("<h2>" . htmlSpecialChars($data["title"]) . "</h2>");
        }

        // Get options
        $categories = $data["categories"];
        $articlesCount = (int) $data["count"];
        $displayType = (int) $data["display_type"];

        // Sorting
        $oldestTop = false;
        if (isSet($data["order"]) && $data["order"] == self::SORT_OLDEST_TOP) {
            $oldestTop = true;
        }

        // Archive link
        $showArchiveLink = false;
        if (!isSet($data["archive"]) || $data["archive"] == true) {
            $showArchiveLink = true;
        }

        $oArticles = new ArticleRepository($website);
        $articles = $oArticles->getArticlesData($categories, $articlesCount, $oldestTop);

        if ($displayType >= self::TYPE_LIST) {
            // Small <ul> list
            $oArticlesTemplate = new ArticleSmallListTemplate($website->getText(), $articles, $website->isLoggedInAsStaff(), $categories[0], $displayType == self::TYPE_LIST_WITH_IMAGES, $showArchiveLink);
        } else {
            // Real paragraphs
            $oArticlesTemplate = new ArticleListTemplate($website->getText(), $articles, $categories[0], $displayType == self::TYPE_WITH_METADATA, $showArchiveLink, $website->isLoggedInAsStaff());
        }

        $oArticlesTemplate->writeText($stream);
    }

    public function getEditor(Website $website, $widget_id, $data) {
        $title = isSet($data["title"]) ? $data["title"] : "";
        $categories = isSet($data["categories"]) ? $data["categories"] : [];
        $count = isSet($data["count"]) ? $data["count"] : 4;
        $display_type = isSet($data["display_type"]) ? $data["display_type"] : self::TYPE_WITHOUT_METADATA;
        $order = isSet($data["order"]) ? $data["order"] : self::SORT_NEWEST_TOP;
        $archive = isSet($data["archive"]) ? $data["archive"] : true;

        // Title
        $textToDisplay = "<p>\n";
        $textToDisplay.= '<label for="title_' . $widget_id . '">';
        $textToDisplay.= $website->t("widgets.title") . "</label>:<br />\n";
        $textToDisplay.= '<input type="text" name="title_' . $widget_id . '" id="title_' . $widget_id . '"';
        $textToDisplay.= 'value="' . htmlSpecialChars($title) . '" />' . "\n";
        $textToDisplay.= "</p>\n";

        // Categories
        $oCategories = new CategoryRepository($website->getDatabase());
        $textToDisplay.= "<p>" . $website->t("main.categories") . ':';
        $textToDisplay.= '<span class="required">*</span><br />' . "\n";
        foreach ($oCategories->getCategories() as $category) {
            $checkbox_id = 'categories_' . $category->getId() . "_" . $widget_id;
            $textToDisplay.= '<input type="checkbox" class="checkbox" ';
            $textToDisplay.= 'name="categories_' . $widget_id . '[]" ';
            if (array_search($category->getId(), $categories) !== false) {
                $textToDisplay.= 'checked="checked" ';
            }
            $textToDisplay.= 'id="' . $checkbox_id . '" value="' . $category->getId() . '" />';
            $textToDisplay.= '<label for="' . $checkbox_id . '">' . htmlSpecialChars($category->getName()) . "</label><br />" . "\n";
        }
        $textToDisplay.= "</p>\n";

        // Count
        $textToDisplay.= '<p><label for="count_' . $widget_id . '">' . $website->t("articles.count") . ':';
        $textToDisplay.= '<span class="required">*</span><br />' . "\n";
        $textToDisplay.= '<input type="number" id="count_' . $widget_id . '" ';
        $textToDisplay.= 'name="count_' . $widget_id . '" value="' . $count . '" />';
        $textToDisplay.= "</p>";

        // Display type
        $textToDisplay.= '<p><label for="display_type_' . $widget_id . '">' . $website->t("articles.display_type") . ':';
        $textToDisplay.= '<span class="required">*</span><br />' . "\n";
        $textToDisplay.= '<select name="display_type_' . $widget_id . '" id="display_type_' . $widget_id . '">';
        $textToDisplay.= $this->getSelectOption(
                $website->t("articles.display_type.without_metadata"), self::TYPE_WITHOUT_METADATA, $display_type);
        $textToDisplay.= $this->getSelectOption(
                $website->t("articles.display_type.with_metadata"), self::TYPE_WITH_METADATA, $display_type);
        $textToDisplay.= $this->getSelectOption(
                $website->t("articles.display_type.list"), self::TYPE_LIST, $display_type);
        $textToDisplay.= $this->getSelectOption(
                $website->t("articles.display_type.list_with_images"), self::TYPE_LIST_WITH_IMAGES, $display_type);
        $textToDisplay.= "</select>\n";
        $textToDisplay.= "</p>\n";

        // Order
        $textToDisplay.= '<p><label for="order_' . $widget_id . '">' . $website->t("articles.order") . ':';
        $textToDisplay.= '<span class="required">*</span><br />' . "\n";
        $textToDisplay.= '<select name="order_' . $widget_id . '" id="dorder_' . $widget_id . '">';
        $textToDisplay.= $this->getSelectOption(
                $website->t("articles.order.newest_top"), self::SORT_NEWEST_TOP, $order);
        $textToDisplay.= $this->getSelectOption(
                $website->t("articles.order.oldest_top"), self::SORT_OLDEST_TOP, $order);
        $textToDisplay.= "</select>\n";
        $textToDisplay.= "</p>\n";

        // Archive
        $checked = $archive ? 'checked="checked"' : "";
        $textToDisplay.= <<<EOT
            <p>
                <label for="archive_$widget_id">{$website->t("articles.archive")}:</label>
                <input class="checkbox" type="checkbox" name="archive_$widget_id" id="archive_$widget_id" $checked />
            </p>
EOT;

        return $textToDisplay;
    }

    private function getSelectOption($display, $value_of_this_option, $current_value) {
        $textToDisplay = '<option value="' . $value_of_this_option . '"';
        if ($value_of_this_option == $current_value) {
            $textToDisplay.= ' selected="selected"';
        }
        $textToDisplay.= ">" . $display;
        $textToDisplay.= "</option>\n";
        return $textToDisplay;
    }

    public function parseData(Website $website, $id) {
        $data = [];

        // Title
        $data["title"] = trim($website->getRequestString("title_" . $id, ""));
        if (strLen($data["title"]) > 200) {
            $website->addError($website->t("widgets.title") . " " . $website->t("errors.is_too_long_num", 200));
            $data["valid"] = false;
        }

        // Categories
        $categories = isSet($_REQUEST["categories_" . $id]) ? $_REQUEST["categories_" . $id] : [];
        if (!is_array($categories)) {
            // Check for valid array
            $website->addError($website->tReplacedKey("errors.none_set", "main.categories", true));
            $data["valid"] = false;
            $categories = [];
        }
        // Add all categories to the real array
        $data["categories"] = [];
        foreach ($categories as $category_id) {
            $category_id = (int) $category_id;
            if ($category_id > 0) {
                $data["categories"][] = $category_id;
            }
        }
        // Check the real array
        if (count($data["categories"]) == 0) {
            $website->addError($website->tReplacedKey("errors.none_set", "main.categories", true));
            $data["valid"] = false;
        }

        // Count
        if (isSet($_REQUEST["count_" . $id])) {
            $data["count"] = (int) $_REQUEST["count_" . $id];
            if (!Validate::range($data["count"], 1, 20)) {
                $website->addError($website->t("articles.count") . " " . Validate::getLastError($website));
                $data["valid"] = false;
            }
        } else {
            $website->addError($website->t("articles.count") . " " . $website->t("errors.not_found"));
            $data["valid"] = false;
        }

        // Display type
        if (isSet($_REQUEST["display_type_" . $id])) {
            $data["display_type"] = (int) $_REQUEST["display_type_" . $id];
            if ($data["display_type"] != self::TYPE_LIST &&
                    $data["display_type"] != self::TYPE_WITHOUT_METADATA &&
                    $data["display_type"] != self::TYPE_WITH_METADATA &&
                    $data["display_type"] != self::TYPE_LIST_WITH_IMAGES) {
                $website->addError($website->t("articles.count") . " " . $website->t("errors.not_found"));
                $data["valid"] = false;
            }
        } else {
            $website->addError($website->t("articles.count") . " " . $website->t("errors.not_found"));
            $data["valid"] = false;
        }

        // Order
        if (isSet($_REQUEST["order_" . $id])) {
            $data["order"] = (int) $_REQUEST["order_" . $id];
            if ($data["order"] != self::SORT_NEWEST_TOP &&
                    $data["order"] != self::SORT_OLDEST_TOP) {
                $website->addError($website->t("articles.order") . " " . $website->t("errors.not_found"));
                $data["valid"] = false;
            }
        } else {
            $website->addError($website->t("articles.order") . " " . $website->t("errors.not_found"));
            $data["valid"] = false;
        }

        // Archive
        if (isSet($_REQUEST["archive_" . $id])) {
            $data["archive"] = true;
        } else {
            $data["archive"] = false;
        }

        return $data;
    }

}

// Register itself
$this->registerWidget(new WidgetArticles());
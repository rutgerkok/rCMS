<?php

$this->register_widget(new WidgetRkokArticles());

class WidgetRkokArticles extends WidgetDefinition {

    const TYPE_WITHOUT_METADATA = 0;
    const TYPE_WITH_METADATA = 1;
    const TYPE_LIST = 2;

    public function get_widget(Website $oWebsite, $id, $data) {
        if (!isset($data["title"]) || !isset($data["count"])
                || !isset($data["display_type"]) || !isset($data["categories"])) {
            return;
        }

        $return_value = "";
        if (strlen($data["title"]) > 0) {
            $return_value.= "<h2>" . $data["title"] . "</h2>";
        }

        $categories = $data["categories"];
        $articles_count = (int) $data["count"];
        $display_type = (int) $data["display_type"];
        $oArticles = new Articles($oWebsite, $oWebsite->get_database());
        if ($display_type == self::TYPE_LIST) {
            // As list
            $return_value.= $oArticles->get_articles_small_list($categories, $articles_count);
        } elseif ($display_type == self::TYPE_WITH_METADATA) {
            // As paragraphs with metadata
            $return_value.= $oArticles->get_articles_list_category($categories, true, $articles_count);
        } else { // So $display_type should be 0
            // As paragrapsh without metadata
            $return_value.= $oArticles->get_articles_list_category($categories, false, $articles_count);
        }
        unset($categories, $articles_count, $display_type, $oArticles);
        return $return_value;
    }

    public function get_editor(Website $oWebsite, $widget_id, $data) {
        $title = isset($data["title"]) ? $data["title"] : "";
        $categories = isset($data["categories"]) ? $data["categories"] : array();
        $count = isset($data["count"]) ? $data["count"] : 4;
        $display_type = isset($data["display_type"]) ? $data["display_type"] : self::TYPE_WITHOUT_METADATA;

        // Title
        $text_to_display = "<p>\n";
        $text_to_display.= '<label for="title_' . $widget_id . '">';
        $text_to_display.= $oWebsite->t("widgets.title") . "</label>:<br />\n";
        $text_to_display.= '<input type="text" name="title_' . $widget_id . '" id="title_' . $widget_id . '"';
        $text_to_display.= 'value="' . $title . '" />' . "\n";
        $text_to_display.= "</p>\n";

        // Categories
        $oCategories = new Categories($oWebsite, $oWebsite->get_database());
        $text_to_display.= "<p>" . $oWebsite->t("main.categories") . ':';
        $text_to_display.= '<span class="required">*</span><br />' . "\n";
        foreach ($oCategories->get_categories() as $category_id => $name) {
            $checkbox_id = 'categories_' . $category_id . "_" . $widget_id;
            $text_to_display.= '<input type="checkbox" class="checkbox" ';
            $text_to_display.= 'name="categories_' . $widget_id . '[]" ';
            if (array_search($category_id, $categories) !== false) {
                $text_to_display.= 'checked="checked" ';
            }
            $text_to_display.= 'id="' . $checkbox_id . '" value="' . $category_id . '" />';
            $text_to_display.= '<label for="' . $checkbox_id . '">' . $name . "</label><br />" . "\n";
        }
        $text_to_display.= "</p>\n";

        // Count
        $text_to_display.= '<p><label for="count_' . $widget_id . '">' . $oWebsite->t("articles.article_count") . ':';
        $text_to_display.= '<span class="required">*</span><br />' . "\n";
        $text_to_display.= '<input type="number" id="count_' . $widget_id . '" ';
        $text_to_display.= 'name="count_' . $widget_id . '" value="' . $count . '" />';
        $text_to_display.= "</p>";

        // Display type
        $text_to_display.= '<p><label for="display_type_' . $widget_id . '">' . $oWebsite->t("articles.display_type") . ':';
        $text_to_display.= '<span class="required">*</span><br />' . "\n";
        $text_to_display.= '<select name="display_type_' . $widget_id . '" id="display_type_' . $widget_id . '">';
        $text_to_display.= $this->get_select_option(
                $oWebsite->t("articles.display_type.without_metadata"), self::TYPE_WITHOUT_METADATA, $display_type);
        $text_to_display.= $this->get_select_option(
                $oWebsite->t("articles.display_type.with_metadata"), self::TYPE_WITH_METADATA, $display_type);
        $text_to_display.= $this->get_select_option(
                $oWebsite->t("articles.display_type.list"), self::TYPE_LIST, $display_type);
        $text_to_display.= "</select>\n";
        $text_to_display.= "</p>\n";

        return $text_to_display;
    }

    private function get_select_option($display, $value_of_this_option, $current_value) {
        $text_to_display = '<option value="' . $value_of_this_option . '"';
        if ($value_of_this_option == $current_value) {
            $text_to_display.= ' selected="selected"';
        }
        $text_to_display.= ">" . $display;
        $text_to_display.= "</option>\n";
        return $text_to_display;
    }

    public function parse_data(Website $oWebsite, $id) {
        $data = array();

        // Title
        $data["title"] = isset($_REQUEST["title_" . $id]) ? $_REQUEST["title_" . $id] : "";
        $data["title"] = htmlspecialchars(trim($data["title"]));
        if (strlen($data["title"]) > 200) {
            $oWebsite->add_error($oWebsite->t("widgets.title") . " " . $oWebsite->t("errors.is_too_long_num", 200));
            $data["valid"] = false;
        }

        // Categories
        $categories = isset($_REQUEST["categories_" . $id]) ? $_REQUEST["categories_" . $id] : array();
        if (!is_array($categories)) {
            // Check for valid array
            $oWebsite->add_error($oWebsite->t_replaced_key("errors.none_set", "main.categories", true));
            $data["valid"] = false;
            $categories = array();
        }
        // Add all categories to the real array
        $data["categories"] = array();
        foreach ($categories as $category_id) {
            $category_id = (int) $category_id;
            if ($category_id > 0) {
                $data["categories"][] = $category_id;
            }
        }
        // Check the real array
        if (count($data["categories"]) == 0) {
            $oWebsite->add_error($oWebsite->t_replaced_key("errors.none_set", "main.categories", true));
            $data["valid"] = false;
        }

        // Count
        if (isset($_REQUEST["count_" . $id])) {
            $data["count"] = (int) $_REQUEST["count_" . $id];
            if (!Validate::range($data["count"], 1, 20)) {
                $oWebsite->add_error($oWebsite->t("articles.article_count") . " " . Validate::get_last_error($oWebsite));
                $data["valid"] = false;
            }
        } else {
            $oWebsite->add_error($oWebsite->t("articles.article_count") . " " . $oWebsite->t("errors.not_found"));
            $data["valid"] = false;
        }

        // Display type
        if (isset($_REQUEST["display_type_" . $id])) {
            $data["display_type"] = (int) $_REQUEST["display_type_" . $id];
            if ($data["display_type"] != self::TYPE_LIST &&
                    $data["display_type"] != self::TYPE_WITHOUT_METADATA &&
                    $data["display_type"] != self::TYPE_WITH_METADATA) {
                $oWebsite->add_error($oWebsite->t("articles.article_count") . " " . $oWebsite->t("errors.not_found"));
                $data["valid"] = false;
            }
        } else {
            $oWebsite->add_error($oWebsite->t("articles.article_count") . " " . $oWebsite->t("errors.not_found"));
            $data["valid"] = false;
        }

        return $data;
    }

}

?>
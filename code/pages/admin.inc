<h2><?php echo $this->t("main.admin") ?></h2> <!-- admin -->
<?php
//OBJECTEN
$oDB = $this->get_database();
$oAuth = new Authentication($this);
$oCat = new Categories($oDB, $this);
unset($oDB); //al niet meer nodig, want doorgegeven als parameter
//AUTHENTICATIE
$logged_in = $oAuth->check(true, true);
unset($oAuth);


//CATEGORIEENLIJST
$cat_rename_list = '';
$cat_delete_list = '';

$cat_array = $oCat->get_categories();

if (count($cat_array) > 0) {
    $cat_rename_list = '<p>';
    $cat_delete_list = '<p>';

    foreach ($cat_array as $id => $name) {
        //hernoemlijst
        $cat_rename_list.= '<a href="' . $this->get_url_page("rename_category", $id) . '" class="arrow">';
        $cat_rename_list.= $this->t("main.edit") . ' \'' . $name . '\'</a><br />';

        //verwijderlijst
        if ($id != 1) {
            $cat_delete_list.= '<a href="' . $this->get_url_page("delete_category", $id) . '" class="arrow">';
            $cat_delete_list.= $this->t("main.delete") . ' \'' . $name . '\'</a><br />';
        } else { // "No category" category cannot be removed
            $cat_delete_list.= '<a href="' . $this->get_url_page("delete_category", $id) . '" class="arrow-disabled" title="Cannot delete a standard category!">';
            $cat_delete_list.= $this->t("main.delete") . ' \'' . $name . '\'</a><br />';
        }
    }

    $cat_rename_list.= '</p>';
    $cat_delete_list.= '</p>';
}

//FOUTAFHANDELING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

//PAGINA WEERGEVEN
if ($logged_in) {
    ?>

    <p>
        <a href="<?php echo $this->get_url_page("site_settings") ?>" class="arrow"><?php echo $this->t("main.site_settings") ?></a><br />
        <a href="<?php echo $this->get_url_page("installing_database") ?>" class="arrow"><?php echo $this->t("main.upgrade_database") ?></a>
    </p>

    <h3><?php echo $this->t("main.articles") ?></h3>
    <p>
        <a href="<?php echo $this->get_url_page("edit_article") ?>" class="arrow"><?php echo $this->t("articles.create") ?></a><br /> <!-- new article -->
        <a href="<?php echo $this->get_url_page("comments") ?>" class="arrow"><?php echo $this->t("comments.comments") ?></a> <!-- latest comments -->
    </p>

    <h3><?php echo $this->t("users.account_management") ?></h3>
    <p>
        <a href="<?php echo $this->get_url_page("create_account") ?>" class="arrow"><?php echo $this->t("users.create") ?></a><br /> <!-- voeg account toe -->
        <a class="arrow" href="<?php echo $this->get_url_page("change_password") ?>"><?php echo $this->t("editor.password.edit") ?></a><br /><!-- verander wachtwoord -->
        <a class="arrow" href="<?php echo $this->get_url_page("edit_email") ?>"><?php echo $this->t("editor.email.edit") ?></a><br /><!-- verander email -->
        <a class="arrow" href="<?php echo $this->get_url_page("change_display_name") ?>"><?php echo $this->t("editor.display_name.edit") ?></a><br /><!-- verander weergavenaam -->
        <a class="arrow" href="<?php echo $this->get_url_page("account_management") ?>"><?php echo $this->t("users.account_management") ?></a><br /> <!-- accountbeheer -->
    </p>

    <h3><?php echo $this->t("main.links") ?></h3>
    <p>
        <a href="<?php echo $this->get_url_page("create_link") ?>" class="arrow"><?php echo $this->t("links.create") ?></a><br />
        <a href="<?php echo $this->get_url_page("links") ?>" class="arrow"><?php echo $this->t("links.edit_or_delete") ?></a><br />
    </p>

    <h3><?php echo $this->t("main.categories") ?></h3>
    <p>
        <a href="<?php echo $this->get_url_page("create_category") ?>" class="arrow"><?php echo $this->t("categories.create") ?></a><!--nieuwe categorie-->
    </p>
    <?php
    echo $cat_rename_list;
    echo $cat_delete_list;
    ?>

    <h3><?php echo $this->t("main.widgets") ?></h3>
    <p>
        <a href="<?php echo $this->get_url_page("widgets") ?>" class="arrow"><?php echo $this->t("main.edit") ?></a>
    </p>

    <?php
}
?>

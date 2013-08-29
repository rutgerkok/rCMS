<h2><?php echo $this->t("main.admin") ?></h2> <!-- admin -->
<?php
//OBJECTEN
$oDB = $this->getDatabase();
$oAuth = new Authentication($this);
$oCat = new Categories($oDB, $this);
unset($oDB); //al niet meer nodig, want doorgegeven als parameter
//AUTHENTICATIE
$loggedIn = $oAuth->check(true, true);
unset($oAuth);


//CATEGORIEENLIJST
$cat_rename_list = '';
$cat_delete_list = '';

$cat_array = $oCat->getCategories();

if (count($cat_array) > 0) {
    $cat_rename_list = '<p>';
    $cat_delete_list = '<p>';

    foreach ($cat_array as $id => $name) {
        //hernoemlijst
        $cat_rename_list.= '<a href="' . $this->getUrlPage("rename_category", $id) . '" class="arrow">';
        $cat_rename_list.= $this->t("main.edit") . ' \'' . $name . '\'</a><br />';

        //verwijderlijst
        if ($id != 1) {
            $cat_delete_list.= '<a href="' . $this->getUrlPage("delete_category", $id) . '" class="arrow">';
            $cat_delete_list.= $this->t("main.delete") . ' \'' . $name . '\'</a><br />';
        } else { // "No category" category cannot be removed
            $cat_delete_list.= '<a href="' . $this->getUrlPage("delete_category", $id) . '" class="arrow-disabled" title="Cannot delete a standard category!">';
            $cat_delete_list.= $this->t("main.delete") . ' \'' . $name . '\'</a><br />';
        }
    }

    $cat_rename_list.= '</p>';
    $cat_delete_list.= '</p>';
}

//FOUTAFHANDELING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

//PAGINA WEERGEVEN
if ($loggedIn) {
    ?>

    <p>
        <a href="<?php echo $this->getUrlPage("site_settings") ?>" class="arrow"><?php echo $this->t("main.site_settings") ?></a><br />
        <a href="<?php echo $this->getUrlPage("widgets") ?>" class="arrow"><?php echo $this->t("main.edit") . " " . $this->t("main.widgets") ?></a>
    </p>

    <h3><?php echo $this->t("main.articles") ?></h3>
    <p>
        <a href="<?php echo $this->getUrlPage("edit_article") ?>" class="arrow"><?php echo $this->t("articles.create") ?></a><br /> <!-- new article -->
        <a href="<?php echo $this->getUrlPage("comments") ?>" class="arrow"><?php echo $this->t("comments.comments") ?></a> <!-- latest comments -->
    </p>

    <h3><?php echo $this->t("users.account_management") ?></h3>
    <p>
        <a href="<?php echo $this->getUrlPage("create_account") ?>" class="arrow"><?php echo $this->t("users.create") ?></a><br /> <!-- voeg account toe -->
        <a class="arrow" href="<?php echo $this->getUrlPage("edit_password") ?>"><?php echo $this->t("editor.password.edit") ?></a><br /><!-- verander wachtwoord -->
        <a class="arrow" href="<?php echo $this->getUrlPage("edit_email") ?>"><?php echo $this->t("editor.email.edit") ?></a><br /><!-- verander email -->
        <a class="arrow" href="<?php echo $this->getUrlPage("edit_display_name") ?>"><?php echo $this->t("editor.display_name.edit") ?></a><br /><!-- verander weergavenaam -->
        <a class="arrow" href="<?php echo $this->getUrlPage("account_management") ?>"><?php echo $this->t("users.account_management") ?></a><br /> <!-- accountbeheer -->
    </p>

    <h3><?php echo $this->t("main.links") ?></h3>
    <p>
        <a href="<?php echo $this->getUrlPage("create_link") ?>" class="arrow"><?php echo $this->t("links.create") ?></a><br />
        <a href="<?php echo $this->getUrlPage("links") ?>" class="arrow"><?php echo $this->t("links.edit_or_delete") ?></a><br />
    </p>

    <h3><?php echo $this->t("main.categories") ?></h3>
    <p>
        <a href="<?php echo $this->getUrlPage("create_category") ?>" class="arrow"><?php echo $this->t("categories.create") ?></a><!--nieuwe categorie-->
    </p>
    <?php
    echo $cat_rename_list;
    echo $cat_delete_list;

}
?>

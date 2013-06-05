<?php

$oDB = $this->get_database();
$already_installed = $oDB->rows($oDB->query('SELECT gebruiker_wachtwoord FROM `gebruikers` LIMIT 0,1', false)) > 0;
$logged_in_staff = $this->logged_in_staff(true);
$confirmed = isset($_REQUEST['confirm']);
if (!$already_installed || ($logged_in_staff && $confirmed)) {
    // Create tables
    $oDB->create_tables();

    // Login as admin
    $oAuth = new Authentication($this);
    $oAuth->log_in("admin", "admin");

    // Show message
    if(!$already_installed) {
        echo <<<EOT
            <h2>Installed</h2>
            <p>
                The database is now installed! Your username is 
                <strong>"admin"</strong> and your password is also
                <strong>"admin"</strong>. Let's change that!
            </p>
            <p>
                <a class="arrow" href="{$this->get_url_page("change_password")}">
                    Change password
                </a>
            </p>

EOT;
    } else {
        echo <<<EOT
            <h2>Upgraded</h2>
            <p>
                All missing tables (if any) have been added.
            </p>
            <p>
                <a class="arrow" href="{$this->get_url_page("admin")}">
                    {$this->t("main.admin")}
                </a>
            </p>
EOT;
    }
}
if ($logged_in_staff && !$confirmed && $already_installed) {
    $link_confirm = $this->get_url_page("installing_database", 0, array("confirm" => 1));
    echo <<<EOT
        <h2>Upgrading database</h2>
        <p>
            This will add any missing tables.
        </p>
        <p>
            <a class="arrow" href="$link_confirm">Yes</a> |
            <a class="arrow" href="{$this->get_url_main()}">No</a>
        </p>
EOT;
}
?>
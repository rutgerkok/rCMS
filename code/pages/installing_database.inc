<?php

$oDB = $this->get_database();
$installed_and_up_to_date = $oDB->is_up_to_date();
$logged_in_staff = $this->logged_in_staff(true);
$confirmed = isset($_REQUEST['confirm']);
if (!$installed_and_up_to_date) {
    // Create/update tables
    $result = $oDB->update_tables();

    // Login as admin
    $oAuth = new Authentication($this);
    $oAuth->log_in("admin", "admin");

    if($result == 2) {
        // Tables were installed
        echo <<<EOT
            <h2>Installed</h2>
            <p>
                The database is now installed! Your username is 
                <strong>"admin"</strong> and your password is also
                <strong>"admin"</strong>. Let's change that!
            </p>
            <p>
                <a class="arrow" href="{$this->get_url_page("edit_password")}">
                    Change password
                </a>
            </p>
EOT;
    } else {
        // Tables were updated
        echo <<<EOT
            <h2>Upgraded</h2>
            <p>
                All missing tables have been added, existing tables have been updated.
            </p>
            <p>
                <a class="arrow" href="{$this->get_url_page("admin")}">
                    {$this->t("main.admin")}
                </a>
            </p>
EOT;
    }
} elseif($logged_in_staff) {
    echo <<<EOT
        <h2>Nothing to do</h2>
        <p>Tables are already installed and up to date.</p>
        <p>
            <a class="arrow" href="{$this->get_url_page("admin")}">
                {$this->t("main.admin")}
            </a>
        </p>
EOT;
}
?>
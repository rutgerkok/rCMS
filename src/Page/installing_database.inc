<?php

namespace Rcms\Core;

$oDB = $this->getDatabase();
$installed_and_up_to_date = $oDB->isUpToDate();
$loggedInStaff = $this->isLoggedInAsStaff(true);
$confirmed = isSet($_REQUEST['confirm']);
if (!$installed_and_up_to_date) {
    // Create/update tables
    $result = $oDB->updateTables();

    // Login as admin
    $oAuth = new Authentication($this);
    $oAuth->logIn("admin", "admin");

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
                <a class="arrow" href="{$this->getUrlPage("edit_password")}">
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
                <a class="arrow" href="{$this->getUrlPage("admin")}">
                    {$this->t("main.admin")}
                </a>
            </p>
EOT;
    }
} elseif($loggedInStaff) {
    echo <<<EOT
        <h2>Nothing to do</h2>
        <p>Tables are already installed and up to date.</p>
        <p>
            <a class="arrow" href="{$this->getUrlPage("admin")}">
                {$this->t("main.admin")}
            </a>
        </p>
EOT;
}
?>
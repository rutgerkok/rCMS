<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\DatabaseInstaller;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Core\NotFoundException;

use Rcms\Template\EmptyTemplate;
use Rcms\Template\DatabaseInstallTemplate;
use Rcms\Template\InstallationCompletedTemplate;
use Rcms\Template\NoDatabaseConnectionTemplate;
use Rcms\Template\UpdateCompletedTemplate;

class InstallPage extends Page {

    /**
     * @var int The database state.
     */
    private $databaseState;
    
    private $justInstalled;

    public function init(Website $website, Request $request) {
        if ($website->getConfig()->isDatabaseUpToDate()) {
            // Pretend page does not exist if database is already installed
            throw new NotFoundException();
        }

        $installer = new DatabaseInstaller();
        $this->databaseState = $installer->getDatabaseState($website);

        if ($this->databaseState == DatabaseInstaller::STATE_OUTDATED ||
                ($this->databaseState == DatabaseInstaller::STATE_NOT_INSTALLED 
                && $request->getRequestString("action") === "install_database")) {
            $installer->createOrUpdateTables($website);
            $this->justInstalled = true;
        }

        if ($this->databaseState == DatabaseInstaller::STATE_FROM_FUTURE) {
            $text = $website->getText();
            $text->addError($text->t("install.database_version_from_future"));
        }
    }

    public function getPageTitle(Text $text) {
        return $text->t("install.installing_database");
    }

    public function getTemplate(Text $text) {
        if ($this->databaseState == DatabaseInstaller::STATE_NOT_CONNECTED) {
            return new NoDatabaseConnectionTemplate($text);
        } else if ($this->databaseState == DatabaseInstaller::STATE_NOT_INSTALLED) {
            if ($this->justInstalled) {
                return new InstallationCompletedTemplate($text);
            }
            return new DatabaseInstallTemplate($text);
        } else if ($this->databaseState == DatabaseInstaller::STATE_OUTDATED) {
            return new UpdateCompletedTemplate($text);
        } else {
            return new EmptyTemplate($text);
        }
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}

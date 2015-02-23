<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\Website;

use Rcms\Core\Document\DocumentRepository;

use Rcms\Page\View\DocumentListView;

/**
 * Page that lists all documents.
 */
class DocumentListPage extends Page {
    
    private $documents;
    private $editLinks;
    
    public function init(Website $website, Request $request) {
        $isStaff = $website->isLoggedInAsStaff();

        $documentRepo = new DocumentRepository($website->getDatabase(), $isStaff);
        $this->documents = $documentRepo->getAll();
        $this->editLinks = $isStaff;
    }

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageTitle(Text $text) {
        return $text->t("documents.list.title");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getView(Text $text) {
        return new DocumentListView($text, $this->documents, $this->editLinks);
    }

}

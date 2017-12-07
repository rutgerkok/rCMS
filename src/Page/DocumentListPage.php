<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\Website;

use Rcms\Core\Document\DocumentRepository;

use Rcms\Template\DocumentListTemplate;

/**
 * Page that lists all documents.
 */
class DocumentListPage extends Page {
    
    private $documents;
    private $editLinks;
    
    public function init(Website $website, Request $request) {
        $isStaff = $request->hasRank($website, Authentication::RANK_ADMIN);

        $documentRepo = new DocumentRepository($website->getDatabase(), $isStaff);
        $this->documents = $documentRepo->getAll();
        $this->editLinks = $isStaff;
    }

    public function getMinimumRank() {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageTitle(Text $text) {
        return $text->t("documents.list.title");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getTemplate(Text $text) {
        return new DocumentListTemplate($text, $this->documents, $this->editLinks);
    }

}

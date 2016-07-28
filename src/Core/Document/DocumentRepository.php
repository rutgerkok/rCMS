<?php

namespace Rcms\Core\Document;

use PDO;
use PDOException;
use Rcms\Core\NotFoundException;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\WidgetRepository;

/**
 * Repository for documents.
 * @see Document
 */
class DocumentRepository extends Repository {

    const TABLE_NAME = "documents";

    protected $primaryField;
    protected $titleField;
    protected $introField;
    protected $hiddenField;
    protected $createdField;
    protected $editedField;
    protected $userIdField;
    protected $parentIdField;

    /** @var boolean Whether hidden documents should be displayed. */
    private $showHiddenDocuments;

    /**
     * Creates a new document repository.
     * @param PDO $pdo Database to get documents from.
     * @param boolean $showHiddenDocuments True if documents flagged as hidden
     * must be shown anyways, false otherwise.
     */
    public function __construct(PDO $pdo, $showHiddenDocuments) {
        parent::__construct($pdo);

        $this->primaryField = new Field(Field::TYPE_PRIMARY_KEY, "id", "document_id");
        $this->titleField = new Field(Field::TYPE_STRING, "title", "document_title");
        $this->introField = new Field(Field::TYPE_STRING, "intro", "document_intro");
        $this->hiddenField = new Field(Field::TYPE_BOOLEAN, "hidden", "document_hidden");
        $this->createdField = new Field(Field::TYPE_DATE, "created", "document_created");
        $this->editedField = new Field(Field::TYPE_DATE, "edited", "document_edited");
        $this->userIdField = new Field(Field::TYPE_INT, "userId", "user_id");
        $this->parentIdField = new Field(Field::TYPE_INT, "parentId", "document_parent_id");

        $this->showHiddenDocuments = (boolean) $showHiddenDocuments;
    }

    public function getAllFields() {
        return [$this->primaryField, $this->titleField, $this->introField,
            $this->hiddenField, $this->createdField, $this->editedField,
            $this->userIdField, $this->parentIdField];
    }

    public function createEmptyObject() {
        return new Document();
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getPrimaryKey() {
        return $this->primaryField;
    }

    public function whereRaw($sql, $params) {
        // Overridden to modify SQL, so that hidden articles stay hidden
        if (!$this->showHiddenDocuments) {
            if (!empty($sql)) {
                $sql = "({$sql}) AND ";
            }
            $sql.= "`{$this->hiddenField->getNameInDatabase()}` = 0";
        }

        return parent::whereRaw($sql, $params);
    }

    /**
     * Gets all documents currently on the server.
     * @return Document[] The documents.
     */
    public function getAll() {
        return $this->all()->select();
    }

    /**
     * Gets a single document by its id.
     * @param int $id The document id.
     * @return Document The document.
     * @throws NotFoundException If the document with the given id is not found.
     */
    public function getDocument($id) {
        return $this
                        ->where($this->primaryField, '=', (int) $id)
                        ->selectOneOrFail();
    }

    public function getDocumentOrWidgetArea(InstalledWidgets $installedWidgets,
            Text $text, $id) {
        try {
            return $this->getDocument($id);
        } catch (NotFoundException $ex) {
            return Document::createForWidgetArea($installedWidgets, $text, $id);
        }
    }

    /**
     * Saves a document to the database.
     * @param Document $document The document to save.
     * @throws PDOException If a database error occurs while saving the document.
     */
    public function saveDocument(Document $document) {
        $this->saveEntity($document);
    }

    protected function canBeSaved(Entity $document) {
        if (!($document instanceof Document)) {
            return false;
        }

        return parent::canBeSaved($document) 
                && !$document->isForWidgetArea() 
                && Document::isValidIntro($document->getIntro()) 
                && Document::isValidTitle($document->getTitle());
    }

    /**
     * Deletes a document from the database.
     * @param Document $document The document to delete.
     * @throws NotFoundException If the document was already deleted or never saved.
     * @throws PDOException If a database error occurs.
     */
    public function deleteDocument(Document $document,
            WidgetRepository $widgetRepo) {
        $this->where($this->primaryField, '=', $document->getId())->deleteOneOrFail();
        $widgetRepo->deleteAllPlacedWidgetsInDocument($document->getId());
    }

}

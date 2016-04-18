<?php

namespace Rcms\Core\Document;

use PHPUnit_Framework_TestCase;
use Rcms\Core\User;

class DocumentTest extends PHPUnit_Framework_TestCase {
    
    public function getTestUser() {
        $user = new User();
        $user->setDisplayName("John Doe");
        $user->setUsername("testuser");
        return $user;
    }

    public function testBasics() {
        $document = Document::createNew("Test Title", "Test Intro", $this->getTestUser());

        $this->assertEquals("Test Title", $document->getTitle());
        $this->assertEquals("Test Intro", $document->getIntro());
        $this->assertEquals(0, $document->getId()); // new documents have id 0
        $this->assertFalse($document->isForWidgetArea());
    }

    public function testTitleValidation() {
        $goodTitle = "Test Title";
        $tooLongTitle = str_repeat("t", Document::TITLE_MAX_LENGTH + 1);
        $notTooLongTitle = str_repeat("t", Document::TITLE_MAX_LENGTH);
        $emptyTitle = "";

        $this->assertTrue(Document::isValidTitle($goodTitle));
        $this->assertFalse(Document::isValidTitle($tooLongTitle));
        $this->assertTrue(Document::isValidTitle($notTooLongTitle));
        $this->assertFalse(Document::isValidTitle($emptyTitle));
    }

    public function testIntroValidation() {
        $goodIntro = "Test Intro";
        $tooLongIntro = str_repeat("t", Document::INTRO_MAX_LENGTH + 1);
        $notTooLongIntro = str_repeat("t", Document::INTRO_MAX_LENGTH);
        $emptyIntro = "";

        $this->assertTrue(Document::isValidIntro($goodIntro));
        $this->assertFalse(Document::isValidIntro($tooLongIntro));
        $this->assertTrue(Document::isValidIntro($notTooLongIntro));
        $this->assertTrue(Document::isValidIntro($emptyIntro));
    }

    public function testInvalidTitleInSetter() {
        $document = Document::createNew("Test Title", "Test Intro", $this->getTestUser());

        $this->assertTrue(Document::isValidTitle($document->getTitle()));
        $document->setTitle("");
        $this->assertFalse(Document::isValidTitle($document->getTitle()));
    }

    public function testInvalidTitleInConstructor() {
        $document = Document::createNew("", "Test Intro", $this->getTestUser());
        $this->assertFalse(Document::isValidTitle($document->getTitle()));
    }

    public function testInvalidIntroInSetter() {
        $document = Document::createNew("Test Title", "Test Intro", $this->getTestUser());

        $this->assertTrue(Document::isValidIntro($document->getIntro()));
        $document->setIntro(str_repeat("t", Document::INTRO_MAX_LENGTH + 1));
        $this->assertFalse(Document::isValidIntro($document->getIntro()));
    }

    public function testInvalidIntroInConstructor() {
        $invalidIntro = str_repeat("t", Document::INTRO_MAX_LENGTH + 1);
        $document = Document::createNew("Test Title", $invalidIntro, $this->getTestUser());
        $this->assertFalse(Document::isValidIntro($document->getIntro()));
    }
}

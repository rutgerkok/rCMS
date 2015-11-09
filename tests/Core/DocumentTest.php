<?php

namespace Rcms\Core\Document;

use PHPUnit_Framework_TestCase;
use Rcms\Core\User;

class DocumentTest extends PHPUnit_Framework_TestCase {

    public function testBasics() {
        $testUser = User::createNewUser("test", "John Doe", "password1");
        $document = Document::createNew("Test Title", "Test Intro", $testUser);

        $this->assertEquals("Test Title", $document->getTitle());
        $this->assertEquals("Test Intro", $document->getIntro());
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

}

<?php

namespace Rcms\Core;

use DateTime;
use PHPUnit_Framework_TestCase;

use Rcms\Core\Repository\Field;

class ArticleTest extends PHPUnit_Framework_TestCase {
    
    private function getTestUser() {
        $user = new User();
        $user->setDisplayName("John Doe");
        $user->setUsername("testuser");
        // Make user look like existing user by setting a primary key
        $user->setField(new Field(Field::TYPE_PRIMARY_KEY, "id", "foo"), 10);
        return $user;
    }
    
    private function getNewTestArticle() {
        return Article::createArticle($this->getTestUser());
    }
    
    public function testBasics() {
        $article = $this->getNewTestArticle();
        $article->setTitle("Test Title");
        $article->setIntro("Test Intro");
        $article->setBody("Test Body");

        $this->assertEquals("Test Title", $article->getTitle());
        $this->assertEquals("Test Intro", $article->getIntro());
        $this->assertEquals("Test Body", $article->getBody());
        $this->assertEquals(0, $article->getId());
        $this->assertEquals(null, $article->getDateLastEdited());
        $this->assertTrue($article->getDateCreated() instanceof DateTime);
    }
    
    public function testHiding() {
        $article = $this->getNewTestArticle();

        $this->assertEquals(false, $article->isHidden());
        $article->setHidden(true);
        $this->assertEquals(true, $article->isHidden());
        $article->setHidden(false);
        $this->assertEquals(false, $article->isHidden());
    }
    
    public function testLastEdited() {
        $article = $this->getNewTestArticle();

        // Last edited date shouldn't change when article is still new
        $this->assertEquals(null, $article->getDateLastEdited());
        $article->setTitle("New title");
        $this->assertEquals(null, $article->getDateLastEdited());

        // Pretend article is stored in database by setting a primary key
        $primaryKey = new Field(Field::TYPE_PRIMARY_KEY, "id", "foo");
        $article->setField($primaryKey, 3);

        // Now last edited date should update
        $article->setTitle("Newer title");
        $this->assertTrue($article->getDateLastEdited() instanceof DateTime);
    }
    
    public function testValidation() {
        $article = $this->getNewTestArticle();
        
        $this->assertFalse($article->canBeSaved()); // no title, body, intro yet

        $article->setTitle("Test Title");
        $article->setIntro("Test Intro");
        $article->setBody("Test Body.");

        $this->assertTrue($article->canBeSaved());
    }
}

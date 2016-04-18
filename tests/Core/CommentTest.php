<?php

namespace Rcms\Core;

use DateTimeInterface;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\UriInterface;
use Rcms\Core\Repository\Field;
use Zend\Diactoros\Uri;

class CommentTest extends PHPUnit_Framework_TestCase {

    public function testVisitorComment() {
        $article = new Article();
        $comment = Comment::createForVisitor("Rutger", "rutger@example.com", $article, "Eloquent reply");

        // Check user data
        $this->assertTrue($comment->isByVisitor());
        $this->assertEquals("Rutger", $comment->getUserDisplayName());
        $this->assertEquals("Rutger", $comment->getUsername());
        $this->assertEquals("rutger@example.com", $comment->getUserEmail());
        $this->assertEquals(0, $comment->getUserId());
        $this->assertEquals(Authentication::RANK_LOGGED_OUT, $comment->getUserRank());

        // Check reply
        $this->assertEquals(0, $comment->getId());
        $this->assertEquals("Eloquent reply", $comment->getBodyRaw());
        $this->assertEquals($article->getId(), $comment->getArticleId());
        $this->assertEquals(Comment::NORMAL_STATUS, $comment->getStatus());

        // Check date
        $this->assertTrue($comment->getDateCreated() instanceof DateTimeInterface);
        $this->assertNull($comment->getDateLastEdited());
    }

    public function testCommentUpdate() {
        $article = new Article();
        $comment = Comment::createForVisitor("Rutger", "rutger@example.com", $article, "Initial body");

        // Comment is changed before saved to the database
        // so last updated field must still be empty
        $comment->setBodyRaw("Old body");

        // Change the body
        $this->assertEquals("Old body", $comment->getBodyRaw());
        $this->assertNull($comment->getDateLastEdited());

        // Pretend that the comment is saved to the database
        $comment->setField(new Field(Field::TYPE_PRIMARY_KEY, "id", "id"), 10);

        // Then change the body again (last edited field should be populated now)
        $comment->setBodyRaw("New body");

        $this->assertEquals("New body", $comment->getBodyRaw());
        $this->assertTrue($comment->getDateLastEdited() instanceof \DateTimeInterface);
    }

    public function testUserComment() {
        $user = $this->getTestUser();
        $article = Article::createArticle($user);

        $comment = Comment::createForUser($user, $article, "Some reply");

        // Check user data
        $this->assertFalse($comment->isByVisitor());
        $this->assertEquals($user->getDisplayName(), $comment->getUserDisplayName());
        $this->assertEquals($user->getUsername(), $comment->getUsername());
        $this->assertEquals($user->getEmail(), $comment->getUserEmail());
        $this->assertEquals($user->getId(), $comment->getUserId());
        $this->assertEquals($user->getRank(), $comment->getUserRank());
    }

    public function testChildComments() {
        $article = new Article();
        $parent = Comment::createForVisitor("Bob", "", $article, "Parent comment");
        $child = Comment::createForVisitor("Sara", "", $article, "Child comment");

        $this->assertEquals([], $parent->getChildComments());
        $parent->setChildComments([$child]);
        $this->assertEquals([$child], $parent->getChildComments());
    }

    public function testUrl() {
        $article = new Article();
        $comment = Comment::createForVisitor("John", "", $article, "Some comment");
        $text = new Text(new Uri("http://example.com/"), ".", new Uri("http://example.com/assets/"));

        // Pretend that the comment is saved to the database
        $comment->setField(new Field(Field::TYPE_PRIMARY_KEY, "id", "id"), 12);

        $this->assertTrue($comment->getUrl($text) instanceof UriInterface);
    }

    private function getTestUser() {
        $user = new User();
        $user->setDisplayName("John Doe");
        $user->setUsername("testuser");
        $user->setRank(Authentication::RANK_USER);
        $user->setField(new Field(Field::TYPE_PRIMARY_KEY, "id", "id"), 10);
        return $user;
    }

}

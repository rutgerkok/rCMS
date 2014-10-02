<?php

namespace Rcms\Core;

use PHPUnit_Framework_TestCase;

class RequestTokenTest extends PHPUnit_Framework_TestCase {

    public function testSameTokensMatch() {
        $token1 = RequestToken::fromString("foo");
        $token2 = RequestToken::fromString("foo");
        $this->assertTrue($token1->matches($token2));
    }

    public function testDifferentTokensDontMatch() {
        $token1 = RequestToken::fromString("foo");
        $token2 = RequestToken::fromString("bar");
        $this->assertFalse($token1->matches($token2));
    }

    public function testEmptyTokensDontMatch() {
        $token1 = RequestToken::fromString("");
        $token2 = RequestToken::fromString("");
        $this->assertFalse($token1->matches($token2));
    }

    public function testGetTokenString() {
        $token1 = RequestToken::fromString("foo");
        $token2 = RequestToken::fromString($token1->getTokenString());
        $this->assertTrue($token1->matches($token2));
    }

    public function testSaveToSession() {
        $token1 = RequestToken::fromString("foo");
        $token1->saveToSession();
        $token2 = RequestToken::fromSession();
        $this->assertTrue($token1->matches($token2));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCannotSaveEmptyTokens() {
        $token = RequestToken::fromString("");
        $token->saveToSession();
    }

    public function testGenerateNew() {
        // Just a basic test to check if there is any randomness at all, and if
        // the token is actually created correctly
        $token1 = RequestToken::generateNew();
        $token2 = RequestToken::generateNew();
        $this->assertFalse($token1->matches($token2));
    }

}

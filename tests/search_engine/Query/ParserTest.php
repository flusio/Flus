<?php

namespace App\search_engine\Query;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    public function testParseText(): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser();
        $stringQuery = 'some text "and more text"';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(3, count($conditions));

        $this->assertTrue($conditions[0]->isTextCondition());
        $this->assertSame('some', $conditions[0]->getValue());

        $this->assertTrue($conditions[1]->isTextCondition());
        $this->assertSame('text', $conditions[1]->getValue());

        $this->assertTrue($conditions[2]->isTextCondition());
        $this->assertSame('and more text', $conditions[2]->getValue());
    }

    public function testParseQualifierUrl(): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser();
        $stringQuery = 'url: https://flus.fr';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertTrue($conditions[0]->isQualifierCondition());
        $this->assertSame('url', $conditions[0]->getQualifier());
        $this->assertSame('https://flus.fr', $conditions[0]->getValue());
    }

    public function testParseTag(): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser();
        $stringQuery = '#tag';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertTrue($conditions[0]->isTagCondition());
        $this->assertSame('tag', $conditions[0]->getValue());
        $this->assertFalse($conditions[0]->not());
    }

    public function testParseNegativeTag(): void
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser();
        $stringQuery = '-#tag';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertTrue($conditions[0]->isTagCondition());
        $this->assertSame('tag', $conditions[0]->getValue());
        $this->assertTrue($conditions[0]->not());
    }
}

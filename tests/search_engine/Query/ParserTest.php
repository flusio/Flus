<?php

namespace App\search_engine\Query;

use App\search_engine\SyntaxError;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    public const QUALIFIERS = [
        'url' => '@text',
        'is' => ['hidden'],
        'date' => '/^\d{4}(-(0[1-9]|1[0-2]))?$/',
    ];

    public function testParseText(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'some text "and more text"';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(3, count($conditions));

        $this->assertTrue($conditions[0]->isTextCondition());
        $this->assertSame('and', $conditions[0]->getOperator());
        $this->assertSame('some', $conditions[0]->getValue());
        $this->assertFalse($conditions[0]->not());

        $this->assertTrue($conditions[1]->isTextCondition());
        $this->assertSame('and', $conditions[1]->getOperator());
        $this->assertSame('text', $conditions[1]->getValue());
        $this->assertFalse($conditions[1]->not());

        $this->assertTrue($conditions[2]->isTextCondition());
        $this->assertSame('and', $conditions[2]->getOperator());
        $this->assertSame('and more text', $conditions[2]->getValue());
        $this->assertFalse($conditions[2]->not());
    }

    public function testParsePhrase(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'some "text and more"';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(2, count($conditions));

        $this->assertSame('some', $conditions[0]->getValue());
        $this->assertFalse($conditions[0]->isPhrase());

        $this->assertSame('text and more', $conditions[1]->getValue());
        $this->assertTrue($conditions[1]->isPhrase());
    }

    public function testParseSubQuery(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'some NOT (text OR (#tag more))';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(2, count($conditions));

        $this->assertTrue($conditions[0]->isTextCondition());
        $this->assertSame('some', $conditions[0]->getValue());

        $this->assertTrue($conditions[1]->isQueryCondition());
        $this->assertSame('and', $conditions[1]->getOperator());
        $this->assertTrue($conditions[1]->not());

        $sub_conditions = $conditions[1]->getQuery()->getConditions();
        $this->assertSame(2, count($sub_conditions));

        $this->assertTrue($sub_conditions[0]->isTextCondition());
        $this->assertSame('and', $sub_conditions[0]->getOperator());
        $this->assertSame('text', $sub_conditions[0]->getValue());

        $this->assertTrue($sub_conditions[1]->isQueryCondition());
        $this->assertSame('or', $sub_conditions[1]->getOperator());
        $this->assertFalse($sub_conditions[1]->not());

        $sub_sub_conditions = $sub_conditions[1]->getQuery()->getConditions();
        $this->assertSame(2, count($sub_sub_conditions));

        $this->assertTrue($sub_sub_conditions[0]->isTagCondition());
        $this->assertSame('tag', $sub_sub_conditions[0]->getValue());

        $this->assertTrue($sub_sub_conditions[1]->isTextCondition());
        $this->assertSame('more', $sub_sub_conditions[1]->getValue());
    }

    public function testParseOr(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'some OR text';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(2, count($conditions));

        $this->assertSame('and', $conditions[0]->getOperator());
        $this->assertSame('some', $conditions[0]->getValue());

        $this->assertSame('or', $conditions[1]->getOperator());
        $this->assertSame('text', $conditions[1]->getValue());
    }

    public function testParseNot(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'some NOT text';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(2, count($conditions));

        $this->assertSame('some', $conditions[0]->getValue());
        $this->assertFalse($conditions[0]->not());

        $this->assertSame('and', $conditions[1]->getOperator());
        $this->assertSame('text', $conditions[1]->getValue());
        $this->assertTrue($conditions[1]->not());
    }

    public function testParseQualifierUrl(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'url: https://flus.fr';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertTrue($conditions[0]->isQualifierCondition());
        $this->assertSame('url', $conditions[0]->getQualifier());
        $this->assertSame('https://flus.fr', $conditions[0]->getValue());
        $this->assertFalse($conditions[0]->not());
    }

    public function testParseNegativeQualifier(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = '-url:flus.fr';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertTrue($conditions[0]->isQualifierCondition());
        $this->assertSame('url', $conditions[0]->getQualifier());
        $this->assertSame('flus.fr', $conditions[0]->getValue());
        $this->assertTrue($conditions[0]->not());
    }

    public function testParseTag(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
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
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = '-#tag';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertTrue($conditions[0]->isTagCondition());
        $this->assertSame('tag', $conditions[0]->getValue());
        $this->assertTrue($conditions[0]->not());
    }

    public function testParseIgnoresQualifierWithoutValue(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'some OR NOT url: #tag url:';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(2, count($conditions));

        $this->assertTrue($conditions[0]->isTextCondition());
        $this->assertSame('some', $conditions[0]->getValue());

        // The operator and the negation applied to the ignored qualifier, so
        // the tag is combined with an implicit AND.
        $this->assertTrue($conditions[1]->isTagCondition());
        $this->assertSame('tag', $conditions[1]->getValue());
        $this->assertSame('and', $conditions[1]->getOperator());
        $this->assertFalse($conditions[1]->not());
    }

    public function testParseIgnoresRepeatedOperators(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'AND some AND OR text NOT NOT NOT more';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(3, count($conditions));

        $this->assertSame('some', $conditions[0]->getValue());
        $this->assertSame('and', $conditions[0]->getOperator());

        $this->assertSame('text', $conditions[1]->getValue());
        $this->assertSame('or', $conditions[1]->getOperator());

        $this->assertSame('more', $conditions[2]->getValue());
        $this->assertSame('and', $conditions[2]->getOperator());
        $this->assertTrue($conditions[2]->not());
    }

    public function testParseCancelsDoubleNegations(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'NOT NOT some';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertSame('some', $conditions[0]->getValue());
        $this->assertFalse($conditions[0]->not());
    }

    public function testParseIgnoresOperatorAtTheEnd(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'some OR NOT';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertSame('some', $conditions[0]->getValue());
    }

    public function testParseIgnoresEmptySubQueries(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'some () (OR)';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $conditions = $query->getConditions();
        $this->assertSame(1, count($conditions));

        $this->assertSame('some', $conditions[0]->getValue());
    }

    public function testParseReturnsAnEmptyQueryIfQueryIsEmpty(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = '""';
        $tokens = $tokenizer->tokenize($stringQuery);

        $query = $parser->parse($tokens);

        $this->assertSame([], $query->getConditions());
    }

    public function testParseFailsIfQualifierValueIsInvalid(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'foo is:bar';
        $tokens = $tokenizer->tokenize($stringQuery);

        try {
            $parser->parse($tokens);

            $this->fail('A SyntaxError should have been raised.');
        } catch (SyntaxError $e) {
            $this->assertSame(SyntaxError::QUALIFIER_VALUE_INVALID, $e->getCode());
            $this->assertSame(8, $e->getPosition());
            $this->assertSame('is:bar', $e->getValue());
        }
    }

    public function testParseFailsIfDateValueIsInvalid(): void
    {
        $tokenizer = new Tokenizer(array_keys(self::QUALIFIERS));
        $parser = new Parser(self::QUALIFIERS);
        $stringQuery = 'date:2026-13';
        $tokens = $tokenizer->tokenize($stringQuery);

        try {
            $parser->parse($tokens);

            $this->fail('A SyntaxError should have been raised.');
        } catch (SyntaxError $e) {
            $this->assertSame(SyntaxError::QUALIFIER_VALUE_INVALID, $e->getCode());
            $this->assertSame(6, $e->getPosition());
            $this->assertSame('date:2026-13', $e->getValue());
        }
    }
}

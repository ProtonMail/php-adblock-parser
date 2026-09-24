<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use ProtonLabs\AdblockParser\Rule;
use ProtonLabs\AdblockParser\RuleAggregate;
use ProtonLabs\AdblockParser\RuleCollection;

class RuleAggregateTest extends TestCase
{
    public function testAddCollectionsKeepsRulesAlreadyKnownForTheSameDomain(): void
    {
        $ruleAggregate = new RuleAggregate([
            'example.com' => $this->collectionOf(new Rule('first', false, 'example.com')),
        ]);

        $ruleAggregate->addCollections([
            'example.com' => $this->collectionOf(new Rule('second', false, 'example.com')),
        ]);

        Assert::assertSame(
            ['first', 'second'],
            array_map(
                static fn (Rule $rule): ?string => $rule->getRegex(),
                $ruleAggregate->getRuleCollections()['example.com']->getBlockers(),
            ),
        );
    }

    public function testAddCollectionsKeepsExceptionsAndBlockersOfTheSameDomain(): void
    {
        $ruleAggregate = new RuleAggregate([
            'example.com' => $this->collectionOf(new Rule('blocker', false, 'example.com')),
        ]);

        $ruleAggregate->addCollections([
            'example.com' => $this->collectionOf(new Rule('exception', true, 'example.com')),
        ]);

        $collection = $ruleAggregate->getRuleCollections()['example.com'];
        Assert::assertCount(1, $collection->getBlockers());
        Assert::assertCount(1, $collection->getExceptions());
    }

    public function testAddCollectionsAddsUnknownDomains(): void
    {
        $ruleAggregate = new RuleAggregate([
            'example.com' => $this->collectionOf(new Rule('first', false, 'example.com')),
        ]);

        $ruleAggregate->addCollections([
            'other.com' => $this->collectionOf(new Rule('second', false, 'other.com')),
        ]);

        Assert::assertSame(['example.com', 'other.com'], array_keys($ruleAggregate->getRuleCollections()));
    }

    private function collectionOf(Rule $rule): RuleCollection
    {
        $collection = new RuleCollection();
        $collection->addRule($rule);

        return $collection;
    }
}

<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use ProtonLabs\AdblockParser\DomainParserInterface;
use ProtonLabs\AdblockParser\RuleAggregate;
use ProtonLabs\AdblockParser\RuleAggregateFactory;
use ProtonLabs\AdblockParser\RuleApplier;
use ProtonLabs\AdblockParser\Rule;
use ProtonLabs\AdblockParser\RuleFactory;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class RuleAggregateFactoryTest extends TestCase
{
    public function testCreateRuleAggregate(): void
    {
        $ruleAggregateFactory = $this->createRuleAggregateFactory();

        $ruleAggregate = $ruleAggregateFactory->createAdblockParserFromFiles([__DIR__ . '/test-rules.txt']);

        $this->assertCount(1, $ruleAggregate->getRuleCollections());
        $this->assertCount(
            2,
            $ruleAggregate->getRuleCollections()[Rule::DOMAIN_AGNOSTIC_IDENTIFIER]->getBlockers(),
        );
        Assert::assertCount(
            1,
            $ruleAggregate->getRuleCollections()[Rule::DOMAIN_AGNOSTIC_IDENTIFIER]->getExceptions(),
        );

        Assert::assertTrue($this->createRuleApplier()
            ->shouldBlock('http://example.com/avantlink/123', $ruleAggregate));
        Assert::assertTrue($this->createRuleApplier()
            ->shouldBlock('http://example.com//avmws_asd.js', $ruleAggregate));
        Assert::assertFalse($this->createRuleApplier()
            ->shouldBlock('http://example.com//avmws_exception.js', $ruleAggregate));
    }

    public function createRuleApplier(): RuleApplier
    {
        $domainParser = $this->createMock(DomainParserInterface::class);

        return new RuleApplier($domainParser);
    }

    private function createRuleFactory(): RuleFactory
    {
        $domainParser = $this->createMock(DomainParserInterface::class);

        return new RuleFactory($domainParser);
    }

    private function createRuleAggregateFactory(): RuleAggregateFactory
    {
        return new RuleAggregateFactory($this->createRuleFactory());
    }
}
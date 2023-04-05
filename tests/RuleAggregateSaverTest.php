<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use ProtonLabs\AdblockParser\Rule;
use ProtonLabs\AdblockParser\RuleAggregate;
use ProtonLabs\AdblockParser\RuleAggregateSaver;
use ProtonLabs\AdblockParser\RuleCollection;

class RuleAggregateSaverTest extends TestCase
{
    public function testSaveAndLoadParser(): void
    {
        $ruleAggregateSaver = new RuleAggregateSaver();
        $ruleDomain = 'domain.com';
        $ruleDomainTwo = 'anotherDomain.fr';
        $savedFilePath = './dummyRuleAggregate.php';

        $ruleAggregate = new RuleAggregate([
            $ruleDomain => new RuleCollection([new Rule(
                regex: 'test',
                isException: false,
                registrableDomain: $ruleDomain,
            )]),
            $ruleDomainTwo => new RuleCollection([
                new Rule(
                    regex: 'testTwo',
                    isException: false,
                    registrableDomain: $ruleDomain,
                ),
                new Rule(
                    regex: 'testThree',
                    isException: true,
                    registrableDomain: $ruleDomain,
                ),
            ]),
        ]);

        $ruleAggregateSaver->save($ruleAggregate, $savedFilePath);

        $loadedRuleAggregate = $ruleAggregateSaver->load($savedFilePath);

        Assert::assertSame($ruleAggregate->toArray(), $loadedRuleAggregate->toArray());

        Assert::assertSame([$ruleDomain, $ruleDomainTwo], array_keys($loadedRuleAggregate->getRuleCollections()));
        Assert::assertSame(
            'testTwo',
            $loadedRuleAggregate->getRuleCollections()[$ruleDomainTwo]->getAllRules()[0]->getRegex(),
        );
        Assert::assertFalse($loadedRuleAggregate->getRuleCollections()[$ruleDomainTwo]->getAllRules()[0]->isException());
        Assert::assertSame(
            'testThree',
            $loadedRuleAggregate->getRuleCollections()[$ruleDomainTwo]->getAllRules()[1]->getRegex(),
        );
        Assert::assertTrue($loadedRuleAggregate->getRuleCollections()[$ruleDomainTwo]->getAllRules()[1]->isException());

        unlink($savedFilePath);
    }
}

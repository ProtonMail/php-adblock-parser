<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser\Tests;

use PHPUnit\Framework\TestCase;
use ProtonLabs\AdblockParser\DomainParserInterface;
use ProtonLabs\AdblockParser\InvalidRuleException;
use ProtonLabs\AdblockParser\Rule;
use ProtonLabs\AdblockParser\RuleFactory;

class RuleFactoryTest extends TestCase
{
    public function testGetRegex()
    {
        $rule = $this->createRuleFactory()->createFromAdblockEntry('/slashes should be trimmed/');
        $this->assertEquals('slashes should be trimmed', $rule->getRegex());
    }

    public function testInvalidRegex()
    {
        $this->expectException(InvalidRuleException::class);
        $this->createRuleFactory()->createFromAdblockEntry('//');
    }

    public function testEscapeSpecialCharacters()
    {
        $rule = $this->createRuleFactory()->createFromAdblockEntry('.$+?{}()[]/\\');
        $this->assertEquals('\.\$\+\?\{\}\(\)\[\]\/\\\\', $rule->getRegex());
    }

    public function testCaret()
    {
        $rule = $this->createRuleFactory()->createFromAdblockEntry('domain^');
        $this->assertEquals('domain([^\w\d_\-.%]|$)', $rule->getRegex());
    }

    public function testAsterisk()
    {
        $rule = $this->createRuleFactory()->createFromAdblockEntry('domain*');
        $this->assertEquals('domain.*', $rule->getRegex());
    }

    public function testVerticalBars()
    {
        $rule = $this->createRuleFactory()->createFromAdblockEntry('||domain');
        $this->assertEquals('^([^:\/?#]+:)?(\/\/([^\/?#]*\.)?)?domain', $rule->getRegex());

        $rule = $this->createRuleFactory()->createFromAdblockEntry('|domain');
        $this->assertEquals('^domain', $rule->getRegex());

        $rule = $this->createRuleFactory()->createFromAdblockEntry('domain|bl||ah');
        $this->assertEquals('domain\|bl\|\|ah', $rule->getRegex());
    }

    public function testComment()
    {
        $rule = $this->createRuleFactory()->createFromAdblockEntry('!this is comment');
        $this->assertNull($rule);
        $rule = $this->createRuleFactory()->createFromAdblockEntry('[Adblock Plus 1.1]');
        $this->assertNull($rule);
        $rule = $this->createRuleFactory()->createFromAdblockEntry('non-comment rule');
        $this->assertInstanceOf(Rule::class, $rule);
    }

    public function testRegistrableDomain(): void
    {
        $hostsFromAdblockEntries = [
            '||domain.com' => 'domain.com',
            '||domain.com/aPath' => 'domain.com',
            '||subdomain.domain.com^*/aPath' => 'subdomain.domain.com',
            '@@||subdomain.domain.com^*\aPath' => 'subdomain.domain.com',
        ];
        foreach ($hostsFromAdblockEntries as $adblockEntry => $host) {
            $rule = $this->createRuleFactoryWithExpectedCall($host)
                ->createFromAdblockEntry($adblockEntry);
            $this->assertSame($host, $rule->getRegistrableDomain());
        }
    }

    public function testRegistrableDomainAgnostic(): void
    {
        $domainParser = $this->createMock(DomainParserInterface::class);
        $domainParser->expects($this->never())->method('parseRegistrableDomain');
        $ruleFactory = new RuleFactory($domainParser);

        $rule = $ruleFactory->createFromAdblockEntry(adblockEntry: '/banThisPath.');
        $this->assertSame(Rule::DOMAIN_AGNOSTIC_IDENTIFIER, $rule->getRegistrableDomain());
    }

    private function createRuleFactory(): RuleFactory
    {
        $domainParser = $this->createMock(DomainParserInterface::class);

        return new RuleFactory($domainParser);
    }

    private function createRuleFactoryWithExpectedCall(string $expectedHost): RuleFactory
    {
        $domainParser = $this->createMock(DomainParserInterface::class);
        $domainParser->expects($this->once())
            ->method('parseRegistrableDomain')
            ->with($expectedHost)
            ->willReturn($expectedHost);

        return new RuleFactory($domainParser);
    }
}

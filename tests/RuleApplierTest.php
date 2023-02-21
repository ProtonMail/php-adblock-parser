<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser\Tests;

use PHPUnit\Framework\TestCase;
use ProtonLabs\AdblockParser\DomainParserInterface;
use ProtonLabs\AdblockParser\NotAnUrlException;
use ProtonLabs\AdblockParser\RuleAggregate;
use ProtonLabs\AdblockParser\RuleAggregateFactory;
use ProtonLabs\AdblockParser\RuleApplier;
use ProtonLabs\AdblockParser\RuleFactory;

class RuleApplierTest extends TestCase
{
    public function testMatchUrl()
    {
        $rule = $this->createRuleFactory()->createFromAdblockEntry('swf|');
        $this->assertTrue($this->createRuleApplier()->matchUrl("http://example.com/annoyingflash.swf", $rule));
        $this->assertFalse($this->createRuleApplier()->matchUrl("http://example.com/swf/index.html", $rule));
    }

    public function testInvalidUrl(): void
    {
        $this->expectException(NotAnUrlException::class);
        $this->shouldBlock(['sfsaf'], new RuleAggregate());
    }

    public function testBlockByAddressParts(): void
    {
        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['/banner/*/img^']);
        $this->shouldBlock(
            [
                'http://example.com/banner/foo/img',
                'http://example.com/banner/foo/bar/img?param',
                'http://example.com/banner//img/foo',
            ],
            $ruleAggregate,
        );
        $this->shouldNotBlock(
            [
            'http://example.com/banner/img',
            'http://example.com/banner/foo/imgraph',
            'http://example.com/banner/foo/img.gif',
            ],
            $ruleAggregate,
        );
    }

    public function testBlockByDomainName(): void
    {
        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['||ads.example.com^']);
        $this->shouldBlock(
            [
                'http://ads.example.com/foo.gif',
                'http://server1.ads.example.com/foo.gif',
                'https://ads.example.com:8000/',
            ],
            $ruleAggregate,
        );
        $this->shouldNotBlock(
            [
                'http://ads.example.com.ua/foo.gif',
                'http://example.com/redirect/http://ads.example.com/',
            ],
            $ruleAggregate,
        );

        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['|http://baddomain.example/']);
        $this->shouldBlock(
            [
                'http://baddomain.example/banner.gif',
            ],
            $ruleAggregate,
        );
        $this->shouldNotBlock(
            [
                'http://gooddomain.example/analyze?http://baddomain.example',
            ],
            $ruleAggregate,
        );
    }

    public function testBlockExactAddress(): void
    {
        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['|http://example.com/|']);
        $this->shouldBlock(
            [
                'http://example.com/',
            ],
            $ruleAggregate,
        );
        $this->shouldNotBlock(
            [
                'http://example.com/foo.gif',
                'http://example.info/redirect/http://example.com/',
            ],
            $ruleAggregate,
        );
    }

    public function testBlockBeginningDomain(): void
    {
        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['||example.com/banner.gif']);
        $this->shouldBlock(
            [
                'http://example.com/banner.gif',
                'https://example.com/banner.gif',
                'http://www.example.com/banner.gif',
            ],
            $ruleAggregate,
        );
        $this->shouldNotBlock(
            [
                'http://badexample.com/banner.gif',
                'http://gooddomain.example/analyze?http://example.com/banner.gif',
            ],
            $ruleAggregate,
        );
    }

    public function testCaretSeparator(): void
    {
        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['http://example.com^']);
        $this->shouldBlock(
            [
                'http://example.com/',
                'http://example.com:8000/ ',
            ],
            $ruleAggregate,
        );
        $this->shouldNotBlock(
            [
                'http://example.com.ar/',
            ],
            $ruleAggregate,
        );

        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['^example.com^']);
        $this->shouldBlock(
            [
                'http://example.com:8000/foo.bar?a=12&b=%D1%82%D0%B5%D1%81%D1%82',
            ],
            $ruleAggregate,
        );

        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['^%D1%82%D0%B5%D1%81%D1%82^']);
        $this->shouldBlock(
            [
                'http://example.com:8000/foo.bar?a=12&b=%D1%82%D0%B5%D1%81%D1%82',
            ],
            $ruleAggregate,
        );

        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['^foo.bar^']);
        $this->shouldBlock(
            [
                'http://example.com:8000/foo.bar?a=12&b=%D1%82%D0%B5%D1%81%D1%82',
            ],
            $ruleAggregate,
        );
    }

    public function testParserException(): void
    {
        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['adv', '@@advice.']);
        $this->shouldBlock(
            [
                'http://example.com/advert.html',
            ],
            $ruleAggregate,
        );
        $this->shouldNotBlock(
            [
                'http://example.com/advice.html',
            ],
            $ruleAggregate,
        );

        $ruleAggregate = $this->createRuleAggregateFactory()->createFromAdblockEntries(['@@|http://example.com', '@@advice.', 'adv', '!foo']);
        $this->shouldBlock(
            [
                'http://examples.com/advert.html',
            ],
            $ruleAggregate,
        );
        $this->shouldNotBlock(
            [
                'http://example.com/advice.html',
                'http://example.com/advert.html',
                'http://examples.com/advice.html',
                'http://examples.com/#!foo',
            ],
            $ruleAggregate,
        );
    }

    /**
     * @param array<string> $url
     */
    private function shouldBlock(array $urls, RuleAggregate $ruleAggregate): void
    {
        foreach ($urls as $url) {
            $this->assertTrue($this->createRuleApplier()->shouldBlock($url, $ruleAggregate), $url);
        }
    }

    /**
     * @param array<string> $urls
     */
    private function shouldNotBlock(array $urls, RuleAggregate $ruleAggregate): void
    {
        foreach ($urls as $url) {
            $this->assertFalse($this->createRuleApplier()->shouldBlock($url, $ruleAggregate), $url);
        }
    }

    private function createRuleFactory(): RuleFactory
    {
        $domainParser = $this->createMock(DomainParserInterface::class);

        return new RuleFactory($domainParser);
    }

    private function createRuleApplier(): RuleApplier
    {
        $domainParser = self::createMock(DomainParserInterface::class);

        return new RuleApplier($domainParser);
    }

    private function createRuleAggregateFactory(): RuleAggregateFactory
    {
        return new RuleAggregateFactory($this->createRuleFactory());
    }

}

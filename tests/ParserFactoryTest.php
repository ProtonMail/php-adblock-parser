<?php

namespace ProtonLabs\AdblockParser\Tests;

use ProtonLabs\AdblockParser\Parser;
use ProtonLabs\AdblockParser\ParserFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class ParserFactoryTest extends TestCase
{
    public function testCreateAdblockParser(): void
    {
        $mockCacheItemPoolInterface = self::getMockBuilder(CacheItemPoolInterface::class)->getMock();

        $adblockParserFactory = new ParserFactory($mockCacheItemPoolInterface);

        $adblockParser = $adblockParserFactory->createAdblockParserFromFiles([__DIR__ . '/test-rules.txt']);

        $this->assertCount(1, $adblockParser->getRuleCollections());
        $this->assertCount(
            2,
            $adblockParser->getRuleCollections()[Parser::DOMAIN_AGNOSTIC_IDENTIFIER]->getBlockers(),
        );

        Assert::assertTrue($adblockParser->shouldBlock('http://example.com/avantlink/123'));
        Assert::assertTrue($adblockParser->shouldBlock('http://example.com//avmws_asd.js'));
        Assert::assertFalse($adblockParser->shouldBlock('http://example.com//avmws_exception.js'));
    }

    public function testSaveAdblockParser(): void
    {
        $dummyParser = new Parser(['test']);

        $mockCacheItem = self::getMockBuilder(CacheItemInterface::class)->getMock();
        $mockCacheItem->expects($this->once())->method('set')->willReturnCallback(
            static function (Parser $parser) use ($mockCacheItem) {
                if ($parser->getAllRules()[0]->getRegex() !== 'test') {
                    throw new \Exception();
                }

                return $mockCacheItem;
            }
        );

        $mockCacheItemPoolInterface = self::getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $mockCacheItemPoolInterface->method('getItem')->willReturn($mockCacheItem);
        $mockCacheItemPoolInterface->expects($this->once())->method('save')->willReturn(true);

        $adblockParserFactory = new ParserFactory($mockCacheItemPoolInterface);
        $success = $adblockParserFactory->saveAdblockParser($dummyParser);
        Assert::assertTrue($success);
    }

    public function testLoadAdblockParser(): void
    {
        $dummyParser = new Parser(['test']);
        $mockCacheItem = self::getMockBuilder(CacheItemInterface::class)->getMock();
        $mockCacheItem->method('get')->willReturn($dummyParser);
        $mockCacheItem->method('isHit')->willReturn(true);

        $mockCacheItemPoolInterface = self::getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $mockCacheItemPoolInterface->method('getItem')->willReturn($mockCacheItem);

        $adblockParserFactory = new ParserFactory($mockCacheItemPoolInterface);
        $loadedAdblockParser = $adblockParserFactory->loadAdblockParser();
        Assert::assertSame('test', $loadedAdblockParser->getAllRules()[0]->getRegex());
    }

    public function testLoadUnsavedAdblockParser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockCacheItem = self::getMockBuilder(CacheItemInterface::class)->getMock();
        $mockCacheItem->method('isHit')->willReturn(false);

        $mockCacheItemPoolInterface = self::getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $mockCacheItemPoolInterface->method('getItem')->willReturn($mockCacheItem);

        $adblockParserFactory = new ParserFactory($mockCacheItemPoolInterface);
        $adblockParserFactory->loadAdblockParser();
    }

    public function testCreateAdblockParserFromFiles()
    {
        $mockCacheItemPoolInterface = self::getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $adblockParserFactory = new ParserFactory($mockCacheItemPoolInterface);
        assert($adblockParserFactory instanceof ParserFactory);
        $adblockParser = $adblockParserFactory->createAdblockParserFromFiles([__DIR__ . '/test-rules.txt']);

        Assert::assertCount(1, $adblockParser->getRuleCollections());
        Assert::assertCount(
            2,
            $adblockParser->getRuleCollections()[Parser::DOMAIN_AGNOSTIC_IDENTIFIER]->getBlockers(),
        );
        Assert::assertCount(
            1,
            $adblockParser->getRuleCollections()[Parser::DOMAIN_AGNOSTIC_IDENTIFIER]->getExceptions(),
        );
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
}
<?php

declare(strict_types=1);

namespace App\AdblockParser;

use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;

class ParserFactory
{
    private const ADBLOCK_PARSER_CACHE_KEY = 'AdblockParser';

    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
    )
    {
    }

    public function clearCachedAdblockParser(): void
    {
        $this->cacheItemPool->deleteItem(self::ADBLOCK_PARSER_CACHE_KEY);
    }

    /**
     * @return bool whether the item was succesfully saved
     */
    public function saveAdblockParser(Parser $adblockParser): bool
    {
        $cacheItemInterface = $this->cacheItemPool->getItem(
            self::ADBLOCK_PARSER_CACHE_KEY,
        );
        $cacheItemInterface->set($adblockParser);

        return $this->cacheItemPool->save($cacheItemInterface);
    }

    public function loadAdblockParser(): Parser
    {
        $cacheItemInterface = $this->cacheItemPool->getItem(self::ADBLOCK_PARSER_CACHE_KEY);

        if (!$cacheItemInterface->isHit()) {
            throw new InvalidArgumentException('Trying to load adblockParser before saving it');
        }

        $adblockParser = $cacheItemInterface->get();

        if (!($adblockParser instanceof Parser)) {
            throw new \LogicException(
                message: 'Key ' . self::ADBLOCK_PARSER_CACHE_KEY
                    . ' must contain an object of type AdblockParser/Parser',
            );
        }

        return $adblockParser;
    }

    /**
     * @param array<string> $paths
     * @throws NotAPathException
     */
    public function createAdblockParserFromFiles(array $paths): Parser
    {
        $adblockParser = new Parser();
        foreach ($paths as $path) {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new NotAPathException(
                    "The following string is not a valid path to a file $path"
                );
            }
            $lines = preg_split("/(\r\n|\n|\r)/", $content);
            $adblockParser->addRules($lines);
        }

        return $adblockParser;
    }
}

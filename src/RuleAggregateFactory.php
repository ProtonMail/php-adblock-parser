<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;

class RuleAggregateFactory
{
    public function __construct(
        private readonly RuleFactory $ruleFactory,
    ) {
    }

    /**
     * @param array<string> $paths
     * @throws NotAPathException
     */
    public function createFromFiles(array $paths): RuleAggregate
    {
        $ruleAggregate = new RuleAggregate();
        foreach ($paths as $path) {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new NotAPathException(
                    "The following string is not a valid path to a file $path"
                );
            }
            $lines = preg_split("/(\r\n|\n|\r)/", $content);
            $collections = $this->createRuleCollections($lines);
            $ruleAggregate->addCollections($collections);
        }

        return $ruleAggregate;
    }

    /** @param array<string> $adblockEntries */
    public function createFromAdblockEntries(
        array $adblockEntries = [],
    ): RuleAggregate {
        $collections = $this->createRuleCollections($adblockEntries);

        return new RuleAggregate($collections);
    }

    /**
     * @param array<string> $adblockEntries
     * @return array<string, RuleCollection>
     */
    public function createRuleCollections(array $adblockEntries): array
    {
        $collections = [];
        foreach ($adblockEntries as $adblockEntry) {
            try {
                $adblockRule = $this->ruleFactory->createFromAdblockEntry($adblockEntry);
                if (is_null($adblockRule)) {
                    continue;
                }
                $domainIdentifier = $adblockRule->getRegistrableDomain();
                if (!isset($collections[$domainIdentifier])) {
                    $collections[$domainIdentifier] = new RuleCollection();
                }
                $collections[$domainIdentifier]->addRule(rule: $adblockRule);
            } catch (InvalidRuleException) {
                // Skip invalid rules
            }
        }

        return $collections;
    }
}

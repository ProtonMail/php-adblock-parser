<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

class RuleAggregateSaver
{
    public function saveAdblockParser(RuleAggregate $adblockParser, string $savedFilePath): bool
    {
        $export = var_export($adblockParser->toArray(), return: true);
        $selfFqcn = self::class;
        $out = <<<OUT
            <?php
            // This file has been auto-generated by $selfFqcn

            return $export;

            OUT;

        $success = file_put_contents($savedFilePath, $out);

        return (bool) $success;
    }

    public function loadAdblockParser(string $savedFilePath): ?RuleAggregate
    {
        if (!file_exists($savedFilePath)) {
            return null;
        }

        $export = include $savedFilePath;

        $adblockParser = RuleAggregate::fromArray($export);

        assert($adblockParser instanceof RuleAggregate);

        return $adblockParser;
    }
}

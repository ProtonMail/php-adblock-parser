<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

use Pdp\Rules;

class DomainParser implements DomainParserInterface
{
    private static ?Rules $publicSuffixRules = null;

    public static function parseRegistrableDomain(string $host): string
    {
        $publicSuffixRules = self::getPublicSuffixRules();

        $result = $publicSuffixRules->resolve($host);

        return $result->registrableDomain()->toString();
    }

    private static function getPublicSuffixRules(): Rules
    {
        if (!is_null(self::$publicSuffixRules)) {
            return self::$publicSuffixRules;
        }

        $publicSuffixRulesPath = realpath(__DIR__ . '/../resources/publicSuffixRules');
        if ($publicSuffixRulesPath && file_exists($publicSuffixRulesPath)) {
            $serializedRules = file_get_contents($publicSuffixRulesPath);
            $publicSuffixRules = unserialize($serializedRules);
            assert($publicSuffixRules instanceof Rules);
        } else {
            $publicSuffixRules = Rules::fromPath(realpath(__DIR__ . '/../resources/public_suffix_list.dat'));
            $serializedRules = serialize($publicSuffixRules);
            file_put_contents('publicSuffixRules', $serializedRules);
        }

        self::$publicSuffixRules = $publicSuffixRules;

        return $publicSuffixRules;
    }
}

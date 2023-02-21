<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

use Pdp\PublicSuffixList;
use Pdp\Rules;
use Pdp\Storage\PublicSuffixListStorage;

class DomainParser implements DomainParserInterface
{
    private const PUBLIC_SUFFIX_LIST_URI = 'https://publicsuffix.org/list/public_suffix_list.dat';

    private static ?PublicSuffixList $publicSuffixRules = null;

    public function __construct(
        private readonly PublicSuffixListStorage $publicSuffixListStorage,
    ) {

    }

    public function parseRegistrableDomain(string $host): string
    {
        $publicSuffixRules = $this->getPublicSuffixRules();

        $result = $publicSuffixRules->getCookieDomain($host);

        return $result->registrableDomain()->toString();
    }

    private function getPublicSuffixRules(): Rules
    {
        if (!is_null(self::$publicSuffixRules)) {
            return self::$publicSuffixRules;
        }

        return $this->publicSuffixListStorage->get(self::PUBLIC_SUFFIX_LIST_URI);
    }
}

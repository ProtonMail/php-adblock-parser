<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

use Pdp\SyntaxError;
use Pdp\UnableToLoadPublicSuffixList;

interface DomainParserInterface
{
    /**
     * @throws UnableToLoadPublicSuffixList
     * @throws SyntaxError
     */
    public function parseRegistrableDomain(string $host): string;
}

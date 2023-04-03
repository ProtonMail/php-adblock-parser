<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

use Pdp\UnableToLoadPublicSuffixList;
use Pdp\SyntaxError;

interface DomainParserInterface
{
    /**
     * @throws UnableToLoadPublicSuffixList
     * @throws SyntaxError
     */
    public function parseRegistrableDomain(string $host): string;
}
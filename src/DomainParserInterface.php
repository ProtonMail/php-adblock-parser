<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

interface DomainParserInterface
{
    public function parseRegistrableDomain(string $host): string;
}
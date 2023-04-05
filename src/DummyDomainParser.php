<?php

namespace ProtonLabs\AdblockParser;

class DummyDomainParser implements DomainParserInterface
{
    public function parseRegistrableDomain(string $host): string
    {
        return $host;
    }
}

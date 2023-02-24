<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

class RuleApplier
{
    public function __construct(
        private readonly DomainParserInterface $domainParser = new DummyDomainParser(),
    ) {
    }

    public function shouldBlock(string $url, RuleAggregate $ruleAggregate): bool
    {
        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new NotAnUrlException('Invalid URL');
        }

        $host = parse_url($url)['host'];
        if (!is_string($host)) {
            throw new NotAnUrlException('Invalid URL');
        }
        $registrableDomain = $this->domainParser->parseRegistrableDomain($host);

        foreach ($ruleAggregate->getRulesToApplyForDomain($registrableDomain) as $rule) {
            if ($this->matchUrl($url, $rule)) {
                return !$rule->isException();
            }
        }

        return false;
    }

    public function matchUrl(string $url, Rule $rule): bool
    {
        return (bool) preg_match(
            '/' . ($rule->getRegex() ?? '') . '/',
            $url,
        );
    }
}
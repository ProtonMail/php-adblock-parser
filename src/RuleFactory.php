<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

use Pdp\SyntaxError;

class RuleFactory
{
    public function __construct(
        private readonly DomainParserInterface $domainParser = new DummyDomainParser(),
    ) {
    }

    public function createFromAdblockEntry(string $adblockEntry): ?Rule
    {
        $isException = false;
        if (Str::startsWith($adblockEntry, '@@')) {
            $isException = true;
            $adblockEntry = mb_substr($adblockEntry, 2);
        }

        if (
            Str::startsWith($adblockEntry, '!') || Str::startsWith($adblockEntry, '[Adblock') // comment
            || Str::contains($adblockEntry, '##') || Str::contains($adblockEntry, '#@#') // HTML rule
        ) {
            return null;
        }

        $registrableDomain = Rule::DOMAIN_AGNOSTIC_IDENTIFIER;
        if (preg_match(
            pattern: '/\|\|([^\^\/\?\#]*)/',
            subject: $adblockEntry,
            matches: $matches,
        )) {
            try{
                $registrableDomain = $this->domainParser->parseRegistrableDomain(host: $matches[1]);
            } catch (SyntaxError) {
                // do nothing
            }
        }

        $regex = $this->transformAdblockRuleEntryToRegex($adblockEntry);

        return new Rule(
            regex: $regex,
            isException: $isException,
            registrableDomain: $registrableDomain,
        );
    }

    private function transformAdblockRuleEntryToRegex(string $adblockEntry): string
    {
        if (empty($adblockEntry)) {
            throw new InvalidRuleException('Empty rule');
        }

        // Check if the rule isn't already regexp
        if (Str::startsWith($adblockEntry, '/') && Str::endsWith($adblockEntry, '/')) {
            $regex = mb_substr($adblockEntry, 1, mb_strlen($adblockEntry) - 2);
            $regex = preg_replace('/\//', '\\\\/', $regex);

            if (empty($regex)) {
                throw new InvalidRuleException('Empty rule');
            }

            return $regex;
        }

        // escape special regex characters
        $regex = preg_replace('/([\\\.\$\+\?\{\}\(\)\[\]\/])/', '\\\\$1', $adblockEntry);

        // Separator character ^ matches anything but a letter, a digit, or
        // one of the following: _ - . %. The end of the address is also
        // accepted as separator.
        $regex = str_replace('^', '([^\w\d_\-.%]|$)', $regex);

        // * symbol
        $regex = str_replace('*', '.*', $regex);

        // | in the end means the end of the address
        if (Str::endsWith($regex, '|')) {
            $regex = mb_substr($regex, 0, mb_strlen($regex) - 1) . '$';
        }

        // || in the beginning means beginning of the domain name
        if (Str::startsWith($regex, '||')) {
            if (mb_strlen($regex) > 2) {
                // http://tools.ietf.org/html/rfc3986#appendix-B
                $regex = '^([^:\/?#]+:)?(\/\/([^\/?#]*\.)?)?' . mb_substr($regex, 2);
            }
            // | in the beginning means start of the address
        } elseif (Str::startsWith($regex, '|')) {
            $regex = '^' . mb_substr($regex, 1);
        }

        // other | symbols should be escaped
        $regex = preg_replace("/\|(?![\$])/", '\|$1', $regex);

        return $regex;
    }
}
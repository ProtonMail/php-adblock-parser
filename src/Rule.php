<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

class Rule
{
    public const DOMAIN_AGNOSTIC_IDENTIFIER = 'domain-agnostic';

    public function __construct(
        private readonly string $regex,
        private readonly bool $isException,
        private readonly string $registrableDomain = self::DOMAIN_AGNOSTIC_IDENTIFIER,
    ) {
    }

    public function toArray(): array
    {
        return [
            'regex' => $this->regex,
            'isException' => $this->isException,
            'registrableDomain'=> $this->registrableDomain,
        ];
    }

    public static function fromArray(array $array): self
    {
        return new self(
            regex: $array['regex'],
            isException: $array['isException'],
            registrableDomain: $array['registrableDomain'],
        );
    }

    public function getRegex(): ?string
    {
        return $this->regex;
    }

    public function isException(): bool
    {
        return $this->isException;
    }

    public function getRegistrableDomain(): ?string
    {
        return $this->registrableDomain;
    }
}

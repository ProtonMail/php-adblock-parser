<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

class RuleCollection
{
    /**
     * @param array<Rule> $exceptions // items that match this should be whitelisted from all other blocking rules
     * @param array<Rule> $blockers   // items that match this should be blocked
     */
    public function __construct(
        private array $exceptions = [],
        private array $blockers = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'exceptions' => array_map(
                static fn (Rule $exception) => $exception->toArray(),
                $this->exceptions,
            ),
            'blockers' => array_map(
                static fn (Rule $blocker) => $blocker->toArray(),
                $this->blockers,
            ),
        ];
    }

    public static function fromArray(array $array): self
    {
        return new self(
            exceptions: array_map(
                static fn (array $ruleArray) => Rule::fromArray($ruleArray),
                $array['exceptions'],
            ),
            blockers: array_map(
                static fn (array $ruleArray) => Rule::fromArray($ruleArray),
                $array['blockers'],
            ),
        );
    }

    public function addRule(Rule $rule): void
    {
        if ($rule->isException()) {
            $this->exceptions[] = $rule;
        } else {
            $this->blockers[] = $rule;
        }
    }

    /**
     * @return list<Rule>
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * @return list<Rule>
     */
    public function getBlockers(): array
    {
        return $this->blockers;
    }

    /**
     * @return list<Rule>
     */
    public function getAllRules(): array
    {
        return array_merge($this->getExceptions(), $this->getBlockers());
    }
}

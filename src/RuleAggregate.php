<?php

declare(strict_types=1);

namespace ProtonLabs\AdblockParser;

class RuleAggregate
{
    /** @param array<string,RuleCollection> $ruleCollections */
    public function __construct(
        private array $ruleCollections = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'ruleCollections' => array_map(
                static fn (mixed $ruleCollection) => $ruleCollection->toArray(),
                $this->getRuleCollections()
            ),
        ];
    }

    public static function fromArray(array $array): self
    {
        return new self(
            ruleCollections: array_map(
                static fn (array $ruleCollectionArray) => RuleCollection::fromArray($ruleCollectionArray),
                $array['ruleCollections'],
            ),
        );
    }

    public function getRuleCollections(): array
    {
        return $this->ruleCollections;
    }

    /**
     * @return Rule[]
     */
    public function getAllRules(): array
    {
        $allRules = [];
        foreach ($this->ruleCollections as $ruleCollection) {
            foreach ($ruleCollection->getAllRules() as $rule) {
                $allRules[] = $rule;
            }
        }

        return $allRules;
    }

    /**
     * Rules of an already known domain are added to its existing collection. Replacing the collection
     * instead would drop every rule a previous call contributed for that domain.
     *
     * @param array<string,RuleCollection> $collections
     */
    public function addCollections(array $collections): void
    {
        foreach ($collections as $domainIdentifier => $collection) {
            if (!isset($this->ruleCollections[$domainIdentifier])) {
                $this->ruleCollections[$domainIdentifier] = $collection;

                continue;
            }

            foreach ($collection->getAllRules() as $rule) {
                $this->ruleCollections[$domainIdentifier]->addRule($rule);
            }
        }
    }

    /**
     * @return list<Rule>
     */
    public function getRulesToApplyForDomain(?string $registrableDomain): array
    {
        return array_merge( // exceptions must go first
            ($this->ruleCollections[Rule::DOMAIN_AGNOSTIC_IDENTIFIER] ?? null)?->getExceptions() ?? [],
            ($this->ruleCollections[$registrableDomain] ?? null)?->getExceptions() ?? [],
            ($this->ruleCollections[Rule::DOMAIN_AGNOSTIC_IDENTIFIER] ?? null)?->getBlockers() ?? [],
            ($this->ruleCollections[$registrableDomain] ?? null)?->getBlockers() ?? [],
        );
    }
}

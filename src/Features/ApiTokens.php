<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

class ApiTokens extends AbstractFeature
{
    protected int $sort = 50;

    /** @var array<string, string> */
    protected array $abilities = [];

    public function getId(): string
    {
        return 'api-tokens';
    }

    /**
     * @param  array<string, string>  $abilities
     */
    public function abilities(array $abilities): static
    {
        $this->abilities = $abilities;

        return $this;
    }

    /** @return array<string, string> */
    public function getAbilities(): array
    {
        return $this->abilities;
    }
}

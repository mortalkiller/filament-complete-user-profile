<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Contracts;

use Closure;

interface ProfileFeature
{
    public function getId(): string;

    public function enabled(bool|Closure $condition = true): static;

    public function isEnabled(): bool;

    public function isVisible(): bool;

    public function getSort(): int;
}

<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Contracts;

interface ProfileFeature
{
    public function getId(): string;

    public function isEnabled(): bool;

    public function isVisible(): bool;

    public function getSort(): int;
}

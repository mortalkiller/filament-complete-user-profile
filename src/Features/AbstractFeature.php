<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileFeature;

abstract class AbstractFeature implements ProfileFeature
{
    use EvaluatesClosures;

    protected bool|Closure $enabled = true;

    protected bool|Closure $visible = true;

    protected int $sort = 0;

    public static function make(): static
    {
        return new static;
    }

    public function enabled(bool|Closure $condition = true): static
    {
        $this->enabled = $condition;

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function sort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->evaluate($this->enabled);
    }

    public function isVisible(): bool
    {
        return (bool) $this->evaluate($this->visible);
    }

    public function getSort(): int
    {
        return $this->sort;
    }
}

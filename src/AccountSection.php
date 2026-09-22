<?php

namespace Mortalkiller\FilamentCompleteUserProfile;

use Closure;
use Filament\Auth\Pages\EditProfile;
use Filament\Clusters\Cluster;
use Filament\Pages\Page;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Schemas\Components\Component;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithAccountSection;
use ReflectionClass;

final class AccountSection
{
    use EvaluatesClosures;

    protected string|Closure|null $label = null;

    protected string|Closure|null $description = null;

    protected int $sort = 100;

    protected bool|Closure $visible = true;

    /** @var array<array-key, mixed>|Closure */
    protected array|Closure $schema = [];

    protected bool $hasConfiguredSchema = false;

    /** @var class-string<Page>|null */
    protected ?string $page = null;

    protected function __construct(
        protected string $id,
    ) {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id) !== 1) {
            throw new InvalidArgumentException('Account section IDs must use lowercase kebab-case.');
        }
    }

    public static function make(string $id): static
    {
        return new self($id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function label(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function sort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    /** @param array<array-key, mixed>|Closure $components */
    public function schema(array|Closure $components): static
    {
        if ($this->page !== null) {
            throw new LogicException('An account section must use either a schema or a page, not both.');
        }

        $this->hasConfiguredSchema = true;
        $this->schema = $components;

        return $this;
    }

    /** @param class-string<Page> $page */
    public function page(string $page): static
    {
        if ($this->hasConfiguredSchema) {
            throw new LogicException('An account section must use either a schema or a page, not both.');
        }

        if (! is_subclass_of($page, Page::class)
            || ! (new ReflectionClass($page))->isInstantiable()
            || is_a($page, ResourcePage::class, true)
            || is_a($page, EditProfile::class, true)
            || is_a($page, Cluster::class, true)) {
            throw new LogicException("Account section page [{$page}] must be a concrete custom Filament panel page.");
        }

        if (! in_array(InteractsWithAccountSection::class, class_uses_recursive($page), true)) {
            throw new LogicException("Account section page [{$page}] must use InteractsWithAccountSection.");
        }

        if ($page::getCluster() !== null) {
            throw new LogicException("Account section page [{$page}] cannot belong to a cluster.");
        }

        $this->page = $page;

        return $this;
    }

    /** @return class-string<Page>|null */
    public function getPage(): ?string
    {
        return $this->page;
    }

    public function getLabel(): string
    {
        if ($this->label === null) {
            return Str::headline($this->id);
        }

        $label = $this->evaluate($this->label);

        return is_string($label) && ($label !== '')
            ? $label
            : Str::headline($this->id);
    }

    public function getDescription(): ?string
    {
        if ($this->description === null) {
            return null;
        }

        $description = $this->evaluate($this->description);

        return is_string($description) && ($description !== '') ? $description : null;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function isVisible(): bool
    {
        return (bool) $this->evaluate($this->visible);
    }

    /** @return array<int, Component> */
    public function getSchema(): array
    {
        $schema = $this->schema instanceof Closure
            ? $this->evaluate($this->schema)
            : $this->schema;

        if (is_array($schema) === false) {
            throw new LogicException('Account section schema callback must return an array of Filament schema components.');
        }

        foreach ($schema as $component) {
            if (($component instanceof Component) === false) {
                throw new LogicException('Account section schema must contain only Filament schema components.');
            }
        }

        return array_values($schema);
    }
}

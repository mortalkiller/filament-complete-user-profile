<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Closure;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;
use LogicException;

class Profile extends AbstractFeature
{
    protected int $sort = 20;

    protected bool $avatarEnabled = true;

    protected bool $nameEnabled = true;

    protected bool $emailEnabled = true;

    protected bool $localeEnabled = true;

    protected ?Closure $avatarModifier = null;

    protected ?Closure $nameModifier = null;

    protected ?Closure $emailModifier = null;

    protected ?Closure $localeModifier = null;

    /** @var array<string, string> */
    protected array $localeOptions = [];

    /** @var array<int, Component>|Closure */
    protected array|Closure $additionalFields = [];

    protected ?Closure $fieldsModifier = null;

    protected ?Closure $dataMutator = null;

    protected ?Closure $afterSaveCallback = null;

    public function getId(): string
    {
        return 'profile';
    }

    public function avatar(bool|Closure $value = true): static
    {
        if (is_bool($value)) {
            $this->avatarEnabled = $value;
        } else {
            $this->avatarEnabled = true;
            $this->avatarModifier = $value;
        }

        return $this;
    }

    public function name(bool|Closure $value = true): static
    {
        if (is_bool($value)) {
            $this->nameEnabled = $value;
        } else {
            $this->nameEnabled = true;
            $this->nameModifier = $value;
        }

        return $this;
    }

    public function email(bool|Closure $value = true): static
    {
        if (is_bool($value)) {
            $this->emailEnabled = $value;
        } else {
            $this->emailEnabled = true;
            $this->emailModifier = $value;
        }

        return $this;
    }

    /**
     * @param  bool|array<string, string>|Closure  $value
     */
    public function locale(bool|array|Closure $value = true): static
    {
        if (is_bool($value)) {
            $this->localeEnabled = $value;
        } elseif (is_array($value)) {
            $this->localeEnabled = true;
            $this->localeOptions = $value;
        } else {
            $this->localeEnabled = true;
            $this->localeModifier = $value;
        }

        return $this;
    }

    /** @param array<int, Component>|Closure $fields */
    public function fields(array|Closure $fields): static
    {
        $this->additionalFields = $fields;

        return $this;
    }

    public function modifyFieldsUsing(?Closure $callback): static
    {
        $this->fieldsModifier = $callback;

        return $this;
    }

    public function mutateDataBeforeSaveUsing(?Closure $callback): static
    {
        $this->dataMutator = $callback;

        return $this;
    }

    public function afterSave(?Closure $callback): static
    {
        $this->afterSaveCallback = $callback;

        return $this;
    }

    public function hasAvatar(): bool
    {
        return $this->avatarEnabled;
    }

    public function hasName(): bool
    {
        return $this->nameEnabled;
    }

    public function hasEmail(): bool
    {
        return $this->emailEnabled;
    }

    public function hasLocale(): bool
    {
        return $this->localeEnabled;
    }

    /** @return array<string, string> */
    public function getLocaleOptions(): array
    {
        if ($this->localeOptions !== []) {
            return $this->localeOptions;
        }

        $configured = config('app.supported_locales');

        if (is_array($configured) && $configured !== []) {
            $options = [];

            foreach ($configured as $key => $value) {
                if (is_int($key) && is_string($value)) {
                    $options[$value] = $value;
                } elseif (is_string($key) && is_string($value)) {
                    $options[$key] = $value;
                }
            }

            if ($options !== []) {
                return $options;
            }
        }

        $locale = config('app.locale', 'en');

        return is_string($locale) && $locale !== '' ? [$locale => $locale] : ['en' => 'en'];
    }

    public function configureAvatar(Component $field): Component
    {
        return $this->applyFieldModifier($field, $this->avatarModifier);
    }

    public function configureName(Component $field): Component
    {
        return $this->applyFieldModifier($field, $this->nameModifier);
    }

    public function configureEmail(Component $field): Component
    {
        return $this->applyFieldModifier($field, $this->emailModifier);
    }

    public function configureLocale(Component $field): Component
    {
        return $this->applyFieldModifier($field, $this->localeModifier);
    }

    /** @return array<int, Component> */
    public function getAdditionalFields(): array
    {
        $fields = $this->additionalFields instanceof Closure
            ? ($this->additionalFields)()
            : $this->additionalFields;

        if (! is_array($fields)) {
            throw new LogicException('Profile fields callback must return an array of Filament schema components.');
        }

        foreach ($fields as $field) {
            if (! $field instanceof Component) {
                throw new LogicException('Profile fields must contain only Filament schema components.');
            }
        }

        return array_values($fields);
    }

    /**
     * @param  array<int, Component>  $fields
     * @return array<int, Component>
     */
    public function modifyFields(array $fields): array
    {
        if ($this->fieldsModifier === null) {
            return $fields;
        }

        $modified = ($this->fieldsModifier)($fields);

        if (! is_array($modified)) {
            throw new LogicException('Profile fields modifier must return an array of Filament schema components.');
        }

        foreach ($modified as $field) {
            if (! $field instanceof Component) {
                throw new LogicException('Profile fields modifier must return only Filament schema components.');
            }
        }

        return array_values($modified);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateDataBeforeSave(array $data): array
    {
        if ($this->dataMutator === null) {
            return $data;
        }

        $mutated = ($this->dataMutator)($data);

        if (! is_array($mutated)) {
            throw new LogicException('Profile data mutator must return an array.');
        }

        return $mutated;
    }

    /** @param array<string, mixed> $data */
    public function runAfterSave(Authenticatable $user, array $data): void
    {
        if ($this->afterSaveCallback !== null) {
            ($this->afterSaveCallback)($user, $data);
        }
    }

    protected function applyFieldModifier(Component $field, ?Closure $modifier): Component
    {
        if ($modifier === null) {
            return $field;
        }

        $modified = $modifier($field);

        return $modified instanceof Component ? $modified : $field;
    }
}

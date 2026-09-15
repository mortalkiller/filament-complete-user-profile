<x-filament-panels::page>
    <div class="fcup-account-layout">
        <aside class="fcup-account-navigation" aria-label="{{ __('filament-complete-user-profile::profile.navigation.label') }}">
            <x-filament::section :compact="true">
                <nav class="fcup-account-navigation-list">
                    @foreach ($this->getVisibleFeatures() as $feature)
                        <a
                            href="#account-{{ $feature->getId() }}"
                            class="fcup-account-navigation-link"
                        >
                            {{ $this->getFeatureLabel($feature) }}
                        </a>
                    @endforeach
                </nav>
            </x-filament::section>
        </aside>

        <main class="fcup-account-content">
            {{ $this->content }}
        </main>
    </div>

    <style>
        .fcup-account-layout {
            display: grid;
            grid-template-columns: minmax(13.75rem, 15rem) minmax(0, 1fr);
            gap: 1.5rem;
            align-items: start;
        }

        .fcup-account-navigation {
            position: sticky;
            top: 1.5rem;
        }

        .fcup-account-navigation-list {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .fcup-account-navigation-link {
            display: block;
            padding: .625rem .75rem;
            border-radius: .5rem;
            color: inherit;
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
        }

        .fcup-account-navigation-link:hover,
        .fcup-account-navigation-link:focus-visible {
            background: color-mix(in srgb, currentColor 7%, transparent);
            outline: none;
        }

        .fcup-account-content {
            min-width: 0;
        }

        @media (max-width: 768px) {
            .fcup-account-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .fcup-account-navigation {
                position: static;
                overflow-x: auto;
            }

            .fcup-account-navigation-list {
                flex-direction: row;
                width: max-content;
                min-width: 100%;
            }

            .fcup-account-navigation-link {
                white-space: nowrap;
            }
        }
    </style>
</x-filament-panels::page>

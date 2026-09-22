@php
    $state = $getState();
@endphp

<a
    {{ \Filament\Support\generate_href_html($getUrl(), $shouldOpenUrlInNewTab()) }}
    {{ $attributes->merge($getExtraAttributes())->class(['fcup-security-card']) }}
    @isset($securityEnabled) data-security-enabled="{{ $securityEnabled ? 'true' : 'false' }}" @endisset
>
    <span class="fcup-security-card-icon" aria-hidden="true">
        {{ \Filament\Support\generate_icon_html($getIcon($state)) }}
    </span>
    <span class="fcup-security-card-copy">
        <span class="fcup-security-card-label">{{ $getLabel() }}</span>
        <span class="fcup-security-card-state">
            @isset($securityEnabled)
                <span class="fcup-security-card-dot" aria-hidden="true"></span>
            @endisset
            {{ $state }}
        </span>
    </span>
    {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronRight, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fcup-security-card-chevron', 'aria-hidden' => 'true'])) }}
</a>

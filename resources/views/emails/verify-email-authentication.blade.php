<x-mail::message>
# {{ __('filament-complete-user-profile::profile.security.email_authentication.email.heading') }}

{{ __('filament-complete-user-profile::profile.security.email_authentication.email.intro', ['app' => $appName]) }}

<div style="margin: 28px 0; text-align: center;">
    <div style="display: inline-block; padding: 16px 24px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f9fafb; color: #111827; font-size: 30px; font-weight: 700; letter-spacing: 0.22em; line-height: 1; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">
        {{ $formattedCode }}
    </div>
</div>

{{ trans_choice('filament-complete-user-profile::profile.security.email_authentication.email.expiry', $codeExpiryMinutes, ['minutes' => $codeExpiryMinutes]) }}

**{{ __('filament-complete-user-profile::profile.security.email_authentication.email.warning') }}**

{{ __('filament-complete-user-profile::profile.security.email_authentication.email.ignore') }}

{{ __('filament-complete-user-profile::profile.security.email_authentication.email.footer', ['app' => $appName]) }}
</x-mail::message>

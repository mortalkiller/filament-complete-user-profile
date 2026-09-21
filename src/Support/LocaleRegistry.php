<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Support;

final class LocaleRegistry
{
    /** @var array<string, string> */
    private const NAMES = [
        'af' => 'Afrikaans',
        'am' => 'አማርኛ',
        'ar' => 'العربية',
        'as' => 'অসমীয়া',
        'az' => 'Azərbaycan',
        'be' => 'Беларуская',
        'bg' => 'Български',
        'bn' => 'বাংলা',
        'bo' => 'བོད་ཡིག',
        'bs' => 'Bosanski',
        'ca' => 'Català',
        'ckb' => 'کوردی',
        'cs' => 'Čeština',
        'cy' => 'Cymraeg',
        'da' => 'Dansk',
        'de' => 'Deutsch',
        'dv' => 'ދިވެހި',
        'el' => 'Ελληνικά',
        'en' => 'English',
        'eo' => 'Esperanto',
        'es' => 'Español',
        'et' => 'Eesti',
        'eu' => 'Euskera',
        'fa' => 'فارسی',
        'fi' => 'Suomi',
        'fo' => 'Føroyskt',
        'fr' => 'Français',
        'fy' => 'Frysk',
        'ga' => 'Gaeilge',
        'gd' => 'Gàidhlig',
        'gl' => 'Galego',
        'gu' => 'ગુજરાતી',
        'ha' => 'Hausa',
        'he' => 'עברית',
        'hi' => 'हिन्दी',
        'hr' => 'Hrvatski',
        'hu' => 'Magyar',
        'hy' => 'Հայերեն',
        'id' => 'Indonesia',
        'ig' => 'Igbo',
        'is' => 'Íslenska',
        'it' => 'Italiano',
        'ja' => '日本語',
        'jv' => 'Basa Jawa',
        'ka' => 'ქართული',
        'kk' => 'Қазақ',
        'km' => 'ខ្មែរ',
        'kn' => 'ಕನ್ನಡ',
        'ko' => '한국어',
        'ku' => 'کوردی',
        'ky' => 'Кыргызча',
        'lb' => 'Lëtzebuergesch',
        'lo' => 'ລາວ',
        'lt' => 'Lietuvių',
        'lv' => 'Latviešu',
        'mg' => 'Malagasy',
        'mk' => 'Македонски',
        'ml' => 'മലയാളം',
        'mn' => 'Монгол',
        'mr' => 'मराठी',
        'ms' => 'Bahasa Malaysia',
        'mt' => 'Malti',
        'my' => 'မြန်မာ',
        'nb' => 'Norsk (Bokmål)',
        'ne' => 'नेपाली',
        'nl' => 'Nederlands',
        'nn' => 'Norsk (Nynorsk)',
        'no' => 'Norsk',
        'or' => 'ଓଡ଼ିଆ',
        'pa' => 'ਪੰਜਾਬੀ',
        'pl' => 'Polski',
        'ps' => 'پښتو',
        'pt' => 'Português',
        'ro' => 'Română',
        'ru' => 'Русский',
        'rw' => 'Kinyarwanda',
        'si' => 'සිංහල',
        'sk' => 'Slovenčina',
        'sl' => 'Slovenščina',
        'so' => 'Soomaali',
        'sq' => 'Shqip',
        'sr' => 'Српски',
        'sv' => 'Svenska',
        'sw' => 'Kiswahili',
        'ta' => 'தமிழ்',
        'te' => 'తెలుగు',
        'th' => 'ไทย',
        'tr' => 'Türkçe',
        'uk' => 'Українська',
        'ur' => 'اردو',
        'uz' => "O'zbek",
        'vi' => 'Tiếng Việt',
        'zh' => '中文',
        'zu' => 'isiZulu',

        'de_AT' => 'Deutsch (Österreich)',
        'de_CH' => 'Deutsch (Schweiz)',
        'en_AU' => 'English (Australia)',
        'en_CA' => 'English (Canada)',
        'en_GB' => 'English (United Kingdom)',
        'en_IE' => 'English (Ireland)',
        'en_IN' => 'English (India)',
        'en_NZ' => 'English (New Zealand)',
        'en_US' => 'English (United States)',
        'en_ZA' => 'English (South Africa)',
        'es_AR' => 'Español (Argentina)',
        'es_CL' => 'Español (Chile)',
        'es_CO' => 'Español (Colombia)',
        'es_MX' => 'Español (México)',
        'es_PE' => 'Español (Perú)',
        'fr_BE' => 'Français (Belgique)',
        'fr_CA' => 'Français (Canada)',
        'fr_CH' => 'Français (Suisse)',
        'it_CH' => 'Italiano (Svizzera)',
        'nl_BE' => 'Nederlands (België)',
        'pt_AO' => 'Português (Angola)',
        'pt_BR' => 'Português (Brasil)',
        'pt_MZ' => 'Português (Moçambique)',
        'pt_PT' => 'Português (Portugal)',
        'zh_CN' => '简体中文',
        'zh_HK' => '繁體中文 (香港)',
        'zh_TW' => '繁體中文',
    ];

    public static function name(string $localeCode): string
    {
        $normalized = self::normalize($localeCode);

        return self::NAMES[$normalized] ?? $localeCode;
    }

    private static function normalize(string $localeCode): string
    {
        $parts = explode('_', str_replace('-', '_', $localeCode), 2);
        $language = strtolower($parts[0]);

        if (! isset($parts[1]) || $parts[1] === '') {
            return $language;
        }

        return $language.'_'.strtoupper($parts[1]);
    }
}

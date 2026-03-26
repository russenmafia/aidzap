<?php
declare(strict_types=1);

const COUNTRIES = [
    'AT' => 'Austria',          'DE' => 'Germany',          'CH' => 'Switzerland',
    'US' => 'United States',    'GB' => 'United Kingdom',   'FR' => 'France',
    'ES' => 'Spain',            'IT' => 'Italy',            'NL' => 'Netherlands',
    'PL' => 'Poland',           'RU' => 'Russia',           'UA' => 'Ukraine',
    'TR' => 'Turkey',           'BR' => 'Brazil',           'IN' => 'India',
    'CN' => 'China',            'JP' => 'Japan',            'KR' => 'South Korea',
    'CA' => 'Canada',           'AU' => 'Australia',        'MX' => 'Mexico',
    'AR' => 'Argentina',        'NG' => 'Nigeria',          'ZA' => 'South Africa',
    'EG' => 'Egypt',            'ID' => 'Indonesia',        'PH' => 'Philippines',
    'TH' => 'Thailand',         'VN' => 'Vietnam',          'PK' => 'Pakistan',
    'BD' => 'Bangladesh',       'SE' => 'Sweden',           'NO' => 'Norway',
    'DK' => 'Denmark',          'FI' => 'Finland',          'BE' => 'Belgium',
    'PT' => 'Portugal',         'CZ' => 'Czech Republic',   'RO' => 'Romania',
    'HU' => 'Hungary',          'GR' => 'Greece',           'SG' => 'Singapore',
    'MY' => 'Malaysia',         'HK' => 'Hong Kong',        'AE' => 'UAE',
    'SA' => 'Saudi Arabia',     'IL' => 'Israel',
];

const LANGUAGES = [
    'en' => 'English',      'de' => 'German',       'fr' => 'French',
    'es' => 'Spanish',      'it' => 'Italian',      'pt' => 'Portuguese',
    'ru' => 'Russian',      'zh' => 'Chinese',      'ja' => 'Japanese',
    'ko' => 'Korean',       'ar' => 'Arabic',        'hi' => 'Hindi',
    'nl' => 'Dutch',        'pl' => 'Polish',        'tr' => 'Turkish',
    'sv' => 'Swedish',      'da' => 'Danish',        'fi' => 'Finnish',
    'no' => 'Norwegian',    'uk' => 'Ukrainian',     'cs' => 'Czech',
    'ro' => 'Romanian',     'hu' => 'Hungarian',     'el' => 'Greek',
    'id' => 'Indonesian',   'th' => 'Thai',          'vi' => 'Vietnamese',
];

const DEVICES = ['desktop', 'mobile', 'tablet'];

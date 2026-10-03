<?php

if (! defined('IDNA_DEFAULT')) {
    define('IDNA_DEFAULT', 0);
}

if (! defined('INTL_IDNA_VARIANT_UTS46')) {
    define('INTL_IDNA_VARIANT_UTS46', 1);
}

if (! class_exists('Locale')) {
    class Locale
    {
        private static string $default = 'en';

        public static function setDefault(string $locale): bool
        {
            self::$default = $locale;
            return true;
        }

        public static function getDefault(): string
        {
            return self::$default;
        }
    }
}

if (! class_exists('Normalizer')) {
    class Normalizer
    {
        public const FORM_C = 4;

        public static function normalize(string $string, int $form = self::FORM_C)
        {
            return $string;
        }

        public static function isNormalized(string $string, int $form = self::FORM_C): bool
        {
            return true;
        }
    }
}

if (! function_exists('idn_to_ascii')) {
    function idn_to_ascii(string $domain, int $flags = IDNA_DEFAULT, int $variant = INTL_IDNA_VARIANT_UTS46, ?array &$idna_info = null)
    {
        return $domain;
    }
}

if (! function_exists('idn_to_utf8')) {
    function idn_to_utf8(string $domain, int $flags = IDNA_DEFAULT, int $variant = INTL_IDNA_VARIANT_UTS46, ?array &$idna_info = null)
    {
        return $domain;
    }
}

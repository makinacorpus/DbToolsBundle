<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper;

/**
 * Code identified as such in the below class originated from FakerPHP library
 * released under the MIT licence with the following copyright:
 *
 * Copyright (c) 2011 François Zaninotto
 * Portions Copyright (c) 2008 Caius Durling
 * Portions Copyright (c) 2008 Adam Royle
 * Portions Copyright (c) 2008 Fiona Burrows
 *
 * @see https://github.com/FakerPHP/Faker/blob/2.0/LICENSE
 */
class Iban
{
    /**
     * List of supported country codes.
     */
    public static function supportedCountries(): array
    {
        return \array_keys(self::$ibanFormats);
    }

    /**
     * International Bank Account Number (IBAN)
     *
     * @see http://en.wikipedia.org/wiki/International_Bank_Account_Number
     *
     * @param string $countryCode
     *   ISO 3166-1 alpha-2 country code.
     * @param string $prefix
     *   For generating bank account number of a specific bank.
     * @param int $length
     *   Total length without country code and 2 check digits.
     *
     * @see https://github.com/FakerPHP/Faker
     */
    public static function iban(?string $countryCode = null, string $prefix = '', ?int $length = null): string
    {
        $countryCode = \strtoupper($countryCode ?? \array_rand(self::$ibanFormats));
        $format = self::$ibanFormats[$countryCode] ?? [['n', $length ?? 24]];

        $letterMin = \ord('A');
        $letterMax = \ord('Z');

        $result = '';
        foreach ($format as $piece) {
            list($type, $size) = $piece;
            for ($i = 0; $i < $size; ++$i) {
                $result .= match ($type) {
                    'c' => \rand(0, 1) ? \rand(0, 9) : \chr(\rand($letterMin, $letterMax)),
                    'a' => \chr(\rand($letterMin, $letterMax)),
                    // Is also 'n'.
                    default => \rand(0, 9),
                };
            }
        }

        if ($prefix) {
            $result = \substr($result, \strlen($prefix));
        }

        $checksum = self::checksum($countryCode . '00' . $result);

        return $countryCode . $checksum . $result;
    }

    public static function bic(): string
    {
        $result = '';

        $letterMin = \ord('A');
        $letterMax = \ord('Z');

        // BIC format can be 8 or 11 characters long.
        // Format is: LLLL LL XX (XXX)
        // Where L means letter, and X can be either a letter or a number.
        for ($i = 0; $i < 6; ++$i) {
            $result .= \chr(\rand($letterMin, $letterMax));
        }
        for ($i = 0; $i < 2; ++$i) {
            $result .= \rand(0, 1) ? \rand(0, 9) : \chr(\rand($letterMin, $letterMax));
        }

        return $result;
    }

    /**
     * @see https://github.com/FakerPHP/Faker
     */
    public static function checksum(string $iban): string
    {
        // Move first four digits to end and set checksum to '00'.
        // For example: FR76 ABCD 15 => ABCD FR76 00.
        $checkString = \substr($iban, 4) . \substr($iban, 0, 2) . '00';

        // Replace all letters with their number equivalents.
        // Number equivalent is from the IBAN spec: \ord(char) - 55:
        // "A" si 10, "B" is 11, ...
        $checkString = \preg_replace_callback(
            '/[A-Z]/',
            static fn (array $matches) => (string) (\ord($matches[0]) - 55),
            $checkString,
        );

        // Perform mod 97 and subtract from 98.
        $checksum = 98 - self::mod97($checkString);

        return \str_pad((string) $checksum, 2, '0', \STR_PAD_LEFT);
    }

    /**
     * @see \Symfony\Component\Validator\Constraints\IbanValidator::bigModulo97().
     */
    private static function mod97(string $number): int
    {
        $parts = \str_split($number, 7);
        $rest = 0;
        foreach ($parts as $part) {
            $rest = \intval($rest . $part) % 97;
        }
        return (int)$rest;
    }

    /**
     * IBAN formats codification.
     *
     * @see https://www.swift.com/standards/data-standards/iban
     * @see https://github.com/FakerPHP/Faker
     */
    private static $ibanFormats = [
        'AD' => [['n', 4], ['n', 4], ['c', 12]],
        'AE' => [['n', 3], ['n', 16]],
        'AL' => [['n', 8], ['c', 16]],
        'AT' => [['n', 5], ['n', 11]],
        'AZ' => [['a', 4], ['c', 20]],
        'BA' => [['n', 3], ['n', 3], ['n', 8], ['n', 2]],
        'BE' => [['n', 3], ['n', 7], ['n', 2]],
        'BG' => [['a', 4], ['n', 4], ['n', 2], ['c', 8]],
        'BH' => [['a', 4], ['c', 14]],
        'BR' => [['n', 8], ['n', 5], ['n', 10], ['a', 1], ['c', 1]],
        'CH' => [['n', 5], ['c', 12]],
        'CR' => [['n', 4], ['n', 14]],
        'CY' => [['n', 3], ['n', 5], ['c', 16]],
        'CZ' => [['n', 4], ['n', 6], ['n', 10]],
        'DE' => [['n', 8], ['n', 10]],
        'DK' => [['n', 4], ['n', 9], ['n', 1]],
        'DO' => [['c', 4], ['n', 20]],
        'EE' => [['n', 2], ['n', 2], ['n', 11], ['n', 1]],
        'EG' => [['n', 4], ['n', 4], ['n', 17]],
        'ES' => [['n', 4], ['n', 4], ['n', 1], ['n', 1], ['n', 10]],
        'FI' => [['n', 6], ['n', 7], ['n', 1]],
        'FR' => [['n', 5], ['n', 5], ['c', 11], ['n', 2]],
        'GB' => [['a', 4], ['n', 6], ['n', 8]],
        'GE' => [['a', 2], ['n', 16]],
        'GI' => [['a', 4], ['c', 15]],
        'GR' => [['n', 3], ['n', 4], ['c', 16]],
        'GT' => [['c', 4], ['c', 20]],
        'HR' => [['n', 7], ['n', 10]],
        'HU' => [['n', 3], ['n', 4], ['n', 1], ['n', 15], ['n', 1]],
        'IE' => [['a', 4], ['n', 6], ['n', 8]],
        'IL' => [['n', 3], ['n', 3], ['n', 13]],
        'IS' => [['n', 4], ['n', 2], ['n', 6], ['n', 10]],
        'IT' => [['a', 1], ['n', 5], ['n', 5], ['c', 12]],
        'KW' => [['a', 4], ['n', 22]],
        'KZ' => [['n', 3], ['c', 13]],
        'LB' => [['n', 4], ['c', 20]],
        'LI' => [['n', 5], ['c', 12]],
        'LT' => [['n', 5], ['n', 11]],
        'LU' => [['n', 3], ['c', 13]],
        'LV' => [['a', 4], ['c', 13]],
        'MC' => [['n', 5], ['n', 5], ['c', 11], ['n', 2]],
        'MD' => [['c', 2], ['c', 18]],
        'ME' => [['n', 3], ['n', 13], ['n', 2]],
        'MK' => [['n', 3], ['c', 10], ['n', 2]],
        'MR' => [['n', 5], ['n', 5], ['n', 11], ['n', 2]],
        'MT' => [['a', 4], ['n', 5], ['c', 18]],
        'MU' => [['a', 4], ['n', 2], ['n', 2], ['n', 12], ['n', 3], ['a', 3]],
        'NL' => [['a', 4], ['n', 10]],
        'NO' => [['n', 4], ['n', 6], ['n', 1]],
        'PK' => [['a', 4], ['c', 16]],
        'PL' => [['n', 8], ['n', 16]],
        'PS' => [['a', 4], ['c', 21]],
        'PT' => [['n', 4], ['n', 4], ['n', 11], ['n', 2]],
        'RO' => [['a', 4], ['c', 16]],
        'RS' => [['n', 3], ['n', 13], ['n', 2]],
        'SA' => [['n', 2], ['c', 18]],
        'SE' => [['n', 3], ['n', 16], ['n', 1]],
        'SI' => [['n', 5], ['n', 8], ['n', 2]],
        'SK' => [['n', 4], ['n', 6], ['n', 10]],
        'SM' => [['a', 1], ['n', 5], ['n', 5], ['c', 12]],
        'TN' => [['n', 2], ['n', 3], ['n', 13], ['n', 2]],
        'TR' => [['n', 5], ['n', 1], ['c', 16]],
        'VG' => [['a', 4], ['n', 16]],
    ];
}

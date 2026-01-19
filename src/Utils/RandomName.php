<?php
/**
 * User: Andrey Shamis
 * Date: 24/02/18
 * Time: 15:36
 */

namespace App\Utils;

final class RandomName
{
    /**
     * Looks for suffixes in strings in a case-insensitive way.
     * @param string $value
     * @param string $suffix
     * @return bool
     */
    public static function hasSuffix(string $value, string $suffix): bool
    {
        return 0 === strcasecmp($suffix, substr($value, -strlen($suffix)));
    }

    /**
     * Ensures that the given string ends with the given suffix. If the string
     * already contains the suffix, it's not added twice. It's case-insensitive
     * (e.g. value: 'Foocommand' suffix: 'Command' -> result: 'FooCommand').
     * @param string $value
     * @param string $suffix
     * @return string
     */
    public static function addSuffix(string $value, string $suffix): string
    {
        return self::removeSuffix($value, $suffix).$suffix;
    }

    /**
     * Ensures that the given string doesn't end with the given suffix. If the
     * string contains the suffix multiple times, only the last one is removed.
     * It's case-insensitive (e.g. value: 'Foocommand' suffix: 'Command' -> result: 'Foo'.
     * @param string $value
     * @param string $suffix
     * @return string
     */
    public static function removeSuffix(string $value, string $suffix): string
    {
        return self::hasSuffix($value, $suffix) ? substr($value, 0, -strlen($suffix)) : $value;
    }

    /**
     * Transforms the given string into the format commonly used by PHP classes,
     * (e.g. `app:do_this-and_that` -> `AppDoThisAndThat`) but it doesn't check
     * the validity of the class name.
     * @param string $value
     * @param string $suffix
     * @return string
     */
    public static function asClassName(string $value, string $suffix = ''): string
    {
        $value = trim($value);
        $value = str_replace(['-', '_', '.', ':'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);
        $value = ucfirst($value);
        $value = self::addSuffix($value, $suffix);

        return $value;
    }

    /**
     * Transforms the given string into the format commonly used by Twig variables
     * (e.g. `BlogPostType` -> `blog_post_type`).
     * @param string $value
     * @return string
     */
    public static function asTwigVariable(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^a-zA-Z0-9_]/', '_', $value);
        $value = preg_replace('/(?<=\\w)([A-Z])/', '_$1', $value);
        $value = preg_replace('/_{2,}/', '_', $value);
        $value = strtolower($value);

        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function asRoutePath(string $value): string
    {
        return '/'.str_replace('_', '/', self::asTwigVariable($value));
    }

    /**
     * @param string $value
     * @return string
     */
    public static function asRouteName(string $value): string
    {
        return self::asTwigVariable($value);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function asCommand(string $value): string
    {
        return str_replace('_', '-', self::asTwigVariable($value));
    }

    /**
     * @param string $eventName
     * @return string
     */
    public static function asEventMethod(string $eventName): string
    {
        return sprintf('on%s', self::asClassName($eventName));
    }

    /**
     * @return string
     */
    public static function getRandomTerm(): string
    {
        $adjectives = [
            'tiny',
            'delicious',
            'gentle',
            'agreeable',
            'brave',
            'orange',
            'grumpy',
            'fierce',
            'victorious',
            'angry',
            'annoyed',
            'better',
            'black',
            'blue',
            'busy',
            'clean',
            'colorful',
            'crazy',
            'dark',
            'dizzy',
            'easy',
            'elated',
            'encouraging',
            'fair',
            'fancy',
            'fine',
            'frail',
            'gifted',
            'happy',
            'important',
            'jealous',
            'kind',
            'lazy',
            'light',
            'long',
            'nice',
            'plain',
            'real',
            'sleepy',
            'terrible',
            'ugly',
            'unusual',
            'vast',
            'wild',
            'xenophobic',
            'yellowed',
            'zany',
        ];
        $nouns = [
            'pizza',
            'chef',
            'puppy',
            'kangaroo',
            'ape',
            'bear',
            'camel',
            'dog',
            'elephant',
            'falcon',
            'fish',
            'frog',
            'gentoo',
            'gnome',
            'hamster',
            'hippopotamus',
            'iguana',
            'jellybean',
            'kangaroo',
            'koala',
            'lark',
            'lemur',
            'leopard',
            'lion',
            'llama',
            'macaque',
            'macaw',
            'magpie',
            'mamba',
            'marten',
            'mink',
            'narwhal',
            'newt',
            'octopus',
            'okapi',
            'oryx',
            'oyster',
            'panda',
            'panther',
            'parrot',
            'partridge',
            'peacock',
            'pigeon',
            'plover',
            'puma',
            'quagga',
            'rabbit',
            'raccoon',
            'rat',
            'sable',
            'salamander',
            'salmon',
            'snail',
            'snake',
            'spider',
            'tamarin',
            'tarantula',
            'tiger',
            'tortoise',
            'unicorn',
            'vendace',
            'vole',
            'wallaby',
            'wolf',
            'xenomorph',
            'yacare',
            'zebra',
        ];

        return sprintf('%s %s', $adjectives[array_rand($adjectives)], $nouns[array_rand($nouns)]);
    }
}
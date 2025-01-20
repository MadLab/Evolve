<?php

namespace MadLab\Evolve;

class BladeManager
{
    protected static string $currentTest = '';
    /*
     *  structure: [
     *      testName => [
     *          'default' => '',
     *          'variants' => []
     *      ]
     * ]
     */
    protected static array $tests = [];

    public static function start(string $testName)
    {
        self::$currentTest = $testName;
        self::$tests[self::$currentTest] = [
            'default'  => '',
            'variants'   => [],
        ];
    }

    public static function startDefault()
    {
        // Begin capturing default content
    }

    public static function endDefault(string $content)
    {
        self::$tests[self::$currentTest]['default'] = $content;
    }

    public static function startVariant()
    {
        // Begin capturing a new variant
    }

    public static function endVariant(string $content)
    {
        self::$tests[self::$currentTest]['variants'][] = $content;
    }

    public static function render()
    {
        $test = self::$tests[self::$currentTest] ?? null;
        if (!$test) {
            return '';
        }

        // Combine default + all variants into one array
        $combined = array_merge(
            [$test['default']],
            $test['variants']
        );

        // Randomly pick one (for demo)
        return $combined[array_rand($combined)];
    }

    /**
     * Optional: get all variants (including default) for admin interface
     */
    public static function getVariants(string $testName): array
    {
        $test = self::$tests[$testName] ?? null;
        if (!$test) {
            return [];
        }

        // Return them as [ 'default' => '...', 'variants' => [...] ]
        return [
            'default'  => $test['default'],
            'variants' => $test['variants'],
        ];
    }
}
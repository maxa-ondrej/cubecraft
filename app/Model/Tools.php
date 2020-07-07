<?php

namespace App\Model;


class Tools
{
    public const
        MUTED = 'muted',
        PRIMARY = 'primary',
        SUCCESS = 'success',
        INFO = 'info',
        WARNING = 'warning',
        DANGER = 'danger',
        SECONDARY = 'secondary',
        WHITE = 'white',
        DARK = 'dark',
        BODY = 'body',
        LIGHT = 'light';

    /**
     * Colorizes text
     *
     * @param string $color
     * @param string $text
     * @return string
     */
    public static function colorize(string $color, string $text): string
    {
        return '<span class="text-'.$color.'">'.$text.'</span>';
    }


    /**
     * Generates rows from file
     *
     * @param string $filename
     * @return array
     */
    public static function generateTempFilename(): string
    {
        while (true) {
            $filename = __DIR__ . '/../../temp/' . uniqid('Sheet', true) . '.php';
            if (!file_exists($filename)) {
                return $filename;
            }
        }
    }

    /**
     * Replaces nth occurance
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @param int $nth
     * @return string
     */
    public static function str_replace_nth(string $search, string $replace, string $subject, int $nth): string
    {
        $found = preg_match_all($search, $subject, $matches, PREG_OFFSET_CAPTURE);
        if (false !== $found && $found > $nth) {
            return substr_replace($subject, $replace, $matches[0][$nth][1], strlen($matches[0][$nth][0]));
        }
        return $subject;
    }
}

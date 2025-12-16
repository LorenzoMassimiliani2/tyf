<?php

// Some environments may not have the mbstring extension enabled. Breeze/Artisan
// use mb_split when generating StudlyCase strings, so we provide a tiny
// fallback to keep the CLI usable without requiring system packages.
if (!function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array
    {
        $delimited = '/' . $pattern . '/u';

        return preg_split($delimited, $string, $limit);
    }
}

<?php

use Illuminate\Support\Str;

if (! function_exists('tenant_slug')) {
    function tenant_slug(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }
}

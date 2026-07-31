<?php
use Carbon\Carbon;
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
if (! function_exists('data_atual')) {
    function data_atual(): string
    {
        return Carbon::now()->format('d/m/Y');
    }
}
if (! function_exists('data_atual_mais_um_dia')) {
    function data_atual_mais_um_dia(): string
    {
        return Carbon::now()->addDay()->format('d/m/Y');
    }
}
function pessoa_tipo(string $value): string
{
    $numeros = preg_replace('/\D/', '', $value);
    if (strlen($numeros) === 11) {
        return 'F';
    }
    if (strlen($numeros) === 14) {
        return 'J';
    }
    return '';
}
if (! function_exists('data_expedicao')) {
    function data_expedicao(): string
    {
        return Carbon::now()->format('Y-m-d');
    }
}
if (! function_exists('data_expedicao_um_dia_mais')) {
    function data_expedicao_um_dia_mais(string | DateTime | Carbon | null $data = null): string
    {
        $base = is_null($data) ? Carbon::now() : Carbon::parse($data);
        return $base->addDay()->format('Y-m-d');
    }
}

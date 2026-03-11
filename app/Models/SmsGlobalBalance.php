<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsGlobalBalance extends Model
{
    protected $connection = 'mysql';
    protected $table      = 'sms_global_balance';
    protected $fillable   = ['balance'];

    /** Retorna a única linha do saldo global, criando se não existir. */
    public static function instance(): self
    {
        return static::firstOrCreate([], ['balance' => 0]);
    }

    public function hasBalance(int $amount): bool
    {
        return $this->balance >= $amount;
    }
}

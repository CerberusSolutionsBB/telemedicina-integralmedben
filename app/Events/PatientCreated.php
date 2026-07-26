<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $tenantPatientId,
        public string $tenantId,
        public array $answers,
        public bool $smsAlreadySent = false,
    ) {}
}

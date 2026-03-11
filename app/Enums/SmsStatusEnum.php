<?php

namespace App\Enums;

enum SmsStatusEnum: string
{
    case Pending = 'pending';
    case Sent    = 'sent';
    case Failed  = 'failed';
}

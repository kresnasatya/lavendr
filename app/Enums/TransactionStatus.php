<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}

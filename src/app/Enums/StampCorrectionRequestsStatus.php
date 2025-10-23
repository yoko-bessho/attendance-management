<?php

namespace App\Enums;

enum StampCorrectionRequestsStatus: string
{
    case PENDING = 'pending';
    case APPROVAL = 'approval';
}

<?php

namespace App\Enums;

enum StampCorrectionRequestsStatus: string
{
    case PENDING = 'pending';
    case APPROVAL = 'approval';

    public static function fromTab(string $tab): self
    {
        return match($tab) {
            'pending' => self::PENDING,
            'approval' => self::APPROVAL,
            default => self::PENDING,
        };
    }

    public function label(): string
    {
        return match($this) {
            self::PENDING => '承認待ち',
            self::APPROVAL => '承認済み',
        };
    }

}

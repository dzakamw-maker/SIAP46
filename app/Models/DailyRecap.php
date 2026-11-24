<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyRecap extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'recap_date' => 'date',
            'opening_bni_balance' => 'decimal:2',
            'opening_cash_balance' => 'decimal:2',
            'bni_balance_remaining' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'stamp_remaining_value' => 'decimal:2',
            'total' => 'decimal:2',
            'is_closed' => 'boolean',
        ];
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Mengambil data saldo awal loket hari ini (jika sudah ada kasir yang menginput).
     */
    public static function todayOpening(): ?self
    {
        return static::whereDate('recap_date', today())
            ->whereNotNull('opening_bni_balance')
            ->first();
    }
}

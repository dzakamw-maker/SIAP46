<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    public const STAMP_SELLING_PRICE = 'stamp_selling_price';

    public const STAMP_COST_PRICE = 'stamp_cost_price';

    /**
     * Ambil nilai integer dari pengaturan sistem, atau kembalikan default jika belum tersimpan.
     */
    public static function getInt(string $key, int $default): int
    {
        $val = static::where('key', $key)->value('value');

        return $val !== null ? (int) $val : $default;
    }

    /**
     * Simpan atau perbarui nilai pengaturan sistem berdasarkan key.
     */
    public static function setValue(string $key, string|int|float $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );
    }
}

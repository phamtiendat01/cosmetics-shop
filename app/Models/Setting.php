<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];
    public $timestamps = false; // để trống created_at/updated_at cũng không sao

    /* ========== Helper ========== */
    public static function get(string $key, $default = null)
    {
        $raw = Cache::rememberForever(
            "setting.$key",
            fn() =>
            static::query()->where('key', $key)->value('value')
        );

        if ($raw === null) return $default;
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    public static function set(string $key, $value): void
    {
        $stored = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        static::updateOrCreate(['key' => $key], ['value' => $stored]);
        Cache::forget("setting.$key");
    }

    public static function setMany(array $dotArray): void
    {
        foreach ($dotArray as $k => $v) static::set($k, $v);
    }

    public static function forget(string $key): void
    {
        static::query()->where('key', $key)->delete();
        Cache::forget("setting.$key");
    }

    /* ========== Áp cấu hình runtime ========== */
    public static function syncRuntime(): void
    {
        if (!Schema::hasTable('settings')) return;

        // app name/locale/timezone
        if ($v = static::get('store.name'))     Config::set('app.name', $v);
        if ($v = static::get('store.locale')) {
            Config::set('app.locale', $v);
            app()->setLocale($v);
        }
        if ($v = static::get('store.timezone')) {
            Config::set('app.timezone', $v);
            date_default_timezone_set($v);
        }

        // mail from
        if ($addr = static::get('mail.from_address')) {
            Config::set('mail.from.address', $addr);
            Config::set('mail.from.name', static::get('mail.from_name', $addr));
        }

        // SMTP (nếu có)
        if ($host = static::get('smtp.host')) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', static::get('smtp.port', 587));
            Config::set('mail.mailers.smtp.username', static::get('smtp.username'));
            Config::set('mail.mailers.smtp.password', static::get('smtp.password'));
            Config::set('mail.mailers.smtp.encryption', static::get('smtp.encryption')); // tls|ssl|null
        }
    }
}

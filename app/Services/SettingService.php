<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_PREFIX = 'app_setting_';

    /**
     * Get a setting by key, with default fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(self::CACHE_PREFIX . $key, 3600, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            return $setting->cast_value;
        });
    }

    /**
     * Set / update a setting.
     */
    public function set(string $key, mixed $value, ?string $type = null, ?string $group = null, ?string $label = null, ?string $description = null): Setting
    {
        $setting = Setting::firstOrNew(['key' => $key]);

        if (is_array($value) || is_object($value)) {
            $setting->value = json_encode($value);
            $type = $type ?? 'json';
        } elseif (is_bool($value)) {
            $setting->value = $value ? '1' : '0';
            $type = $type ?? 'boolean';
        } else {
            $setting->value = (string) $value;
        }

        if ($type) {
            $setting->type = $type;
        }
        if ($group) {
            $setting->group = $group;
        }
        if ($label) {
            $setting->label = $label;
        }
        if ($description) {
            $setting->description = $description;
        }

        $setting->save();
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * Return all settings grouped by their group identifier.
     */
    public function allGrouped(): array
    {
        $this->seedDefaultsIfEmpty();

        $all = Setting::orderBy('group')->orderBy('id')->get();
        $grouped = [];

        foreach ($all as $item) {
            $grouped[$item->group][] = $item;
        }

        return $grouped;
    }

    /**
     * Ensure baseline application settings exist.
     */
    public function seedDefaultsIfEmpty(): void
    {
        if (Setting::count() > 0) {
            return;
        }

        $defaults = [
            // Subscriptions & Billing
            [
                'key' => 'trial_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'subscription',
                'label' => 'Free Trial Duration (Days)',
                'description' => 'Default free trial period granted to newly registered users.',
            ],
            [
                'key' => 'subscription_price',
                'value' => '49',
                'type' => 'float',
                'group' => 'subscription',
                'label' => 'Subscription Plan Price (₹)',
                'description' => 'Monthly subscription plan price in Indian Rupees (INR).',
            ],
            [
                'key' => 'subscription_duration_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'subscription',
                'label' => 'Subscription Duration (Days)',
                'description' => 'Number of days access granted upon successful subscription payment.',
            ],
            [
                'key' => 'currency_symbol',
                'value' => '₹',
                'type' => 'string',
                'group' => 'subscription',
                'label' => 'Currency Symbol',
                'description' => 'Currency symbol shown throughout price tags and subscription interfaces.',
            ],

            // System & Synchronization
            [
                'key' => 'sync_interval_hours',
                'value' => '1',
                'type' => 'integer',
                'group' => 'system',
                'label' => 'Automated Sync Interval (Hours)',
                'description' => 'Frequency in hours for the automated Telegram channel message crawler.',
            ],
            [
                'key' => 'search_pagination_limit',
                'value' => '24',
                'type' => 'integer',
                'group' => 'system',
                'label' => 'Search Results Per Page',
                'description' => 'Number of canonical product cards loaded per page or batch in search results.',
            ],
            [
                'key' => 'enable_public_registration',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Enable Public Registration',
                'description' => 'When disabled, new signups will be blocked; only admins can invite or create accounts.',
            ],
        ];

        foreach ($defaults as $d) {
            Setting::create($d);
        }
    }
}

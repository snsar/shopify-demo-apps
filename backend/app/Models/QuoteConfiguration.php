<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop',
        'display_rule',
        'position',
        'type',
        'is_active',
        'style_config',
        'additional_config',
        'synced_at'
    ];

    protected $casts = [
        'style_config' => 'array',
        'additional_config' => 'array',
        'is_active' => 'boolean',
        'type' => 'integer',
        'synced_at' => 'datetime'
    ];

    /**
     * Get configuration for a shop
     */
    public static function getForShop(string $shop): ?self
    {
        return static::where('shop', $shop)->first();
    }

    /**
     * Create or update configuration for a shop
     */
    public static function updateOrCreateForShop(string $shop, array $config): self
    {
        $displayRule = $config['displayRule'] ?? 'all';

        return static::updateOrCreate(
            ['shop' => $shop],
            [
                'display_rule' => $displayRule,
                'position' => $config['position'] ?? 'under-button',
                'type' => $displayRule === 'all' ? 1 : 2,
                'is_active' => $config['isActive'] ?? true,
                'style_config' => [
                    'buttonLabel' => $config['buttonLabel'] ?? 'Request for quote',
                    'alignment' => $config['alignment'] ?? 'center',
                    'fontSize' => $config['fontSize'] ?? 15,
                    'cornerRadius' => $config['cornerRadius'] ?? 15,
                    'textColor' => $config['textColor'] ?? ['hue' => 0, 'brightness' => 1, 'saturation' => 0],
                    'buttonColor' => $config['buttonColor'] ?? ['hue' => 39, 'brightness' => 1, 'saturation' => 1],
                ],
                'additional_config' => $config['additional'] ?? null,
                'synced_at' => now()
            ]
        );
    }

    /**
     * Convert to frontend format
     */
    public function toFrontendFormat(): array
    {
        return [
            'displayRule' => $this->display_rule,
            'position' => $this->position,
            'isActive' => $this->is_active,
            'buttonLabel' => $this->style_config['buttonLabel'] ?? 'Request for quote',
            'alignment' => $this->style_config['alignment'] ?? 'center',
            'fontSize' => $this->style_config['fontSize'] ?? 15,
            'cornerRadius' => $this->style_config['cornerRadius'] ?? 15,
            'textColor' => $this->style_config['textColor'] ?? ['hue' => 0, 'brightness' => 1, 'saturation' => 0],
            'buttonColor' => $this->style_config['buttonColor'] ?? ['hue' => 39, 'brightness' => 1, 'saturation' => 1],
        ];
    }

    /**
     * Convert to Shopify metafield format
     */
    public function toMetafieldFormat(): array
    {
        return [
            'displayRule' => $this->display_rule,
            'position' => $this->position,
            'buttonLabel' => $this->style_config['buttonLabel'] ?? 'Request for quote',
            'alignment' => $this->style_config['alignment'] ?? 'center',
            'fontSize' => $this->style_config['fontSize'] ?? 15,
            'cornerRadius' => $this->style_config['cornerRadius'] ?? 15,
            'textColor' => $this->style_config['textColor'] ?? ['hue' => 0, 'brightness' => 1, 'saturation' => 0],
            'buttonColor' => $this->style_config['buttonColor'] ?? ['hue' => 39, 'brightness' => 1, 'saturation' => 1],
            'isActive' => $this->is_active,
        ];
    }

    /**
     * Check if needs sync with Shopify
     */
    public function needsSync(): bool
    {
        return $this->synced_at === null || $this->synced_at->lt($this->updated_at);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineTrainingOption extends Model
{
    use HasFactory;

    protected $table = 'offline_training_options';

    protected $fillable = [
        'option_type',
        'value',
        'explanation',
        'normalized_value',
        'year',
        'scope_key',
        'source',
        'parent_id',
        'classification_id',
        'is_active',
        'sort_order',
        'created_from',
        'created_by',
    ];

    protected $casts = [
        'year'              => 'integer',
        'parent_id'         => 'integer',
        'classification_id' => 'integer',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
        'created_by'        => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->year !== null && (int) $model->year > 0) {
                $model->scope_key = self::buildScopeKey((int) $model->year);
            } elseif (empty($model->scope_key)) {
                $model->scope_key = 'global';
                $model->year = null;
            }

            if (empty($model->normalized_value) && !empty($model->value)) {
                $model->normalized_value = self::normalizeString($model->value);
            }
        });
    }

    public static function buildScopeKey(?int $year): string
    {
        return ($year !== null && $year > 0) ? 'year:' . $year : 'global';
    }

    public static function normalizeString(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class, 'classification_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function courseExamples(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('option_type', OfflineOptionService::TYPE_COURSE_NAME);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('option_type', $type);
    }

    public function scopeForScope(Builder $query, ?int $year = null): Builder
    {
        return $query->where('scope_key', self::buildScopeKey($year));
    }

    public function scopeForSource(Builder $query, ?string $source = null): Builder
    {
        if ($source === null || $source === '' || $source === 'all') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($source) {
            $q->where('source', $source)
              ->orWhere('source', 'all')
              ->orWhereNull('source');
        });
    }
}

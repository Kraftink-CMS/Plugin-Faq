<?php

namespace KraftInk\Plugins\Faq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * @mixin Builder<static>
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $locale
 * @property string $name
 * @property string|null $description
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category|null $parent
 */
class Category extends Model
{
    protected $table = 'faq__categories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'position' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'id' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    public static function descendantIds(int $id, ?Collection $categories = null): array
    {
        $children = $categories?->groupBy('parent_id');
        $ids = [$id];
        $level = [$id];
        for ($depth = 0; $level; $depth++) {
            if ($children === null && ($depth >= 20 || count($ids) > 5000)) {
                throw ValidationException::withMessages(['parent_id' => __('faq::messages.editor.invalid_parent')]);
            }
            $descendants = $children === null
                ? self::query()->whereIn('parent_id', $level)->pluck('id')
                : collect($level)->flatMap(fn (int $parent) => $children->get($parent, collect())->pluck('id'));
            $level = $descendants
                ->map(fn ($value) => (int) $value)
                ->diff($ids)
                ->values()
                ->all();
            $ids = array_merge($ids, $level);
        }

        return $ids;
    }
}

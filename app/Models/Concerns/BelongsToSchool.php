<?php

namespace App\Models\Concerns;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::creating(function ($model) {
            if (empty($model->school_id) && app()->bound('currentSchool')) {
                $model->school_id = app('currentSchool')->id;
            }
        });

        static::addGlobalScope('school', function (Builder $builder) {
            if (app()->bound('currentSchool')) {
                $builder->where(
                    $builder->getModel()->getTable() . '.school_id',
                    app('currentSchool')->id
                );
            }
        });
    }

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query
            ->withoutGlobalScope('school')
            ->where($this->getTable() . '.school_id', $schoolId);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
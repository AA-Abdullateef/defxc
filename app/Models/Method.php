<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Method extends Model
{
    use HasUuid;

    protected $fillable = ['name'];

    public function subMethods(): HasMany
    {
        return $this->hasMany(SubMethod::class);
    }
}
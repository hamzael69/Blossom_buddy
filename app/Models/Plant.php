<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $fillable = [
        'common_name',
        'watering_general_benchmark',
    ];

    protected $casts = [
        'watering_general_benchmark' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_plant');
    }
}

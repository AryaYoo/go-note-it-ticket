<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyAnalysis extends Model
{
    protected $fillable = [
        'title',
        'month',
        'year',
        'content',
        'raw_data',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStatistic extends Model
{
    protected $table = 'product_statistics';
    protected $primaryKey = 'statistic_id';

    protected $fillable = [
        'product_id',
        'views',
        'likes',
        'downloads',
        'shares',
    ];
}

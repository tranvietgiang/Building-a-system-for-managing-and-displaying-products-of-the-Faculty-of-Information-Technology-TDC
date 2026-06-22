<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGraphic extends Model
{
    //
    protected $table = 'product_graphic';
    protected $primaryKey = 'product_graphic_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'product_id',
        'design_type',
        'tools_used',
        'color_palette',
        'behance_link',
    ];

    protected function casts(): array
    {
        return [
            'color_palette' => 'array',
        ];
    }
}

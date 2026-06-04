<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable;

    protected $table = 'products';
    protected $primaryKey = 'product_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'status',
        'user_id',
        'major_id',
        'cate_id',
        'approved_by',
        'awards',
        'github_link',
        'demo_link',
        'approved_at',
        'submitted_at'
    ];

    public function toSearchableArray(): array
    {
        // Scout database driver only searches real columns on the products table.
        return [
            'product_id' => $this->product_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'major_id' => $this->major_id,
            'cate_id' => $this->cate_id,
        ];
    }
}

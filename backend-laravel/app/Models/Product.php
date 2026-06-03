<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        $major = DB::table('majors')
            ->where('major_id', $this->major_id)
            ->select('major_name', 'major_code')
            ->first();

        $categoryName = DB::table('categories')
            ->where('cate_id', $this->cate_id)
            ->value('category_name');

        $tags = DB::table('product_tags')
            ->where('product_id', $this->product_id)
            ->pluck('tag_name')
            ->implode(' ');

        return [
            'product_id' => $this->product_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'major_id' => $this->major_id,
            'major_name' => $major->major_name ?? '',
            'major_code' => $major->major_code ?? '',
            'cate_id' => $this->cate_id,
            'category_name' => $categoryName ?? '',
            'tags' => $tags,
        ];
    }
}

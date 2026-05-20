<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;

class TeacherRepository extends BaseRepository
{
    // trả về kết quả sau khi đếm được dựa vào id người dùng
    public function productStatistic(): ?int
    {
        $idUser = $this->getCurrentUserId();
        return Product::where('approved_by', $idUser)->count();
    }

    // trả về kết quả mà teacher từ chối
    public function rejectedStatistic(): ?int
    {
        $idUser = $this->getCurrentUserId();
        return Product::where('approved_by', $idUser)
            ->where('status', 'rejected')
            ->count();
    }
}

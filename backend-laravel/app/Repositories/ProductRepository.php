<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Traits\HasCurrentUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Repositories\MajorRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\User;

class ProductRepository extends BaseRepository
{

    public function __construct(protected MajorRepository $majorRepository) {}


    // kiểm tra sản phẩm có tồn tại bằng id
    public function productExists(int $productId): bool
    {
        return Product::where("product_id", $productId)->exists();
    }

    public function findProductById(int $productId): ?array
    {
        $majorCode = strtolower($this->majorRepository->getMajorCodeByProductId($productId));
        $userId = $this->getCurrentUserId();

        $product = DB::table('products as p')
            ->join('categories as c', 'p.cate_id', '=', 'c.cate_id')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('users as approved_user', 'p.approved_by', '=', 'approved_user.user_id')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.user_id')

            // Join bảng detail theo major
            ->leftJoin('product_ai as ai', 'p.product_id', '=', 'ai.product_id')
            ->leftJoin('product_cntt as it', 'p.product_id', '=', 'it.product_id')
            ->leftJoin('product_mmt as nw', 'p.product_id', '=', 'nw.product_id')
            ->leftJoin('product_graphic as gr', 'p.product_id', '=', 'gr.product_id')

            ->select(
                'p.product_id',
                'p.title',
                'p.description',
                'p.team_members',
                'p.thumbnail',
                'p.status',
                'p.awards',
                'p.github_link',
                'p.demo_link',
                'p.video_url',
                'p.submitted_at',
                'p.approved_at',
                'p.created_at',
                'p.updated_at',
                'p.user_id',
                'u.name as fullname',

                'p.major_id',
                'm.major_name',
                'm.major_code',

                'p.cate_id',
                'c.category_name',

                'p.approved_by',
                'p.advisor_name',
                'approved_user.name as approved_by_fullname',
                'approved_user.email as approved_by_email',
                'approved_user.role as approved_by_role',

                // AI
                'ai.model_used',
                'ai.framework',
                'ai.language',
                'ai.dataset_used',
                'ai.accuracy_score',

                // CNTT
                'it.programming_language',
                'it.framework as it_framework',
                'it.database_used',

                // MMT
                'nw.simulation_tool',
                'nw.network_protocol',
                'nw.topology_type',
                'nw.config_file',

                // TKDH
                'gr.design_type',
                'gr.tools_used',
                'gr.color_palette',
                'gr.behance_link',
            )
            ->where('p.user_id', $userId)
            ->where('p.product_id', $productId)
            ->first();

        if (!$product) {
            return null;
        }

        $images = DB::table('product_images')
            ->where('product_id', $productId)
            ->select('product_image_id', 'image_url', 'created_at')
            ->get();

        $tags = DB::table('product_tags')
            ->where('product_id', $productId)
            ->select('product_tag_id', 'tag_name')
            ->get();

        $reviews = DB::table('reviews')
            ->leftJoin('users as teacher', 'reviews.teacher_id', '=', 'teacher.user_id')
            ->where('reviews.product_id', $productId)
            ->select(
                'reviews.review_id',
                'reviews.teacher_id',
                'reviews.comment',
                'reviews.created_at',
                'teacher.user_id as teacher_user_id',
                'teacher.name as teacher_fullname',
                'teacher.email as teacher_email',
                'teacher.role as teacher_role'
            )
            ->get();

        $statistics = DB::table('product_statistics')
            ->where('product_id', $productId)
            ->select('views', 'likes', 'downloads', 'shares')
            ->first();

        $result = [
            'product_id' => $product->product_id,
            'title' => $product->title,
            'description' => $product->description,
            'team_members' => is_string($product->team_members)
                ? (json_decode($product->team_members, true) ?: [])
                : ($product->team_members ?? []),
            'thumbnail' => $product->thumbnail,
            'status' => $product->status,
            'awards' => $product->awards,
            'github_link' => $product->github_link,
            'demo_link' => $product->demo_link,
            'video_url' => $product->video_url,

            'submitted_at' => $product->submitted_at,
            'approved_at' => $product->approved_at,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,

            'user_id' => $product->user_id,
            'fullname' => $product->fullname,
            'advisor_name' => $product->advisor_name,

            'major' => [
                'major_id' => $product->major_id,
                'major_name' => $product->major_name,
                'major_code' => $product->major_code,
            ],

            'category' => [
                'cate_id' => $product->cate_id,
                'name' => $product->category_name,
            ],

            'approved_by_user' => [
                'user_id' => $product->approved_by,
                'fullname' => $product->approved_by_fullname,
                'email' => $product->approved_by_email,
                'role' => $product->approved_by_role,
            ],

            'images' => $images,
            'tags' => $tags,

            'reviews' => $reviews->map(function ($review) {
                return [
                    'review_id' => $review->review_id,
                    'teacher_id' => $review->teacher_id,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'teacher' => [
                        'user_id' => $review->teacher_user_id,
                        'fullname' => $review->teacher_fullname,
                        'email' => $review->teacher_email,
                        'role' => $review->teacher_role,
                    ],
                ];
            }),

            'activity_logs' => [
                'views' => $statistics->views ?? 0,
                'likes' => $statistics->likes ?? 0,
                'downloads' => $statistics->downloads ?? 0,
                'shares' => $statistics->shares ?? 0,
            ],
        ];

        switch ($majorCode) {
            case 'ai':
                $result['ai_detail'] = [
                    'model_used' => $product->model_used,
                    'framework' => $product->framework,
                    'language' => $product->language,
                    'dataset_used' => $product->dataset_used,
                    'accuracy_score' => $product->accuracy_score,
                ];
                break;

            case 'cntt':
                $result['it_detail'] = [
                    'programming_language' => $product->programming_language,
                    'framework' => $product->it_framework,
                    'database_used' => $product->database_used,
                ];
                break;

            case 'mmt':
                $result['network_detail'] = [
                    'simulation_tool' => $product->simulation_tool,
                    'network_protocol' => $product->network_protocol,
                    'topology_type' => $product->topology_type,
                    'config_file' => $product->config_file,
                ];
                break;

            case 'tkdh':
                $result['graphic_detail'] = [
                    'design_type' => $product->design_type,
                    'tools_used' => $product->tools_used,
                    'color_palette' => $this->decodeColorPalette($product->color_palette),
                    'behance_link' => $product->behance_link,
                ];
                break;
        }

        return $result;
    }

    // lấy tất cả sản phẩm của sinh viên theo id
    public function productAllById(int $perPage = 50, ?string $status = null): LengthAwarePaginator
    {
        $userId = $this->getCurrentUserId();

        $query = Product::query()
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->where('products.user_id', $userId);

        if ($status) {
            $query->where('products.status', $status);
        }

        return $query
            ->select(
                'products.product_id',
                'products.title',
                'products.thumbnail',
                'products.description',
                'products.status',
                'products.cate_id',
                'categories.category_name',
                'products.submitted_at',
                DB::raw('COALESCE(product_statistics.views, 0) as views'),
                DB::raw('COALESCE(product_statistics.likes, 0) as likes'),

                DB::raw('(SELECT comment FROM reviews 
                WHERE reviews.product_id = products.product_id 
                ORDER BY reviews.created_at DESC 
                LIMIT 1) as feedback')
            )
            ->orderByDesc('products.approved_at')
            ->orderByDesc('products.created_at')
            ->paginate($perPage);
    }

    public function productStatusCountsByCurrentUser(): array
    {
        $userId = $this->getCurrentUserId();

        $counts = Product::query()
            ->where('user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'approved' => (int) ($counts['approved'] ?? 0),
            'pending' => (int) ($counts['pending'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    public function deleteProductStudent(int $productId): bool
    {
        $userId = $this->getCurrentUserId();

        return DB::transaction(function () use ($productId, $userId) {
            $product = Product::query()
                ->where('product_id', $productId)
                ->where('user_id', $userId)
                ->first();

            if (!$product) {
                return false;
            }

            return (bool) $product->delete();
        });
    }

    public function teacherAllData(): Collection
    {
        $idUser = $this->getCurrentUserId();

        $idMajor = User::query()
            ->join('majors', 'users.major_id', '=', 'majors.major_id')
            ->where('users.user_id', $idUser)
            ->value('majors.major_id');

        return Product::query()
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('users', 'products.user_id', '=', 'users.user_id')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->where('products.major_id', $idMajor)
            ->select(
                'products.product_id',
                'products.title',
                'products.description',
                'products.thumbnail',
                'products.github_link',
                'products.demo_link',
                'products.video_url',
                'products.status',
                'products.user_id',
                'products.major_id',
                'products.approved_by',
                'products.approved_at',
                'products.created_at',
                'products.updated_at',
                'majors.major_name',
                'majors.major_code',
                'categories.category_name',
                'categories.description as category_description',
                'users.name as student_fullname',
                'users.email as student_email',
                'users.class as student_class',
            )
            ->orderBy('products.approved_at', 'desc')
            ->get();
    }

    private function currentTeacherMajorId(): ?int
    {
        $idUser = $this->getCurrentUserId();

        return User::query()
            ->join('majors', 'users.major_id', '=', 'majors.major_id')
            ->where('users.user_id', $idUser)
            ->value('majors.major_id');
    }

    private function teacherProductBaseQuery()
    {
        return Product::query()
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('users', 'products.user_id', '=', 'users.user_id')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->where('products.major_id', $this->currentTeacherMajorId());
    }

    public function teacherProductsByStatus(string $status, int $perPage = 6): LengthAwarePaginator
    {
        return $this->teacherProductBaseQuery()
            ->where('products.status', $status)
            ->select(
                'products.product_id',
                'products.title',
                'products.description',
                'products.thumbnail',
                'products.github_link',
                'products.demo_link',
                'products.video_url',
                'products.status',
                'products.user_id',
                'products.major_id',
                'products.approved_by',
                'products.approved_at',
                'products.created_at',
                'products.updated_at',
                'majors.major_name',
                'majors.major_code',
                'categories.category_name',
                'categories.description as category_description',
                'users.name as student_fullname',
                'users.email as student_email',
                'users.class as student_class',
            )
            ->orderByDesc('products.approved_at')
            ->orderByDesc('products.created_at')
            ->paginate($perPage);
    }

    public function teacherStatusCounts(): array
    {
        $counts = Product::query()
            ->where('major_id', $this->currentTeacherMajorId())
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    public function productViewIdTeacher(int $productId, ?object $user = null)
    {
        $role = $user->role ?? null;
        $majorId = $user->major_id ?? null;

        if ($role === 'teacher' && !$majorId) {
            return null;
        }

        $query = DB::table('products')
            ->join('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->join('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('users as approved_user', 'products.approved_by', '=', 'approved_user.user_id')
            ->leftJoin('users as student', 'products.user_id', '=', 'student.user_id')

            ->select(
                'products.*',
                'majors.major_name',
                'majors.major_code',
                'categories.category_name',
                'categories.description as category_description',
                'approved_user.name as approved_by_fullname',
                'student.name as student_name',
                'student.email as student_email',
                'student.role as student_role',
                'student.class as student_class',
                'student.user_id as student_id',
            )

            ->where('products.product_id', $productId)
            ->where('student.role', 'student')
            ->whereIn('products.status', ['pending', 'rejected', 'approved']);

        if ($role === 'teacher') {
            $query->where('products.major_id', $majorId);
        }

        $product = $query->first();

        if (!$product) {
            return null;
        }

        $images = DB::table('product_images')
            ->where('product_id', $productId)
            ->get();

        $tags = DB::table('product_tags')
            ->where('product_id', $productId)
            ->select('product_tag_id', 'tag_name')
            ->get();

        $reviews = DB::table('reviews')
            ->leftJoin('users as teacher', 'reviews.teacher_id', '=', 'teacher.user_id')
            ->where('reviews.product_id', $productId)
            ->select(
                'reviews.review_id',
                'reviews.comment',
                'reviews.created_at',
                'teacher.name as teacher_name'
            )
            ->get();

        $product->team_members = is_string($product->team_members)
            ? (json_decode($product->team_members, true) ?: [])
            : ($product->team_members ?? []);

        $major = mb_strtolower(trim(($product->major_code ?? '').' '.($product->major_name ?? '')), 'UTF-8');
        $isGraphic = str_contains($major, 'tkdh')
            || str_contains($major, 'graphic')
            || str_contains($major, 'đồ họa')
            || str_contains($major, 'thiết kế');

        $graphicDetail = null;

        if ($isGraphic) {
            $graphic = DB::table('product_graphic')
                ->where('product_id', $productId)
                ->first();

            $graphicDetail = [
                'design_type' => $graphic->design_type ?? null,
                'tools_used' => $graphic->tools_used ?? null,
                'behance_link' => $graphic->behance_link ?? null,
                'color_palette' => $this->decodeColorPalette($graphic->color_palette ?? null),
            ];
        }

        $author = [
            'name'  => $product->student_name,
            'email' => $product->student_email,
            'role'  => $product->student_role,
            'class' => $product->student_class,
            'mssv'  => $product->student_id,
        ];

        unset(
            $product->student_name,
            $product->student_email,
            $product->student_role,
            $product->student_class,
            $product->student_id,
        );

        return [
            'product' => $product,
            'author'  => $author,
            'images'  => $images,
            'tags'    => $tags,
            'reviews' => $reviews,
            'graphic_detail' => $graphicDetail,
        ];
    }

    // Duyệt sản phẩm
    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    public function findByIdPAndIdMajor($productId, $majorId): ?Product
    {
        return Product::where('product_id', $productId)
            ->where('major_id', $majorId)
            ->first();
    }

    public function getProductsVisitorPaginated(int $perPage = 9, ?int $majorId = null, string $sortBy = 'newest'): LengthAwarePaginator
    {
        $query = DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('product_statistics as s', 'p.product_id', '=', 's.product_id')
            ->leftJoin('categories as c', 'p.cate_id', '=', 'c.cate_id')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.status', 'approved');

        if ($majorId) {
            $query->where('p.major_id', $majorId);
        }

        match ($sortBy) {
            'most_viewed' => $query->orderByDesc(DB::raw('COALESCE(s.views, 0)'))->orderByDesc('p.created_at'),
            'most_liked' => $query->orderByDesc(DB::raw('COALESCE(s.likes, 0)'))->orderByDesc('p.created_at'),
            default => $query->orderByDesc('p.created_at'),
        };

        $paginator = $query
            ->select(
                'p.product_id as id',
                'p.title',
                'p.description',
                'p.thumbnail',
                'p.video_url',
                'p.created_at',
                'p.cate_id',
                'm.major_id',
                'm.major_name as major',
                'u.name as student',
                'u.user_id as studentId',
                'p.advisor_name as advisor',
                'c.category_name as type',
                's.views',
                's.likes',
                's.shares'
            )
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'cate_id' => $p->cate_id,
            'description' => $p->description,
            'thumbnail' => $p->thumbnail,
            'video_url' => $p->video_url,
            'year' => $p->created_at ? date('Y', strtotime($p->created_at)) : null,
            'student' => $p->student ?? 'Ẩn danh',
            'studentId' => $p->studentId ?? null,
            'major_id' => $p->major_id,
            'major' => $p->major,
            'type' => $p->type ?? null,
            'views' => (int) ($p->views ?? 0),
            'likes' => (int) ($p->likes ?? 0),
            'shares' => (int) ($p->shares ?? 0),
            'advisor' => $p->advisor ?? null,
        ]);

        return $paginator;
    }

    public function getVisitorStats(): array
    {
        $productStats = DB::table('products as p')
            ->leftJoin('product_statistics as s', 'p.product_id', '=', 's.product_id')
            ->where('p.status', 'approved')
            ->selectRaw('COUNT(DISTINCT p.product_id) as products_count')
            ->selectRaw('COALESCE(SUM(s.views), 0) as views_count')
            ->first();

        $userStats = DB::table('users')
            ->selectRaw("SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students_count")
            ->selectRaw("SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as advisors_count")
            ->first();

        return [
            'products_count' => (int) ($productStats->products_count ?? 0),
            'students_count' => (int) ($userStats->students_count ?? 0),
            'advisors_count' => (int) ($userStats->advisors_count ?? 0),
            'views_count' => (int) ($productStats->views_count ?? 0),
        ];
    }

    public function getProductsVisitor(): array
    {
        return DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('product_statistics as s', 'p.product_id', '=', 's.product_id')
            ->leftJoin('categories as c', 'p.cate_id', '=', 'c.cate_id')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.status', 'approved')
            ->orderByDesc('p.created_at')
            ->select(
                'p.product_id as id',
                'p.title',
                'p.description',
                'p.thumbnail',
                'p.video_url',
                'p.created_at',
                'p.cate_id',

                'm.major_id',
                'm.major_name as major',

                'u.name as student',
                'u.user_id as studentId',

                'p.advisor_name as advisor',

                'c.category_name as type',

                's.views',
                's.likes',
                's.shares'
            )
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'cate_id' => $p->cate_id,
                'description' => $p->description,
                'thumbnail' => $p->thumbnail,
                'video_url' => $p->video_url,

                'year' => $p->created_at
                    ? date('Y', strtotime($p->created_at))
                    : null,

                //sv
                'student' => $p->student ?? 'Ẩn danh',
                'studentId' => $p->studentId ?? null,

                'major_id' => $p->major_id,
                'major' => $p->major,

                'type' => $p->type ?? null,

                'views' => (int) ($p->views ?? 0),
                'likes' => (int) ($p->likes ?? 0),
                'shares' => (int) ($p->shares ?? 0),

                //gv duyệt
                'advisor' => $p->advisor ?? null,
            ])
            ->toArray();
    }

    public function getVisitorProductById($id): array
    {
        // ================= 1. GET PRODUCT =================
        $product = DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('product_statistics as s', 'p.product_id', '=', 's.product_id')
            ->leftJoin('categories as c', 'p.cate_id', '=', 'c.cate_id')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.product_id', $id)
            ->where('p.status', 'approved')
            ->select(
                'p.*',
                'm.major_name as major',
                'm.major_code',
                'u.name as student',
                'u.user_id as studentId',
                'p.advisor_name as advisor',
                'c.category_name as type',
                's.views',
                's.likes',
                's.shares'
            )
            ->first();

        if (!$product) return [];

        $productId = $product->product_id;

        // ================= 2. IMAGES =================
        $images = DB::table('product_images')
            ->where('product_id', $productId)
            ->select('product_image_id', 'image_url')
            ->get()
            ->map(fn($i) => [
                'product_image_id' => $i->product_image_id,
                'image_url' => $i->image_url,
            ])
            ->toArray();

        // ================= 3. TAGS =================
        $tags = DB::table('product_tags')
            ->where('product_id', $productId)
            ->pluck('tag_name')
            ->toArray();

        // ================= 4. REVIEWS =================
        $reviews = DB::table('reviews')
            ->leftJoin('users as teacher', 'reviews.teacher_id', '=', 'teacher.user_id')
            ->where('product_id', $productId)
            ->select(
                'reviews.comment',
                'reviews.teacher_id',
                'reviews.created_at',
                'teacher.name as teacher_name'
            )
            ->get()
            ->toArray();

        // ================= 4. DETECT MAJOR =================
        $major = strtolower(trim($product->major ?? ''));

        $isAI = str_contains($major, 'ai')
            || str_contains($major, 'trí tuệ')
            || str_contains($major, 'artificial');

        $isCNTT = str_contains($major, 'cntt')
            || str_contains($major, 'công nghệ thông tin');

        $isMMT = str_contains($major, 'mmt')
            || str_contains($major, 'mạng')
            || str_contains($major, 'network');

        $isGRAPHIC = str_contains($major, 'tkdh')
            || str_contains($major, 'graphic')
            || str_contains($major, 'thiết kế');

        // ================= 5. GET MAJOR DETAIL =================
        $detail = null;

        if ($isCNTT) {
            $detail = DB::table('product_cntt')
                ->where('product_id', $productId)
                ->first();
        }

        if ($isAI) {
            $detail = DB::table('product_ai')
                ->where('product_id', $productId)
                ->first();
        }

        if ($isMMT) {
            $detail = DB::table('product_mmt')
                ->where('product_id', $productId)
                ->first();
        }

        if ($isGRAPHIC) {
            $detail = DB::table('product_graphic')
                ->where('product_id', $productId)
                ->first();

            if ($detail) {
                $detail->color_palette = $this->decodeColorPalette($detail->color_palette ?? null);
            }
        }

        // ================= 6. TECHNOLOGIES =================
        $technologies = [];

        if ($isCNTT && $detail) {
            $technologies = array_values(array_filter([
                $detail->programming_language ?? null,
                $detail->framework ?? null,
                $detail->database_used ?? null,
            ]));
        }

        if ($isAI && $detail) {
            $technologies = array_values(array_filter([
                $detail->model_used ?? null,
                $detail->framework ?? null,
                $detail->language ?? null,
                $detail->dataset_used ?? null,
            ]));
        }

        if ($isMMT && $detail) {
            $technologies = array_values(array_filter([
                $detail->simulation_tool ?? null,
                $detail->network_protocol ?? null,
                $detail->topology_type ?? null,
            ]));
        }

        if ($isGRAPHIC && $detail) {
            $technologies = array_values(array_filter([
                $detail->design_type ?? null,
                $detail->tools_used ?? null,
            ]));
        }

        // ================= 7. RESOURCES =================
        $resources = [
            'github' => $product->github_link ?? null,
            'demo' => $product->demo_link ?? null,
            'video' => $product->video_url ?? null,
            'behance' => $detail->behance_link ?? null,
            'config_file' => $detail->config_file ?? null,
        ];

        // ================= 8. RETURN =================
        return [
            'id' => $product->product_id,
            'title' => $product->title,
            'description' => $product->description,
            'thumbnail' => $product->thumbnail,
            'video_url' => $product->video_url ?? null,

            'images' => $images,

            'year' => $product->created_at
                ? date('Y', strtotime($product->created_at))
                : null,

            'approved_at' => $product->approved_at
                ? date('Y-m-d H:i:s', strtotime($product->approved_at))
                : null,

            'student' => $product->student ?? 'Ẩn danh',
            'studentId' => $product->studentId,

            'team_members' => is_string($product->team_members)
                ? (json_decode($product->team_members, true) ?: [])
                : ($product->team_members ?? []),

            'major_id' => $product->major_id,
            'major' => $product->major,
            'major_code' => $product->major_code,

            'type' => $product->type,

            'views' => (int) ($product->views ?? 0),
            'likes' => (int) ($product->likes ?? 0),
            'shares' => (int) ($product->shares ?? 0),

            'advisor' => $product->advisor,

            'technologies' => $technologies,

            'resources' => $resources,

            'awards' => $product->awards
                ? json_decode($product->awards, true)
                : [],

            'feedback' => array_map(fn($review) => [
                'comment' => $review->comment,
                'teacher_id' => $review->teacher_id,
                'teacher_name' => $review->teacher_name,
                'created_at' => $review->created_at,
            ], $reviews),

            'tags' => $tags,

            'major_detail' => $detail,
        ];
    }

    public function incrementView(int $productId): array
    {
        if (!DB::table('product_statistics')->where('product_id', $productId)->exists()) {
            DB::table('product_statistics')->insert([
                'product_id' => $productId,
                'views' => 0,
                'likes' => 0,
                'downloads' => 0,
                'shares' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('product_statistics')
            ->where('product_id', $productId)
            ->increment('views');

        $statistics = DB::table('product_statistics')
            ->where('product_id', $productId)
            ->select('views', 'likes', 'shares')
            ->first();

        return [
            'views' => (int) ($statistics->views ?? 0),
            'likes' => (int) ($statistics->likes ?? 0),
            'shares' => (int) ($statistics->shares ?? 0),
        ];
    }

    public function incrementLike(int $productId): array
    {
        if (!DB::table('product_statistics')->where('product_id', $productId)->exists()) {
            DB::table('product_statistics')->insert([
                'product_id' => $productId,
                'views' => 0,
                'likes' => 0,
                'downloads' => 0,
                'shares' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('product_statistics')
            ->where('product_id', $productId)
            ->increment('likes');

        $statistics = DB::table('product_statistics')
            ->where('product_id', $productId)
            ->select('views', 'likes', 'shares')
            ->first();

        return [
            'views' => (int) ($statistics->views ?? 0),
            'likes' => (int) ($statistics->likes ?? 0),
            'shares' => (int) ($statistics->shares ?? 0),
        ];
    }

    public function incrementShare(int $productId): array
    {
        if (!DB::table('product_statistics')->where('product_id', $productId)->exists()) {
            DB::table('product_statistics')->insert([
                'product_id' => $productId,
                'views' => 0,
                'likes' => 0,
                'downloads' => 0,
                'shares' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('product_statistics')
            ->where('product_id', $productId)
            ->increment('shares');

        $statistics = DB::table('product_statistics')
            ->where('product_id', $productId)
            ->select('views', 'likes', 'shares')
            ->first();

        return [
            'views' => (int) ($statistics->views ?? 0),
            'likes' => (int) ($statistics->likes ?? 0),
            'shares' => (int) ($statistics->shares ?? 0),
        ];
    }

    // Find matching AI products based on model_used, framework, and language
    public function findMatchingAiProducts(int $productId): array
    {
        $current = DB::table('products as p')
            ->leftJoin('product_ai as ai', 'p.product_id', '=', 'ai.product_id')
            ->leftJoin('product_cntt as cntt', 'p.product_id', '=', 'cntt.product_id')
            ->leftJoin('product_mmt as mmt', 'p.product_id', '=', 'mmt.product_id')
            ->leftJoin('product_graphic as gr', 'p.product_id', '=', 'gr.product_id')
            ->where('p.product_id', $productId)
            ->select(
                'p.*',
                'ai.model_used',
                'ai.framework as ai_framework',
                'ai.language',
                'ai.dataset_used',
                'cntt.programming_language',
                'cntt.framework as cntt_framework',
                'cntt.database_used',
                'mmt.simulation_tool',
                'mmt.network_protocol',
                'mmt.topology_type',
                'mmt.config_file',
                'gr.design_type',
                'gr.tools_used',
                'gr.behance_link'
            )
            ->first();

        if (!$current) {
            return [];
        }

        return DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('product_ai as ai', 'p.product_id', '=', 'ai.product_id')
            ->leftJoin('product_cntt as cntt', 'p.product_id', '=', 'cntt.product_id')
            ->leftJoin('product_mmt as mmt', 'p.product_id', '=', 'mmt.product_id')
            ->leftJoin('product_graphic as gr', 'p.product_id', '=', 'gr.product_id')
            ->where('p.product_id', '!=', $productId)
            ->where('p.major_id', $current->major_id)
            ->whereIn('p.status', ['approved', 'pending'])
            ->select(
                'p.product_id',
                'p.title',
                'p.description',
                'p.thumbnail',
                'p.status',
                'p.created_at',
                'p.approved_at',
                'u.name as fullname',
                'm.major_name',

                'ai.model_used',
                'ai.framework',
                'ai.language',
                'ai.dataset_used',
                'ai.accuracy_score',

                'cntt.programming_language',
                'cntt.framework as cntt_framework',
                'cntt.database_used',

                'mmt.simulation_tool',
                'mmt.network_protocol',
                'mmt.topology_type',
                'mmt.config_file',

                'gr.design_type',
                'gr.tools_used',
                'gr.behance_link'
            )
            ->orderByDesc('p.created_at')
            ->limit(50)
            ->get()
            ->filter(fn($product) => $this->isComparisonCandidate($current, $product))
            ->unique('product_id')
            ->take(10)
            ->values()
            ->map(fn($p) => [
                'product_id' => $p->product_id,
                'title' => $p->title,
                'description' => $p->description,
                'thumbnail' => $p->thumbnail,
                'status' => $p->status,
                'created_at' => $p->created_at,
                'approved_at' => $p->approved_at,
                'fullname' => $p->fullname,
                'major_name' => $p->major_name,

                'model_used' => $p->model_used,
                'framework' => $p->framework ?? $p->cntt_framework,
                'language' => $p->language,
                'dataset_used' => $p->dataset_used,
                'accuracy_score' => $p->accuracy_score,

                'programming_language' => $p->programming_language,
                'database_used' => $p->database_used,

                'simulation_tool' => $p->simulation_tool,
                'network_protocol' => $p->network_protocol,
                'topology_type' => $p->topology_type,
                'config_file' => $p->config_file,

                'design_type' => $p->design_type,
                'tools_used' => $p->tools_used,
                'behance_link' => $p->behance_link,
            ])
            ->toArray();
    }

    private function isComparisonCandidate(object $current, object $candidate): bool
    {
        $pairs = [
            [$current->title ?? null, $candidate->title ?? null, 55],
            [$current->description ?? null, $candidate->description ?? null, 70],
            [$current->thumbnail ?? null, $candidate->thumbnail ?? null, 100],
            [$current->github_link ?? null, $candidate->github_link ?? null, 100],
            [$current->demo_link ?? null, $candidate->demo_link ?? null, 100],
            [$current->model_used ?? null, $candidate->model_used ?? null, 70],
            [$current->ai_framework ?? null, $candidate->framework ?? null, 70],
            [$current->language ?? null, $candidate->language ?? null, 80],
            [$current->dataset_used ?? null, $candidate->dataset_used ?? null, 70],
            [$current->programming_language ?? null, $candidate->programming_language ?? null, 80],
            [$current->cntt_framework ?? null, $candidate->cntt_framework ?? null, 70],
            [$current->database_used ?? null, $candidate->database_used ?? null, 80],
            [$current->simulation_tool ?? null, $candidate->simulation_tool ?? null, 70],
            [$current->network_protocol ?? null, $candidate->network_protocol ?? null, 65],
            [$current->topology_type ?? null, $candidate->topology_type ?? null, 65],
            [$current->config_file ?? null, $candidate->config_file ?? null, 100],
            [$current->design_type ?? null, $candidate->design_type ?? null, 70],
            [$current->tools_used ?? null, $candidate->tools_used ?? null, 65],
            [$current->behance_link ?? null, $candidate->behance_link ?? null, 100],
        ];

        foreach ($pairs as [$first, $second, $minimumSimilarity]) {
            $first = $this->normalizeCandidateValue($first);
            $second = $this->normalizeCandidateValue($second);

            if ($first === '' || $second === '') {
                continue;
            }

            if ($first === $second) {
                return true;
            }

            similar_text($first, $second, $similarity);

            if ($similarity >= $minimumSimilarity) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCandidateValue(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function decodeColorPalette(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? array_values(array_filter($decoded))
            : [];
    }

    public function compareData(int $productId): ?object
    {
        return DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('product_ai as ai', 'p.product_id', '=', 'ai.product_id')
            ->leftJoin('product_cntt as cntt', 'p.product_id', '=', 'cntt.product_id')
            ->leftJoin('product_mmt as mmt', 'p.product_id', '=', 'mmt.product_id')
            ->leftJoin('product_graphic as gr', 'p.product_id', '=', 'gr.product_id')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.user_id')
            ->where('p.product_id', $productId)
            ->select(
                'p.product_id',
                'p.title',
                'p.description',
                'p.thumbnail',
                'p.status',
                'p.created_at',
                'p.approved_at',
                'u.name as fullname',
                'm.major_name',
                'ai.model_used',
                DB::raw('COALESCE(ai.framework, cntt.framework) as framework'),
                'ai.language',
                'ai.dataset_used',
                'ai.accuracy_score',
                'cntt.programming_language',
                'cntt.database_used',
                'mmt.simulation_tool',
                'mmt.network_protocol',
                'mmt.topology_type',
                'mmt.config_file',
                'gr.design_type',
                'gr.tools_used',
                'gr.behance_link'
            )
            ->first();
    }
}

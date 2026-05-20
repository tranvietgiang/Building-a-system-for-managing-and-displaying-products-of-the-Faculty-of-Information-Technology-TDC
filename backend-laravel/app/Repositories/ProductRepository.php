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
                'p.thumbnail',
                'p.status',
                'p.awards',
                'p.github_link',
                'p.demo_link',
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
                'gr.drive_link',
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

        $files = DB::table('product_files')
            ->where('product_id', $productId)
            ->select('product_file_id', 'file_url', 'file_type', 'created_at')
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
            'thumbnail' => $product->thumbnail,
            'status' => $product->status,
            'awards' => $product->awards,
            'github_link' => $product->github_link,
            'demo_link' => $product->demo_link,

            'submitted_at' => $product->submitted_at,
            'approved_at' => $product->approved_at,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,

            'user_id' => $product->user_id,
            'fullname' => $product->fullname,

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
            'files' => $files,
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
                    'drive_link' => $product->drive_link,
                    'behance_link' => $product->behance_link,
                ];
                break;
        }

        return $result;
    }

    // lấy tất cả sản phẩm của sinh viên theo id
    public function productAllById(int $perPage = 50): LengthAwarePaginator
    {
        $userId = $this->getCurrentUserId();

        return Product::query()
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->where('products.user_id', $userId)
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
            ->paginate($perPage);
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

        $files = DB::table('product_files')
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
            'files'   => $files,
            'tags'    => $tags,
            'reviews' => $reviews,
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

    public function getProductsVisitor(): array
    {
        return DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('product_statistics as s', 'p.product_id', '=', 's.product_id')
            ->leftJoin('categories as c', 'p.cate_id', '=', 'c.cate_id')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('users as gv', 'p.approved_by', '=', 'gv.user_id')
            ->where('p.status', 'approved')
            ->orderByDesc('p.created_at')
            ->select(
                'p.product_id as id',
                'p.title',
                'p.description',
                'p.thumbnail',
                'p.created_at',
                'p.cate_id',

                'm.major_id',
                'm.major_name as major',

                'u.name as student',
                'u.user_id as studentId',

                'gv.name as advisor',

                'c.category_name as type',

                's.views',
                's.likes'
            )
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'cate_id' => $p->cate_id,
                'description' => $p->description,
                'thumbnail' => $p->thumbnail,

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
            ->leftJoin('users as gv', 'p.approved_by', '=', 'gv.user_id')
            ->where('p.product_id', $id)
            ->where('p.status', 'approved')
            ->select(
                'p.*',
                'm.major_name as major',
                'm.major_code',
                'u.name as student',
                'u.user_id as studentId',
                'gv.name as advisor',
                'c.category_name as type',
                's.views',
                's.likes'
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
            ->where('product_id', $productId)
            ->select('comment', 'teacher_id')
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
            'drive' => $detail->drive_link ?? null,
            'behance' => $detail->behance_link ?? null,
            'config_file' => $detail->config_file ?? null,
        ];

        // ================= 8. RETURN =================
        return [
            'id' => $product->product_id,
            'title' => $product->title,
            'description' => $product->description,
            'thumbnail' => $product->thumbnail,

            'images' => $images,

            'year' => $product->created_at
                ? date('Y', strtotime($product->created_at))
                : null,

            'approved_at' => $product->approved_at
                ? date('Y-m-d H:i:s', strtotime($product->approved_at))
                : null,

            'student' => $product->student ?? 'Ẩn danh',
            'studentId' => $product->studentId,

            'major_id' => $product->major_id,
            'major' => $product->major,
            'major_code' => $product->major_code,

            'type' => $product->type,

            'views' => (int) ($product->views ?? 0),
            'likes' => (int) ($product->likes ?? 0),

            'advisor' => $product->advisor,

            'technologies' => $technologies,

            'resources' => $resources,

            'awards' => $product->awards
                ? json_decode($product->awards, true)
                : [],

            'feedback' => array_map(fn($r) => $r->comment, $reviews),

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
            ->select('views', 'likes')
            ->first();

        return [
            'views' => (int) ($statistics->views ?? 0),
            'likes' => (int) ($statistics->likes ?? 0),
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
            ->select('views', 'likes')
            ->first();

        return [
            'views' => (int) ($statistics->views ?? 0),
            'likes' => (int) ($statistics->likes ?? 0),
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
                'gr.drive_link',
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
            ->where(function ($q) use ($current) {
                $q->where('p.title', $current->title)
                    ->orWhere('p.description', $current->description)
                    ->orWhere('p.thumbnail', $current->thumbnail)
                    ->orWhere('p.github_link', $current->github_link)
                    ->orWhere('p.demo_link', $current->demo_link)

                    // AI
                    ->orWhere('ai.model_used', $current->model_used)
                    ->orWhere('ai.framework', $current->ai_framework)
                    ->orWhere('ai.language', $current->language)
                    ->orWhere('ai.dataset_used', $current->dataset_used)

                    // CNTT
                    ->orWhere('cntt.programming_language', $current->programming_language)
                    ->orWhere('cntt.framework', $current->cntt_framework)
                    ->orWhere('cntt.database_used', $current->database_used)

                    // MMT
                    ->orWhere('mmt.simulation_tool', $current->simulation_tool)
                    ->orWhere('mmt.network_protocol', $current->network_protocol)
                    ->orWhere('mmt.topology_type', $current->topology_type)
                    ->orWhere('mmt.config_file', $current->config_file)

                    // Graphic
                    ->orWhere('gr.design_type', $current->design_type)
                    ->orWhere('gr.tools_used', $current->tools_used)
                    ->orWhere('gr.drive_link', $current->drive_link)
                    ->orWhere('gr.behance_link', $current->behance_link);
            })
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
                'gr.drive_link',
                'gr.behance_link'
            )
            ->orderByDesc('p.created_at')
            ->limit(10)
            ->get()
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
                'drive_link' => $p->drive_link,
                'behance_link' => $p->behance_link,
            ])
            ->toArray();
    }

    public function compareData(int $productId): ?object
    {
        return DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('product_ai as ai', 'p.product_id', '=', 'ai.product_id')
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
                'ai.framework',
                'ai.language',
                'ai.dataset_used',
                'ai.accuracy_score'
            )
            ->first();
    }
}

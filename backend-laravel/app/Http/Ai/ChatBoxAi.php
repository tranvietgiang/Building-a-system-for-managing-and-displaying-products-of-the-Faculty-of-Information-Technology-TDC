<?php

namespace App\Http\Ai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Common\NormalizeMajorCode;
use Illuminate\Support\Facades\Auth;

class ChatBoxAi
{
    public function __construct(protected NormalizeMajorCode $normalizeMajorCode) {}

    private function isRelevantQuestion(string $message): bool
    {
        $msg = mb_strtolower($message, 'UTF-8');

        $keywords = [
            // ── Đồ án / Sản phẩm ─────────────────────────────────────
            'đồ án',
            'sản phẩm',
            'tài liệu',
            'project',
            'bài làm',
            'upload',
            'tải lên',
            'nộp',
            'bài tập',
            'tiểu luận',
            'báo cáo',
            'nghiên cứu',
            'đề tài',
            'khóa luận',

            // ── Ngành học chung ───────────────────────────────────────
            'ngành',
            'major',
            'chuyên ngành',
            'khoa',

            // ── CNTT ─────────────────────────────────────────────────
            'cntt',
            'công nghệ thông tin',
            'information technology',
            'lập trình',
            'phần mềm',
            'software',
            'web',
            'website',
            'mobile',
            'ứng dụng',
            'android',
            'ios',
            'frontend',
            'backend',
            'fullstack',
            'api',
            'restful',
            'database',
            'cơ sở dữ liệu',
            'sql',
            'mysql',
            'mongodb',
            'postgresql',
            'laravel',
            'nodejs',
            'reactjs',
            'vuejs',
            'angular',
            'spring',
            'django',
            'flask',
            'php',
            'python',
            'java',
            'javascript',
            'typescript',
            'c#',
            'dotnet',
            'docker',
            'kubernetes',
            'devops',
            'git',
            'github',
            'microservice',
            'cloud',
            'aws',
            'azure',
            'firebase',

            // ── AI ───────────────────────────────────────────────────
            'trí tuệ nhân tạo',
            'artificial intelligence',
            'machine learning',
            'học máy',
            'deep learning',
            'học sâu',
            'neural network',
            'mạng nơ ron',
            'nlp',
            'xử lý ngôn ngữ',
            'computer vision',
            'thị giác máy tính',
            'image recognition',
            'object detection',
            'classification',
            'phân loại',
            'regression',
            'clustering',
            'reinforcement learning',
            'chatbot',
            'robot',
            'tự động hóa',
            'automation',
            'tensorflow',
            'pytorch',
            'keras',
            'scikit',
            'opencv',
            'dataset',
            'dữ liệu',
            'training',
            'huấn luyện',
            'accuracy',
            'độ chính xác',
            'prediction',
            'dự đoán',
            'yolo',
            'bert',
            'gpt',
            'transformer',
            'llm',

            // ── MMT ──────────────────────────────────────────────────
            'mmt',
            'mạng máy tính',
            'network',
            'mạng',
            'an ninh mạng',
            'bảo mật',
            'cybersecurity',
            'security',
            'firewall',
            'vpn',
            'proxy',
            'dns',
            'dhcp',
            'tcp',
            'udp',
            'ftp',
            'ssh',
            'protocol',
            'giao thức',
            'topology',
            'mô hình mạng',
            'router',
            'switch',
            'cisco',
            'packet tracer',
            'wireshark',
            'iot',
            'internet of things',
            'vạn vật kết nối',
            'cloud computing',
            'điện toán đám mây',
            'simulation',
            'mô phỏng',
            'gns3',
            'vmware',
            'penetration testing',
            'ethical hacking',
            'pentest',
            'encryption',
            'mã hóa',
            'ssl',
            'tls',

            // ── Đồ họa ───────────────────────────────────────────────
            'graphic',
            'đồ họa',
            'thiết kế',
            'design',
            'thiết kế đồ họa',
            'ui',
            'ux',
            'ui/ux',
            'hình ảnh',
            'poster',
            'banner',
            'logo',
            'branding',
            'motion',
            'motion graphic',
            'animation',
            'hoạt hình',
            'illustration',
            'minh họa',
            'typography',
            'font',
            'photoshop',
            'illustrator',
            'figma',
            'canva',
            'indesign',
            'after effects',
            'premiere',
            'xd',
            'behance',
            'dribbble',
            'mockup',
            'wireframe',
            'màu sắc',
            'layout',
            'bố cục',
            'in ấn',
            'packaging',
            'bao bì',
            'social media',
            'quảng cáo',
            'marketing',

            // ── Danh mục / Thống kê ───────────────────────────────────
            'danh mục',
            'category',
            'loại',
            'phân loại',
            'thống kê',
            'bao nhiêu',
            'tổng',
            'số lượng',
            'xem nhiều',
            'lượt xem',
            'lượt thích',
            'like',
            'view',
            'top',
            'nổi bật',
            'phổ biến',
            'trending',

            // ── Người dùng ────────────────────────────────────────────
            'người dùng',
            'tài khoản',
            'giảng viên',
            'sinh viên',
            'teacher',
            'student',
            'user',
            'thầy',
            'cô',
            'giáo viên',

            // ── Review ────────────────────────────────────────────────
            'đánh giá',
            'nhận xét',
            'review',
            'comment',
            'phản hồi',
            'feedback',
            'duyệt',
            'approve',
            'pending',

            // ── Tìm kiếm ─────────────────────────────────────────────
            'tìm',
            'search',
            'danh sách',
            'liệt kê',
            'cho tôi biết',
            'cho mình biết',
            'hiển thị',
            'show',
            'xem',
            'tra cứu',
            'tìm kiếm',

            // ── Tags ──────────────────────────────────────────────────
            'tag',
            'nhãn',
            'từ khóa',
            'keyword',

            // ── Hệ thống ─────────────────────────────────────────────
            'hệ thống',
            'hoạt động',
            'log',
            'lịch sử',
            'activity',
            'demo',
            'link',
            'github',
            'source code',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($msg, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function chat(Request $request)
    {
        // Validate input
        $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        $user    = $this->resolveUser($request);
        $role    = $user->role ?? 'guest';
        $majorId = $user->major_id ?? null;

        /* ── 1. PARSE MESSAGE ────────────────────────────────────── */
        $message = $request->input('message');

        if (is_array($message)) {
            $message = $message['text'] ?? implode(' ', $message);
        }
        if (is_object($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        $message = trim((string) $message);

        if ($message === '') {
            return response()->json(['reply' => 'Vui lòng nhập câu hỏi.'], 422);
        }

        // Check minimum length
        if (strlen($message) < 3) {
            return response()->json(['reply' => 'Câu hỏi phải ít nhất 3 ký tự.'], 422);
        }

        // Check maximum length
        if (strlen($message) > 1000) {
            return response()->json(['reply' => 'Câu hỏi không được vượt quá 1000 ký tự.'], 422);
        }

        if (!$this->isRelevantQuestion($message)) {
            $userName = $user ? ($user->name ?? $user->username ?? null) : null;
            $nameTag  = $userName ? " {$userName}" : "";

            $replies = [
                "Xin lỗi{$nameTag}, mình chỉ hỗ trợ các câu hỏi liên quan đến hệ thống quản lý đồ án. Bạn có thể hỏi về sản phẩm, ngành học, danh mục, thống kê, v.v.",
                "Câu hỏi này nằm ngoài phạm vi hỗ trợ của mình rồi 😅",
                "Chào{$nameTag}! Tôi có thể giúp gì cho bạn hôm nay? Bạn quan tâm đến đồ án hay tài liệu học thuật nào không?",
                "Xin chào{$nameTag}! Mình là trợ lý hệ thống đồ án. Bạn cần tìm kiếm tài liệu hay xem thống kê gì không?",
            ];

            return response()->json(['reply' => $replies[array_rand($replies)]]);
        }

        /* ── 2. OVERRIDE MAJOR TỪ TEXT ───────────────────────────── */
        if (in_array($role, ['student', 'teacher'], true) && !$majorId) {
            return response()->json([
                'reply' => 'Tài khoản của bạn chưa được gán ngành học nên chưa thể tra cứu dữ liệu theo ngành.',
                'products' => [],
            ], 403);
        }

        $majorCode = $this->normalizeMajorCode->NormalizeMajorCode($message);
        if ($majorCode) {
            $detectedMajorId = $this->resolveMajorIdFromCode($majorCode);

            // Student chỉ được hỏi đúng ngành của mình
            if ($role === 'student' && $detectedMajorId && $detectedMajorId != $majorId) {
                $userName  = $user->name ?? $user->username ?? 'bạn';
                $majorName = DB::table('majors')->where('major_id', $majorId)->value('major_name') ?? 'ngành của bạn';

                return response()->json([
                    'reply' => "Xin lỗi {$userName}, bạn chỉ có thể xem thông tin trong phạm vi {$majorName} thôi nhé 😊"
                ]);
            }

            // Teacher chỉ được hỏi đúng ngành của mình
            if ($role === 'teacher' && $detectedMajorId && $detectedMajorId != $majorId) {
                $userName  = $user->name ?? $user->username ?? 'thầy/cô';
                $majorName = DB::table('majors')->where('major_id', $majorId)->value('major_name') ?? 'ngành của bạn';

                return response()->json([
                    'reply' => "Xin lỗi thầy/cô {$userName}, thầy/cô chỉ có thể xem thông tin trong phạm vi {$majorName} thôi nhé 📝"
                ]);
            }

            //  Admin được xem tất cả, override major bình thường
            if ($role === 'admin') {
                $majorId = $detectedMajorId;
            }

            // Guest không override major
        }

        /* ── 3. BUILD CONTEXT THEO ROLE ──────────────────────────── */
        $data = match ($role) {
            'admin'   => $this->buildAdminContext(),
            'teacher' => $this->buildMajorContext($majorId, $role),
            'student' => $this->buildMajorContext($majorId, $role),
            default   => $this->buildGuestContext(), // guest
        };

        if ($role === 'teacher') {
            $data = array_merge($data, $this->buildTeacherContext($majorId));
        }

        if (isset($data['__error'])) {
            return response()->json(['reply' => $data['__error']], 403);
        }

        /* ── 4. CALL OPENAI ──────────────────────────────────────── */
        $systemPrompt = $this->buildSystemPrompt($role, $data, $user);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/responses', [
            'model' => 'gpt-4.1-mini',
            'input' => [
                [
                    'role'    => 'system',
                    'content' => [['type' => 'input_text', 'text' => $systemPrompt]],
                ],
                [
                    'role'    => 'user',
                    'content' => [['type' => 'input_text', 'text' => $message]],
                ],
            ],
        ]);

        /* ── 5. PARSE RESPONSE ───────────────────────────────────── */
        if ($response->failed()) {
            return response()->json(['reply' => 'Không thể kết nối AI lúc này, vui lòng thử lại.'], 502);
        }

        $result = $response->json();
        $reply  = data_get($result, 'output.0.content.0.text')
            ?? data_get($result, 'output_text')
            ?? 'AI không trả về dữ liệu.';

        $mentionedProducts = $this->extractMentionedProducts($reply, $majorId, $role);

        return response()->json([
            'reply'    => $reply,
            'products' => $mentionedProducts,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  CONTEXT BUILDERS
     * ═══════════════════════════════════════════════════════════════ */

    private function resolveUser(Request $request): ?object
    {
        return Auth::guard('sanctum')->user()
            ?? $request->user()
            ?? Auth::user();
    }

    private function resolveMajorIdFromCode(?string $majorCode): ?int
    {
        if (!$majorCode) {
            return null;
        }

        $normalizedCode = strtoupper(trim($majorCode));
        $aliases = match ($normalizedCode) {
            'TKDH', 'GRAPHIC', 'GRAPHICS' => ['TKDH', 'GRAPHIC', 'GRAPHICS'],
            'CNTT', 'IT' => ['CNTT', 'IT'],
            'MMT', 'NETWORK' => ['MMT', 'NETWORK'],
            'AI' => ['AI'],
            default => [$normalizedCode],
        };

        return DB::table('majors')
            ->whereIn(DB::raw('UPPER(major_code)'), $aliases)
            ->value('major_id');
    }

    private function buildTeacherContext(?int $majorId): array
    {
        if (!$majorId) {
            return ['__error' => 'Bạn chưa được gán ngành học.'];
        }

        $recentReviews = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.product_id')
            ->where('products.major_id', $majorId)
            ->select('reviews.review_id', 'products.title as product_title', 'reviews.comment', 'reviews.created_at')
            ->latest('reviews.created_at')
            ->get();

        return ['recent_reviews_by_teacher_context' => $recentReviews];
    }

    private function buildAdminContext(): array
    {
        $overview = [
            'total_products'   => DB::table('products')->count(),
            'total_categories' => DB::table('categories')->count(),
            'total_majors'     => DB::table('majors')->count(),
            'total_users'      => DB::table('users')->count(),
            'total_reviews'    => DB::table('reviews')->count(),
            'total_files'      => DB::table('product_files')->count(),
            'total_images'     => DB::table('product_images')->count(),
        ];

        $productsByMajor = DB::table('majors as m')
            ->leftJoin('products as p', 'p.major_id', '=', 'm.major_id')
            ->selectRaw('m.major_name, m.major_code, COUNT(p.product_id) as total')
            ->groupBy('m.major_id', 'm.major_name', 'm.major_code')
            ->orderByDesc('total')
            ->get();

        $topViewedProducts = DB::table('products as p')
            ->leftJoin('product_statistics as ps', 'ps.product_id', '=', 'p.product_id')
            ->select(
                'p.product_id',
                'p.title',
                'p.github_link',
                'p.demo_link',
                DB::raw('COALESCE(ps.views, 0) as views'),
                DB::raw('COALESCE(ps.likes, 0) as likes')
            )
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $recentReviewsAll = DB::table('reviews as r')
            ->join('products as p', 'r.product_id', '=', 'p.product_id')
            ->leftJoin('users as u', 'r.teacher_id', '=', 'u.user_id')
            ->select('p.title as product_title', 'u.name as teacher_name', 'r.comment', 'r.created_at')
            ->latest('r.created_at')
            ->limit(10)
            ->get();

        $statsByMajor = DB::table('majors as m')
            ->leftJoin('products as p', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('product_statistics as ps', 'ps.product_id', '=', 'p.product_id')
            ->selectRaw('m.major_name, COALESCE(SUM(ps.views), 0) as total_views, COALESCE(SUM(ps.likes), 0) as total_likes')
            ->groupBy('m.major_id', 'm.major_name')
            ->orderByDesc('total_views')
            ->get();

        $popularTags = DB::table('product_tags')
            ->selectRaw('tag_name, COUNT(*) as total')
            ->groupBy('tag_name')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $categoriesWithCount = DB::table('categories as c')
            ->leftJoin('products as p', 'p.cate_id', '=', 'c.cate_id')
            ->selectRaw('c.category_name, COUNT(p.product_id) as product_count')
            ->groupBy('c.cate_id', 'c.category_name')
            ->orderByDesc('product_count')
            ->get();

        $specializedCounts = [
            'AI'      => DB::table('product_ai')->count(),
            'CNTT'    => DB::table('product_cntt')->count(),
            'Graphic' => DB::table('product_graphic')->count(),
            'MMT'     => DB::table('product_mmt')->count(),
        ];

        $recentActivities = DB::table('activity_logs as al')
            ->leftJoin('users as u', 'al.user_id', '=', 'u.user_id')
            ->select('al.user_id', 'u.name as user_name', 'al.action', 'al.description', 'al.created_at')
            ->latest('al.created_at')
            ->limit(10)
            ->get();

        return compact(
            'overview',
            'productsByMajor',
            'topViewedProducts',
            'recentReviewsAll',
            'statsByMajor',
            'popularTags',
            'categoriesWithCount',
            'specializedCounts',
            'recentActivities'
        );
    }

    private function buildMajorContext(?int $majorId, string $role): array
    {
        if (!$majorId) {
            return ['__error' => 'Bạn chưa được gán ngành học.'];
        }

        $major = DB::table('majors')->where('major_id', $majorId)->first();
        if (!$major) {
            return ['__error' => 'Ngành học không tồn tại.'];
        }

        $products = DB::table('products')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->where('products.major_id', $majorId)
            ->where('products.status', 'approved') // ✅ Chỉ approved
            ->select(
                'products.product_id',
                'products.title',
                'products.status',
                'products.github_link',
                'products.demo_link',
                'categories.category_name',
                'products.submitted_at',
                'product_statistics.views',
                'product_statistics.likes'
            )
            ->latest('products.submitted_at')
            ->get();

        $topStats = DB::table('product_statistics')
            ->join('products', 'product_statistics.product_id', '=', 'products.product_id')
            ->where('products.major_id', $majorId)
            ->where('products.status', 'approved') //
            ->select('products.title', 'product_statistics.views', 'product_statistics.likes')
            ->orderByDesc('product_statistics.views')
            ->limit(10)
            ->get();

        $popularTags = DB::table('product_tags')
            ->join('products', 'product_tags.product_id', '=', 'products.product_id')
            ->where('products.major_id', $majorId)
            ->where('products.status', 'approved') // 
            ->selectRaw('product_tags.tag_name, COUNT(*) as total')
            ->groupBy('product_tags.tag_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $recentReviews = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.product_id')
            ->join('users', 'reviews.teacher_id', '=', 'users.user_id')
            ->where('products.major_id', $majorId)
            ->select('products.title as product_title', 'users.name as teacher_name', 'reviews.comment', 'reviews.created_at')
            ->latest('reviews.created_at')
            ->limit(10)
            ->get();

        $categories = DB::table('categories')
            ->join('products', 'categories.cate_id', '=', 'products.cate_id')
            ->where('products.major_id', $majorId)
            ->where('products.status', 'approved') // 
            ->selectRaw('categories.category_name, COUNT(*) as total')
            ->groupBy('categories.cate_id', 'categories.category_name')
            ->orderByDesc('total')
            ->get();

        $specializedData = $this->buildSpecializedData($majorId, $major->major_code);

        return [
            'major_name'     => $major->major_name,
            'major_code'     => $major->major_code,
            'role'           => $role,
            'product_count'  => $products->count(),
            'products'       => $products,
            'top_stats'      => $topStats,
            'popular_tags'   => $popularTags,
            'recent_reviews' => $recentReviews,
            'categories'     => $categories,
            'specialized'    => $specializedData,
        ];
    }

    private function buildSpecializedData(int $majorId, string $majorCode): array
    {
        $code = strtoupper($majorCode);

        if (str_contains($code, 'AI')) {
            return DB::table('product_ai')
                ->join('products', 'product_ai.product_id', '=', 'products.product_id')
                ->where('products.major_id', $majorId)
                ->where('products.status', 'approved') // ✅ thêm
                ->select('products.product_id', 'products.title', 'products.github_link', 'products.demo_link', 'product_ai.model_used', 'product_ai.framework', 'product_ai.language', 'product_ai.accuracy_score')
                ->get()->toArray();
        }

        if (str_contains($code, 'CNTT') || str_contains($code, 'IT')) {
            return DB::table('product_cntt')
                ->join('products', 'product_cntt.product_id', '=', 'products.product_id')
                ->where('products.major_id', $majorId)
                ->where('products.status', 'approved') // ✅ thêm
                ->select('products.product_id', 'products.title', 'products.github_link', 'products.demo_link', 'product_cntt.programming_language', 'product_cntt.framework', 'product_cntt.database_used')
                ->get()->toArray();
        }

        if (str_contains($code, 'GR') || str_contains($code, 'GRAPHIC')) {
            return DB::table('product_graphic')
                ->join('products', 'product_graphic.product_id', '=', 'products.product_id')
                ->where('products.major_id', $majorId)
                ->where('products.status', 'approved') // ✅ thêm
                ->select('products.product_id', 'products.title', 'products.demo_link', 'product_graphic.design_type', 'product_graphic.tools_used', 'product_graphic.behance_link')
                ->get()->toArray();
        }

        if (str_contains($code, 'MMT')) {
            return DB::table('product_mmt')
                ->join('products', 'product_mmt.product_id', '=', 'products.product_id')
                ->where('products.major_id', $majorId)
                ->where('products.status', 'approved') // ✅ thêm
                ->select('products.product_id', 'products.title', 'products.github_link', 'products.demo_link', 'product_mmt.simulation_tool', 'product_mmt.network_protocol', 'product_mmt.topology_type')
                ->get()->toArray();
        }

        return [];
    }

    private function buildGuestContext(): array
    {
        $majors     = DB::table('majors')->select('major_name', 'major_code')->get();
        $categories = DB::table('categories')->select('category_name')->get();

        $allProducts = DB::table('products')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->where('products.status', 'approved') // ✅ Chỉ approved
            ->select(
                'products.product_id',
                'products.title',
                'products.github_link',
                'products.demo_link',
                'products.status',
                'majors.major_name',
                'majors.major_code',
                'categories.category_name',
                'product_statistics.views',
                'product_statistics.likes'
            )
            ->orderByDesc('product_statistics.views')
            ->limit(20) // ✅ Giới hạn để không quá nặng
            ->get();

        return [
            'majors'          => $majors,
            'categories'      => $categories,
            'totalProducts'   => DB::table('products')->where('status', 'approved')->count(),
            'totalCategories' => $categories->count(),
            'allProducts'     => $allProducts,
        ];
    }

    private function extractMentionedProducts(string $reply, ?int $majorId, string $role): array
    {
        $query = DB::table('products')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->select('products.product_id as id', 'products.title', 'product_statistics.views')
            ->where('products.status', 'approved'); // ✅ Luôn chỉ lấy approved

        if (in_array($role, ['student', 'teacher']) && $majorId) {
            $query->where('products.major_id', $majorId); // ✅ Filter ngành cho student/teacher
        }
        // guest và admin không filter major → lấy tất cả ngành

        $allProducts = $query->get();

        $mentioned = $allProducts
            ->filter(fn($product) => str_contains($reply, $product->title))
            ->unique('id'); // ✅ Tránh trùng lặp

        if ($mentioned->isEmpty()) {
            return DB::table('products')
                ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
                ->select('products.product_id as id', 'products.title', 'product_statistics.views')
                ->where('products.status', 'approved')
                ->when(
                    in_array($role, ['student', 'teacher']) && $majorId,
                    fn($q) => $q->where('products.major_id', $majorId)
                )
                ->orderByDesc('product_statistics.views')
                ->limit(5)
                ->get()
                ->toArray();
        }

        return $mentioned->values()->toArray();
    }

    /* ═══════════════════════════════════════════════════════════════
     *  SYSTEM PROMPT
     * ═══════════════════════════════════════════════════════════════ */

    private function buildSystemPrompt(string $role, array $data, ?object $user = null): string
    {
        $roleLabel = match ($role) {
            'admin'   => 'Quản trị viên',
            'teacher' => 'Giảng viên',
            'student' => 'Sinh viên',
            default   => 'Khách',
        };

        $dataJson     = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $userName     = $user?->name ?? $user?->username ?? null;
        $userInfoLine = $userName
            ? "- Tên người dùng: {$userName}"
            : "- Người dùng: Khách (chưa đăng nhập)";

        $greetingRule = match ($role) {
            'admin' => $userName
                ? "10. Khi người dùng chào hoặc mở đầu hội thoại, hãy chào: \"Xin chào {$userName}! Bạn cần tra cứu hoặc thống kê gì trong hệ thống không? 📊\""
                : "10. Khi người dùng chào, hãy chào thân thiện và giới thiệu khả năng quản trị hệ thống.",
            'teacher' => $userName
                ? "10. Khi người dùng chào hoặc mở đầu hội thoại, hãy chào: \"Xin chào thầy/cô {$userName}! Thầy/cô cần hỗ trợ gì về đồ án hoặc sinh viên không? 📝\""
                : "10. Khi người dùng chào, hãy chào thân thiện với xưng hô thầy/cô.",
            'student' => $userName
                ? "10. Khi người dùng chào hoặc mở đầu hội thoại, hãy chào: \"Xin chào {$userName}! Mình có thể giúp gì cho bạn? 😊\""
                : "10. Khi người dùng chào, hãy chào thân thiện và hỏi bạn cần hỗ trợ gì.",
            default => "10. Khi người dùng chào, hãy chào thân thiện, giới thiệu bản thân là trợ lý hệ thống đồ án và mời đặt câu hỏi.",
        };

        $scopeRule = match ($role) {
            'admin' =>
            "11. Bạn có thể trả lời mọi thông tin trong toàn bộ hệ thống.\n" .
                "12. Có thể cung cấp thống kê tổng hợp, so sánh giữa các ngành, danh sách toàn bộ đồ án.\n" .
                "13. Có thể tiết lộ các số liệu nhạy cảm như tổng số người dùng, tỷ lệ hoàn thành, v.v.",
            'teacher' =>
            "11. Chỉ cung cấp thông tin liên quan đến ngành giảng viên đang phụ trách.\n" .
                "12. Có thể xem danh sách đồ án, tiến độ trong ngành của mình.\n" .
                "13. Không cung cấp thông tin chi tiết về ngành khác ngoài tên và mô tả cơ bản.",
            'student' =>
            "11. QUAN TRỌNG: Chỉ cung cấp thông tin trong phạm vi ngành học của sinh viên này.\n" .
                "12. Từ chối lịch sự nếu sinh viên hỏi về đồ án hoặc dữ liệu của ngành khác.\n" .
                "13. Không tiết lộ điểm số, tiến độ hay thông tin cá nhân của sinh viên khác.",
            default =>
            "11. Chỉ cung cấp thông tin chung, không bao gồm dữ liệu cá nhân hay nhạy cảm.\n" .
                "12. Có thể giới thiệu các ngành học, danh mục đồ án nổi bật, link github/demo nếu có.\n" .
                "13. Khuyến khích người dùng đăng nhập để xem thông tin chi tiết hơn.",
        };

        $majorScopeRule = in_array($role, ['student', 'teacher'], true)
            ? "\n14. BẮT BUỘC: Chỉ được nhắc tới và trả về sản phẩm thuộc ngành \"{$data['major_name']}\" ({$data['major_code']}). Nếu người dùng hỏi ngành khác, hãy từ chối lịch sự và không gợi ý sản phẩm ngành khác."
            : "";

        return <<<PROMPT
        Bạn là trợ lý thông minh của hệ thống quản lý đồ án / tài liệu học thuật.

        THÔNG TIN NGƯỜI DÙNG:
        - Vai trò: {$roleLabel} ({$role})
        {$userInfoLine}

        QUY TẮC BẮT BUỘC:
        1. Chỉ sử dụng dữ liệu được cung cấp bên dưới, không bịa đặt
        2. Không tiết lộ cấu trúc database, tên bảng, tên cột kỹ thuật
        3. Trả lời bằng tiếng Việt, thân thiện và chính xác
        4. Nếu câu hỏi ngoài phạm vi dữ liệu, hãy nói rõ không có thông tin đó
        5. Khi liệt kê đồ án/tài liệu, trình bày gọn gàng theo danh sách
        6. Với đồ án AI: có thể nêu model, framework, độ chính xác nếu được hỏi
        7. Với đồ án CNTT: có thể nêu ngôn ngữ lập trình, framework, database
        8. Với đồ án Graphic: có thể nêu loại thiết kế, công cụ, link Behance
        9. Với đồ án MMT: có thể nêu công cụ mô phỏng, giao thức, topology
        {$greetingRule}
        {$scopeRule}
        {$majorScopeRule}

        DỮ LIỆU HỆ THỐNG:
        {$dataJson}
        PROMPT;
    }
}

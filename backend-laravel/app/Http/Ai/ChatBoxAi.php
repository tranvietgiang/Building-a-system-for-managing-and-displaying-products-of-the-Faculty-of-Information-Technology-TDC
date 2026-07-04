<?php

namespace App\Http\Ai;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Common\NormalizeMajorCode;
use App\Models\ChatboxTrainingLog;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettingService;
use Throwable;

class ChatBoxAi
{
    public function __construct(
        protected NormalizeMajorCode $normalizeMajorCode,
        protected SystemSettingService $settings
    ) {}

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
            'đồ',
            'đồ án tốt nghiệp',

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
            'hybrid',
            'hybrid cloud',
            'káº¿t ná»‘i hybrid',
            'ket noi hybrid',

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
            'kiểm tra',
            'check',
            'trùng',
            'trùng lặp',
            'duplicate',
            'so sánh',
            'giống nhau',
            'đạo ý tưởng',
            'kiểm duyệt',
            'moderation',
            'ảnh',
            'hình',

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
            'chức năng',
            'hướng dẫn',
            'cách',
            'làm sao',
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
        if (!$this->settings->enabled(SystemSettingService::AI_CHATBOX)) {
            return response()->json([
                'reply' => 'Chatbot AI hiện đang bị quản trị viên tắt.',
                'products' => [],
            ], 503);
        }

        // Validate input
        $request->validate([
            'message' => 'nullable|string|max:1000',
        ], [
            'message.string' => 'Tin nhắn không hợp lệ.',
            'message.max' => 'Tin nhắn không được vượt quá 1000 ký tự.',
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
        if (mb_strlen($message, 'UTF-8') < 3) {
            return response()->json(['reply' => 'Câu hỏi phải ít nhất 3 ký tự.'], 422);
        }

        // Check maximum length
        if (strlen($message) > 1000) {
            return response()->json(['reply' => 'Câu hỏi không được vượt quá 1000 ký tự.'], 422);
        }

        if (!$this->isRelevantQuestion($message)) {
            $payload = [
                'reply' => $this->buildNonRelevantReply($message, $user),
                'products' => [],
                'source' => 'reply_bank',
            ];

            return $this->respondAndTrain($request, $message, $user, $role, $majorId, $payload);
        }

        /* ── 2. OVERRIDE MAJOR TỪ TEXT ───────────────────────────── */
        if (in_array($role, ['student', 'teacher'], true) && !$majorId) {
            $payload = [
                'reply' => 'Tài khoản của bạn chưa được gán ngành học nên chưa thể tra cứu dữ liệu theo ngành.',
                'products' => [],
                'source' => 'scope_guard',
            ];

            return $this->respondAndTrain($request, $message, $user, $role, $majorId, $payload, 403);
        }

        $majorCode = $this->normalizeMajorCode->NormalizeMajorCode($message);
        if ($majorCode) {
            $detectedMajorId = $this->resolveMajorIdFromCode($majorCode);

            // Student chỉ được hỏi đúng ngành của mình
            if ($role === 'student' && $detectedMajorId && $detectedMajorId != $majorId) {
                $userName  = $user->name ?? $user->username ?? 'bạn';
                $majorName = DB::table('majors')->where('major_id', $majorId)->value('major_name') ?? 'ngành của bạn';

                $payload = [
                    'reply' => "Xin lỗi {$userName}, bạn chỉ có thể xem thông tin trong phạm vi {$majorName} thôi nhé 😊",
                    'products' => [],
                    'source' => 'scope_guard',
                ];

                return $this->respondAndTrain($request, $message, $user, $role, $majorId, $payload);
            }

            // Teacher chỉ được hỏi đúng ngành của mình
            if ($role === 'teacher' && $detectedMajorId && $detectedMajorId != $majorId) {
                $userName  = $user->name ?? $user->username ?? 'thầy/cô';
                $majorName = DB::table('majors')->where('major_id', $majorId)->value('major_name') ?? 'ngành của bạn';

                $payload = [
                    'reply' => "Xin lỗi thầy/cô {$userName}, thầy/cô chỉ có thể xem thông tin trong phạm vi {$majorName} thôi nhé 📝",
                    'products' => [],
                    'source' => 'scope_guard',
                ];

                return $this->respondAndTrain($request, $message, $user, $role, $majorId, $payload);
            }

            //  Admin được xem tất cả, override major bình thường
            if ($role === 'admin') {
                $majorId = $detectedMajorId;
            }

            // Guest không override major
        }

        $featureResponse = $this->answerFeatureQuestionIfAny($message, $role, $majorId, $user);
        if ($featureResponse) {
            $this->recordTrainingFromJsonResponse($request, $message, $user, $role, $majorId, $featureResponse);
            return $featureResponse;
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
            $payload = [
                'reply' => $data['__error'],
                'products' => [],
                'source' => 'context_error',
            ];

            return $this->respondAndTrain($request, $message, $user, $role, $majorId, $payload, 403);
        }

        /* ── 4. CALL OPENAI ──────────────────────────────────────── */
        $systemPrompt = $this->buildSystemPrompt($role, $data, $user);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type'  => 'application/json',
            ])->connectTimeout(10)->timeout(30)->post('https://api.openai.com/v1/responses', [
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
        } catch (ConnectionException $exception) {
            Log::warning('AI chatbox OpenAI connection failed', [
                'message' => $exception->getMessage(),
            ]);

            $fallback = $this->openAiFallbackResponse($message, $majorId, $role);
            $this->recordTrainingFromJsonResponse($request, $message, $user, $role, $majorId, $fallback);

            return $fallback;
        } catch (Throwable $exception) {
            Log::error('AI chatbox OpenAI unexpected exception', [
                'message' => $exception->getMessage(),
            ]);

            $fallback = $this->openAiFallbackResponse($message, $majorId, $role);
            $this->recordTrainingFromJsonResponse($request, $message, $user, $role, $majorId, $fallback);

            return $fallback;
        }

        /* ── 5. PARSE RESPONSE ───────────────────────────────────── */
        if ($response->failed()) {
            Log::error('AI chatbox OpenAI request failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $fallback = $this->openAiFallbackResponse($message, $majorId, $role);
            $this->recordTrainingFromJsonResponse($request, $message, $user, $role, $majorId, $fallback);

            return $fallback;
        }

        $result = $response->json();
        $reply  = data_get($result, 'output.0.content.0.text')
            ?? data_get($result, 'output_text')
            ?? 'AI không trả về dữ liệu.';

        $mentionedProducts = $this->extractMentionedProducts($reply, $majorId, $role);

        $payload = [
            'reply'    => $reply,
            'products' => $mentionedProducts,
            'source' => 'openai_context',
        ];

        return $this->respondAndTrain($request, $message, $user, $role, $majorId, $payload);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  CONTEXT BUILDERS
     * ═══════════════════════════════════════════════════════════════ */

    private function respondAndTrain(
        Request $request,
        string $message,
        ?object $user,
        string $role,
        ?int $majorId,
        array $payload,
        int $status = 200
    ): JsonResponse {
        $this->recordChatboxTrainingLog($request, $message, $user, $role, $majorId, $payload);

        return response()->json($payload, $status);
    }

    private function recordTrainingFromJsonResponse(
        Request $request,
        string $message,
        ?object $user,
        string $role,
        ?int $majorId,
        JsonResponse $response
    ): void {
        $payload = json_decode($response->getContent(), true);

        if (!is_array($payload)) {
            $payload = [
                'reply' => $response->getContent(),
                'products' => [],
                'source' => 'unknown_response',
            ];
        }

        $this->recordChatboxTrainingLog($request, $message, $user, $role, $majorId, $payload);
    }

    private function recordChatboxTrainingLog(
        Request $request,
        string $message,
        ?object $user,
        string $role,
        ?int $majorId,
        array $payload
    ): void {
        try {
            $products = $this->compactTrainingProducts($payload['products'] ?? []);
            $rawProducts = $payload['products'] ?? [];
            $productsCount = is_countable($rawProducts) ? count($rawProducts) : count($products);
            $analysis = $this->trainingAnalysisForMessage($message, $payload['analysis'] ?? null);
            $source = $this->limitTrainingString((string) ($payload['source'] ?? 'unknown'), 80);

            ChatboxTrainingLog::create([
                'user_id' => $user->user_id ?? $user->id ?? null,
                'major_id' => $majorId,
                'role' => $this->limitTrainingString($role, 30),
                'message' => $message,
                'normalized_message' => $this->limitTrainingString($this->normalizeSearchText($message), 500),
                'analysis' => $analysis,
                'source' => $source,
                'reply' => $this->limitTrainingString((string) ($payload['reply'] ?? ''), 12000),
                'products' => $products,
                'products_count' => $productsCount,
                'needs_training' => $this->shouldMarkTrainingNeeded($payload, $productsCount),
                'reviewed' => false,
                'ip_address' => $this->limitTrainingString((string) $request->ip(), 45),
                'user_agent' => $this->limitTrainingString((string) $request->userAgent(), 500),
            ]);
        } catch (Throwable $exception) {
            Log::warning('AI chatbox training log failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function trainingAnalysisForMessage(string $message, mixed $analysis): array
    {
        $data = is_array($analysis) ? $analysis : [];

        try {
            $data = array_merge($this->analyzeLocalSearchQuery($message), $data);
        } catch (Throwable) {
            $data['terms'] ??= [];
        }

        $data['features'] ??= $this->detectFeatureIntents($message);
        $data['relevant'] ??= $this->isRelevantQuestion($message);

        return $data;
    }

    private function compactTrainingProducts(mixed $products): array
    {
        if ($products instanceof \Illuminate\Support\Collection) {
            $items = $products->all();
        } elseif ($products instanceof \Traversable) {
            $items = iterator_to_array($products);
        } elseif (is_array($products)) {
            $items = $products;
        } else {
            $items = [];
        }

        return collect($items)
            ->take(5)
            ->map(function ($product) {
                if (is_object($product)) {
                    $product = get_object_vars($product);
                }

                if (!is_array($product)) {
                    return null;
                }

                return array_filter([
                    'id' => $product['id'] ?? $product['product_id'] ?? null,
                    'title' => $product['title'] ?? null,
                    'major_name' => $product['major_name'] ?? null,
                    'major_code' => $product['major_code'] ?? null,
                    'category_name' => $product['category_name'] ?? null,
                ], fn($value) => $value !== null && $value !== '');
            })
            ->filter()
            ->values()
            ->all();
    }

    private function shouldMarkTrainingNeeded(array $payload, int $productsCount): bool
    {
        $source = (string) ($payload['source'] ?? '');

        return $productsCount === 0
            || !empty($payload['ai_unavailable'])
            || in_array($source, [
                'reply_bank',
                'local_search',
                'local_search_scope_guard',
                'local_search_fallback',
                'local_fallback',
                'scope_guard',
                'context_error',
                'unknown_response',
            ], true);
    }

    private function limitTrainingString(?string $value, int $limit): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $limit, 'UTF-8');
    }

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
        $normalizedReply = $this->normalizeSearchText($reply);

        // Nếu câu trả lời không phải đang giới thiệu/tìm thấy sản phẩm
        // thì không gắn thẻ "Đồ án liên quan" bên dưới.
        if (!$this->containsAny($normalizedReply, [
            'tim thay san pham',
            'san pham phu hop',
            'san pham lien quan',
            'do an lien quan',
            'du an lien quan',
            'xem chi tiet',
            'chi tiet san pham',
            'bam vao san pham',
            'bam vao the san pham',
        ])) {
            return [];
        }

        $query = DB::table('products')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->select(
                'products.product_id as id',
                'products.product_id',
                'products.title',
                'product_statistics.views'
            )
            ->where('products.status', 'approved');

        if (in_array($role, ['student', 'teacher'], true) && $majorId) {
            $query->where('products.major_id', $majorId);
        }

        $allProducts = $query->get();

        $mentioned = $allProducts
            ->filter(function ($product) use ($reply, $normalizedReply) {
                $title = trim((string) ($product->title ?? ''));

                if ($title === '') {
                    return false;
                }

                return str_contains($reply, $title)
                    || str_contains($normalizedReply, $this->normalizeSearchText($title));
            })
            ->unique('id')
            ->values();

        // Quan trọng: không tự lấy top sản phẩm nữa.
        // Không nhắc đúng tên sản phẩm thì không trả products.
        if ($mentioned->isEmpty()) {
            return [];
        }

        return $mentioned->toArray();
    }

    private function buildNonRelevantReply(string $message, ?object $user): string
    {
        $category = $this->detectNonRelevantReplyCategory($message);
        $bank = $this->nonRelevantReplyBank();
        $templates = $bank[$category] ?? $bank['off_topic'];
        $userName = $user ? ($user->name ?? $user->username ?? null) : null;
        $nameTag = $userName ? " {$userName}" : '';

        $reply = strtr($this->randomItem($templates), [
            '{name}' => $nameTag,
        ]);

        return $reply . "\n\n" . $this->randomItem($this->suggestionSetsForCategory($category));
    }

    private function detectNonRelevantReplyCategory(string $message): string
    {
        $normalized = $this->normalizeSearchText($message);
        $wordCount = count(preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY));

        if ($wordCount <= 5 && $this->containsAny($normalized, [
            'chao',
            'xin chao',
            'hello',
            'hi',
            'hey',
            'alo',
            'co ai khong',
        ])) {
            return 'greeting';
        }

        if ($this->containsAny($normalized, [
            'lam bai giup',
            'lam do an giup',
            'viet bao cao giup',
            'giai bai',
            'code giup',
            'lam ho',
            'lam thay',
            'copy bai',
            'chep bai',
            'viet het',
        ])) {
            return 'study_help';
        }

        if ($this->containsAny($normalized, [
            'loi',
            'bug',
            'khong dang nhap',
            'mat khau',
            'quen mat khau',
            'khong upload',
            'khong hien',
            'khong vao duoc',
            'bi treo',
            'lag',
            'server',
        ])) {
            return 'technical';
        }

        if ($this->containsAny($normalized, [
            'ban lam duoc gi',
            'ban biet gi',
            'ban la ai',
            'chatbot',
            'tro ly',
            'huong dan toi',
            'toi nen hoi gi',
        ])) {
            return 'capability';
        }

        if ($wordCount <= 4 || $this->containsAny($normalized, [
            'giup',
            'hoi ti',
            'hoi chut',
            'tu van',
            'sao vay',
            'ok',
            'uh',
            'ua',
            'roi sao',
        ])) {
            return 'vague';
        }

        return 'off_topic';
    }

    private function nonRelevantReplyBank(): array
    {
        return [
            'greeting' => [
                'Chào{name}, mình nghe đây. Bạn muốn tìm sản phẩm, xem ngành học, hỏi thống kê hay kiểm tra đồ án?',
                'Chào{name}. Mình là trợ lý hệ thống đồ án, sẵn sàng hỗ trợ tra cứu sản phẩm và thông tin học thuật.',
                'Xin chào{name}. Bạn cứ hỏi về sản phẩm, danh mục, ngành, AI Search, kiểm tra ảnh hoặc so sánh trùng nhé.',
                'Mình đây{name}. Bạn đang cần tìm đồ án nào hay muốn xem sản phẩm theo ngành?',
                'Chào bạn{name}. Hôm nay mình có thể hỗ trợ tìm kiếm sản phẩm, xem thông tin ngành hoặc hướng dẫn kiểm tra sản phẩm.',
                'Xin chào{name}. Nếu bạn có từ khóa như "du lịch", "Laravel", "AI Python" hay "Figma", cứ gửi mình tìm.',
                'Chào{name}. Bạn cần tra cứu đồ án, xem sản phẩm nổi bật hay hỏi cách dùng hệ thống?',
                'Mình sẵn sàng{name}. Bạn muốn bắt đầu bằng tìm kiếm sản phẩm hay hỏi về chức năng nào?',
                'Chào{name}. Gõ tên đề tài, công nghệ hoặc ngành học, mình sẽ cố gắng tìm đúng dữ liệu trong hệ thống.',
                'Xin chào{name}. Mình hỗ trợ tốt nhất khi câu hỏi liên quan đến đồ án, sản phẩm, sinh viên, giảng viên, ngành hoặc thống kê.',
                'Có mình đây{name}. Bạn muốn xem danh sách sản phẩm hay hỏi cách kiểm tra trùng lặp?',
                'Chào{name}. Nếu bạn chưa biết hỏi gì, mình có thể gợi ý vài câu mẫu để tra cứu đồ án.',
                'Xin chào{name}. Bạn cần tìm đồ án theo chủ đề, công nghệ, danh mục hay ngành học?',
                'Mình đang nghe{name}. Hãy gửi một từ khóa sản phẩm hoặc câu hỏi về hệ thống đồ án nhé.',
                'Chào{name}. Mình có thể giúp bạn tra cứu sản phẩm đã duyệt, top lượt xem, tag phổ biến hoặc hướng dẫn upload.',
                'Xin chào{name}. Bạn muốn tìm kiếm nhanh hay cần mình giải thích một chức năng của hệ thống?',
                'Chào{name}. Mình ưu tiên trả lời các câu hỏi về hệ thống quản lý đồ án và sản phẩm học tập.',
                'Mình đây{name}. Cứ hỏi bằng tiếng Việt tự nhiên, ví dụ "tìm sản phẩm du lịch" hoặc "so sánh trùng là gì".',
                'Xin chào{name}. Bạn cần xem sản phẩm, ngành học, danh mục hay lịch sử đánh giá?',
                'Chào{name}. Mình có thể giúp bạn đi từ từ: tìm sản phẩm trước, rồi xem chi tiết hoặc hướng dẫn kiểm tra.',
            ],
            'vague' => [
                'Bạn nói hơi ngắn nên mình chưa đoán chắc bạn cần gì. Cho mình thêm từ khóa về sản phẩm hoặc chức năng nhé.',
                'Mình chưa đủ dữ liệu để trả lời đúng ý bạn. Bạn thử nói rõ hơn: muốn tìm sản phẩm, xem thống kê hay hỏi cách dùng?',
                'Câu này còn mơ hồ quá. Bạn thêm chủ đề đồ án, tên ngành, công nghệ hoặc thao tác cần hướng dẫn giúp mình.',
                'Mình hiểu là bạn cần hỗ trợ, nhưng chưa biết hỗ trợ phần nào trong hệ thống đồ án.',
                'Bạn cho mình thêm một chút ngữ cảnh nhé: sản phẩm nào, ngành nào, hay chức năng nào đang cần xem?',
                'Mình chưa bắt được ý chính. Nếu bạn đang tìm đồ án, hãy gửi từ khóa cụ thể hơn.',
                'Câu này cần cụ thể hơn để mình trả lời chính xác. Bạn có thể hỏi theo dạng "tìm sản phẩm ..." hoặc "cách kiểm tra ...".',
                'Mình chưa biết bạn muốn tra cứu hay hướng dẫn. Bạn nói rõ mục tiêu một chút là mình theo được ngay.',
                'Bạn đang hỏi chung quá nên mình chưa nên bịa câu trả lời. Hãy thêm từ khóa sản phẩm hoặc tên chức năng.',
                'Mình cần thêm chi tiết để tránh trả lời sai. Bạn muốn xem dữ liệu, tìm kiếm hay kiểm tra sản phẩm?',
                'Câu này chưa có đủ dấu hiệu liên quan tới sản phẩm hoặc hệ thống. Bạn thử viết lại cụ thể hơn nhé.',
                'Mình có thể hỗ trợ, nhưng cần biết bạn đang quan tâm tới đồ án nào hoặc thao tác nào.',
                'Bạn gửi thêm ngành học, tên đề tài, công nghệ hoặc trạng thái sản phẩm để mình lọc chính xác hơn.',
                'Mình chưa rõ "cái đó" là phần nào. Bạn nói tên chức năng hoặc sản phẩm giúp mình nhé.',
                'Nếu bạn muốn tìm sản phẩm, chỉ cần gửi chủ đề. Nếu muốn hướng dẫn, hãy nói chức năng bạn đang dùng.',
                'Mình chưa đủ tự tin để trả lời câu này. Bạn thêm từ khóa như ngành, danh mục, tag hoặc tên sản phẩm nha.',
                'Câu hỏi hơi cụt nên mình sẽ xin thêm thông tin thay vì đoán sai.',
                'Bạn nói rõ hơn một nhịp nhé: bạn cần xem danh sách, kiểm tra ảnh, so sánh trùng hay thống kê?',
                'Mình đang thiếu ngữ cảnh. Hãy cho mình biết bạn là đang tìm, xem, sửa, upload hay kiểm tra sản phẩm.',
                'Bạn có thể hỏi lại bằng một câu đầy đủ hơn, mình sẽ trả lời sát hơn nhiều.',
            ],
            'off_topic' => [
                'Câu này chưa liên quan trực tiếp tới hệ thống đồ án, nên mình kéo về đúng phạm vi hỗ trợ nhé.',
                'Mình không có dữ liệu đáng tin cho chủ đề đó trong hệ thống. Nếu hỏi về sản phẩm hoặc đồ án, mình trả lời tốt hơn.',
                'Mình chỉ nên trả lời trong phạm vi quản lý đồ án để tránh đưa thông tin sai.',
                'Chủ đề này nằm ngoài dữ liệu hệ thống. Bạn có thể đổi sang câu hỏi về sản phẩm, ngành học hoặc thống kê.',
                'Mình chưa được thiết kế để tư vấn chủ đề này. Mình mạnh hơn ở tra cứu đồ án và hướng dẫn chức năng hệ thống.',
                'Câu hỏi này không khớp với dữ liệu đồ án hiện có, nên mình chưa thể trả lời chắc chắn.',
                'Mình không muốn đoán bừa ngoài phạm vi hệ thống. Bạn hỏi về sản phẩm học tập hoặc chức năng trong web nhé.',
                'Phần đó không thuộc hệ thống quản lý đồ án. Mình có thể hỗ trợ nếu bạn hỏi về danh mục, tag, ngành hoặc sản phẩm.',
                'Mình chưa có nguồn dữ liệu nội bộ cho câu hỏi này. Hãy thử hỏi theo hướng đồ án hoặc sản phẩm trong hệ thống.',
                'Câu này hơi lệch khỏi nhiệm vụ của mình. Mình có thể giúp tra cứu và giải thích dữ liệu sản phẩm học tập.',
                'Mình không xử lý tốt các câu hỏi đời sống/chung chung. Với đồ án, sản phẩm, AI Search hoặc kiểm tra trùng thì mình làm ổn hơn.',
                'Chủ đề này không nằm trong phạm vi hỗ trợ của chatbot đồ án.',
                'Mình sẽ không trả lời lan man ngoài hệ thống. Bạn có thể hỏi lại bằng một từ khóa sản phẩm.',
                'Câu hỏi hiện tại chưa dùng được dữ liệu trong hệ thống, nên mình chưa thể đưa câu trả lời chính xác.',
                'Nếu bạn đang muốn liên hệ câu này với đồ án, hãy nói rõ sản phẩm hoặc ngành liên quan nhé.',
                'Mình không thấy mối liên hệ với sản phẩm học tập. Bạn thử hỏi về đề tài, công nghệ hoặc danh mục cụ thể.',
                'Mình có thể bị sai nếu trả lời câu này, vì nó không thuộc dữ liệu đồ án đang quản lý.',
                'Mình giữ câu trả lời trong phạm vi học thuật và sản phẩm sinh viên để đảm bảo đúng dữ liệu.',
                'Câu này ngoài phạm vi hệ thống. Mình có thể giúp bạn tìm đồ án tương tự nếu bạn đưa từ khóa.',
                'Mình chưa hỗ trợ chủ đề đó. Bạn có thể hỏi về upload, duyệt sản phẩm, kiểm tra ảnh hoặc so sánh trùng.',
                'Mình không có quyền truy cập hay dữ liệu phù hợp cho câu hỏi này.',
                'Nếu mục tiêu của bạn là tìm tài liệu/sản phẩm liên quan, hãy gửi từ khóa cụ thể hơn.',
                'Câu này không phải dạng tra cứu đồ án. Bạn đổi sang câu hỏi về hệ thống nha.',
                'Mình sẽ ưu tiên trả lời các câu hỏi có thể kiểm chứng từ dữ liệu sản phẩm trong hệ thống.',
                'Phần này chưa nằm trong khả năng của chatbot. Nhưng mình có thể giúp bạn tìm sản phẩm theo chủ đề.',
                'Mình không nên trả lời ngoài dữ liệu được cấp. Hãy hỏi mình về sản phẩm, ngành, danh mục, thống kê hoặc đánh giá.',
                'Câu hỏi này chưa có ngữ cảnh học thuật trong hệ thống. Bạn thêm tên đề tài hoặc công nghệ nhé.',
                'Mình chưa xác định được câu này liên quan đến sản phẩm nào. Nếu có, hãy gửi tên hoặc từ khóa sản phẩm.',
                'Mình không hỗ trợ trò chuyện tự do quá xa hệ thống. Mình sẽ hữu ích hơn khi bạn hỏi về đồ án.',
                'Bạn đang hỏi ngoài phạm vi chatbot đồ án. Mình có thể hướng bạn về cách tìm kiếm hoặc kiểm tra sản phẩm.',
            ],
            'study_help' => [
                'Mình không làm thay bài hoặc đồ án, nhưng có thể giúp bạn tìm sản phẩm tham khảo trong hệ thống.',
                'Mình có thể gợi ý hướng tìm tài liệu và sản phẩm liên quan, còn phần làm bài bạn nên tự triển khai.',
                'Nếu bạn cần ý tưởng, hãy nói ngành và chủ đề; mình sẽ tìm đồ án gần giống để bạn tham khảo đúng cách.',
                'Mình không viết hộ toàn bộ báo cáo, nhưng có thể chỉ bạn xem các sản phẩm liên quan và cấu trúc thông tin có sẵn.',
                'Mình không hỗ trợ sao chép bài. Mình có thể giúp bạn kiểm tra sản phẩm có bị trùng ý tưởng không.',
                'Nếu bạn đang bí đề tài, hãy hỏi "gợi ý sản phẩm ngành CNTT/AI/MMT/Graphic" để mình tra cứu dữ liệu phù hợp.',
                'Mình có thể hỗ trợ học tập theo hướng tham khảo, không làm thay hoặc tạo nội dung gian lận.',
                'Bạn có thể hỏi mình tìm các đồ án cùng chủ đề để lấy cảm hứng, rồi tự phát triển hướng riêng.',
                'Mình sẽ giúp bạn đi đúng hướng: tìm sản phẩm mẫu, xem công nghệ dùng, hoặc kiểm tra trùng lặp.',
                'Mình không làm hộ, nhưng có thể giúp bạn hiểu cách hệ thống đánh giá sản phẩm và hình ảnh upload.',
            ],
            'technical' => [
                'Nếu bạn gặp lỗi trong hệ thống, hãy nói rõ màn hình nào, thao tác nào và thông báo lỗi cụ thể.',
                'Mình có thể hướng dẫn kiểm tra lỗi cơ bản, nhưng cần thêm ngữ cảnh: đăng nhập, upload, tìm kiếm hay xem chi tiết?',
                'Bạn mô tả thêm lỗi xảy ra ở đâu nhé. Ví dụ: upload ảnh, gửi sản phẩm, AI Search hay so sánh trùng.',
                'Câu này có vẻ là lỗi kỹ thuật, nhưng chưa đủ thông tin để chẩn đoán.',
                'Bạn gửi thêm nội dung lỗi, tài khoản vai trò nào và bước thao tác trước khi lỗi xảy ra giúp mình.',
                'Nếu lỗi liên quan upload ảnh, hãy kiểm tra định dạng JPG/PNG/WEBP và dung lượng từng ảnh không quá 5 MB.',
                'Nếu lỗi liên quan AI, có thể là tính năng bị tắt hoặc kết nối OpenAI đang chậm. Bạn thử lại hoặc báo quản trị viên.',
                'Mình cần thông báo lỗi nguyên văn để hướng dẫn chính xác hơn.',
                'Bạn cho mình biết lỗi xuất hiện ở trang sinh viên, giảng viên hay quản trị viên nhé.',
                'Mình chưa thể sửa trực tiếp từ chatbox, nhưng có thể giúp bạn khoanh vùng lỗi nếu có bước tái hiện.',
            ],
            'capability' => [
                'Mình là trợ lý cho hệ thống đồ án, tập trung vào tra cứu sản phẩm và hướng dẫn chức năng.',
                'Mình có thể tìm sản phẩm, giải thích ngành/danh mục, hướng dẫn AI Search, kiểm tra ảnh và so sánh trùng.',
                'Bạn có thể hỏi mình bằng tiếng Việt tự nhiên, miễn là liên quan tới dữ liệu đồ án trong hệ thống.',
                'Mình không phải chatbot trò chuyện tự do; mình được tối ưu cho sản phẩm học tập và quy trình duyệt đồ án.',
                'Mình giúp tốt nhất khi bạn đưa từ khóa sản phẩm, ngành học, công nghệ hoặc thao tác đang cần làm.',
                'Các câu mình xử lý tốt: tìm đồ án, xem top sản phẩm, hỏi tag, hỏi kiểm tra ảnh, hỏi so sánh trùng.',
                'Mình có thể trả lời theo vai trò người dùng: sinh viên, giảng viên hoặc quản trị viên.',
                'Nếu bạn hỏi ngoài phạm vi, mình sẽ gợi ý cách chuyển câu hỏi về dữ liệu đồ án.',
                'Mình có thể chỉ đường trong hệ thống, nhưng không thay thế quy trình duyệt hoặc upload chính thức.',
                'Bạn cứ hỏi cụ thể, mình sẽ ưu tiên trả lời ngắn, rõ và đúng dữ liệu hệ thống.',
            ],
        ];
    }

    private function suggestionSetsForCategory(string $category): array
    {
        $general = [
            "Bạn có thể hỏi thử:\n- Tìm sản phẩm du lịch\n- Đồ án AI dùng Python\n- So sánh trùng là gì?",
            "Một vài câu mình hiểu tốt:\n- Có sản phẩm nào về Laravel không?\n- Kiểm tra hình ảnh sản phẩm như thế nào?\n- Top sản phẩm nhiều lượt xem",
            "Gợi ý cách hỏi:\n- Tìm đồ án web bán hàng\n- Sản phẩm ngành CNTT có gì?\n- Hướng dẫn upload ảnh sản phẩm",
            "Bạn thử viết theo mẫu:\n- Tìm sản phẩm về [chủ đề]\n- Xem đồ án ngành [ngành]\n- Kiểm tra [chức năng] dùng sao?",
            "Nếu đang tìm dữ liệu, hãy đưa từ khóa như:\n- du lịch\n- chatbot\n- Figma\n- mạng máy tính",
        ];

        $sets = [
            'greeting' => [
                "Mình hỗ trợ nhanh các việc:\n- Tìm kiếm sản phẩm\n- Hướng dẫn kiểm tra ảnh\n- Hỏi về so sánh trùng",
                "Bạn có thể bắt đầu bằng:\n- Tìm sản phẩm du lịch\n- Xem top sản phẩm\n- Hỏi cách upload đồ án",
                "Nếu chưa biết hỏi gì, thử:\n- AI Search dùng sao?\n- Sản phẩm Graphic có gì?\n- Kiểm tra trùng lặp ở đâu?",
            ],
            'vague' => [
                "Bạn bổ sung theo mẫu này nha:\n- Tôi muốn tìm sản phẩm về ...\n- Tôi muốn kiểm tra chức năng ...\n- Tôi muốn xem thống kê ...",
                "Để mình trả lời đúng hơn, hãy thêm một trong các ý:\n- Chủ đề sản phẩm\n- Ngành học\n- Công nghệ\n- Chức năng đang dùng",
            ],
            'study_help' => [
                "Mình có thể hỗ trợ đúng cách bằng cách:\n- Tìm sản phẩm tham khảo\n- Gợi ý công nghệ đang có trong hệ thống\n- Kiểm tra trùng ý tưởng",
                "Bạn thử hỏi:\n- Tìm đồ án cùng chủ đề quản lý thư viện\n- Có sản phẩm nào dùng React không?\n- Làm sao để tránh trùng lặp sản phẩm?",
            ],
            'technical' => [
                "Bạn gửi thêm 3 ý này giúp mình:\n- Trang đang thao tác\n- Nội dung lỗi\n- Bạn bấm gì trước khi lỗi xảy ra",
                "Nếu là lỗi AI, thử hỏi cụ thể:\n- AI Search bị lỗi gì?\n- Chatbot không trả sản phẩm\n- So sánh trùng không chạy",
            ],
            'capability' => [
                "Mình làm tốt nhất với:\n- Tìm kiếm sản phẩm\n- Giải thích chức năng hệ thống\n- Hướng dẫn kiểm tra ảnh và so sánh trùng",
                "Bạn cứ hỏi tự nhiên như:\n- Tìm đồ án du lịch\n- Kiểm tra ảnh upload ra sao?\n- Sản phẩm nào xem nhiều nhất?",
            ],
        ];

        return array_merge($sets[$category] ?? [], $general);
    }

    private function randomItem(array $items): string
    {
        return $items[array_rand($items)];
    }

    private function answerFeatureQuestionIfAny(string $message, string $role, ?int $majorId, ?object $user = null)
    {
        $features = $this->detectFeatureIntents($message);

        if (empty($features)) {
            return null;
        }

        if (
            in_array('image_check', $features, true)
            || in_array('compare', $features, true)
            || in_array('technical_support', $features, true)
            || count($features) > 1
        ) {
            return $this->featureGuideResponse($message, $features, $role, $majorId);
        }

        if (
            in_array('search', $features, true)
            && $this->shouldAnswerProductSearchLocally($message)
        ) {
            return $this->localProductSearchResponse($message, $role, $majorId, $user);
        }

        return null;
    }

    private function detectFeatureIntents(string $message): array
    {
        $normalized = $this->normalizeSearchText($message);
        $features = [];

        if ($this->containsAny($normalized, [
            'tim',
            'tim kiem',
            'search',
            'tra cuu',
            'liet ke',
            'danh sach',
            'hien thi',
            'xem',
            'cho tui xem',
            'cho toi xem',
            'cho minh xem',
            'xem san pham',
            'xem do an',
            'loc san pham',
        ])) {
            $features[] = 'search';
        }

        if (!in_array('search', $features, true) && $this->looksLikeProductTopicQuery($message)) {
            $features[] = 'search';
        }

        if ($this->containsAny($normalized, [
            'kiem tra hinh anh',
            'kiem tra anh',
            'check anh',
            'check hinh',
            'kiem duyet anh',
            'kiem duyet hinh',
            'anh san pham',
            'hinh anh san pham',
            'moderation',
            'duyet anh',
            'anh upload',
            'hinh upload',
        ])) {
            $features[] = 'image_check';
        }

        if ($this->containsAny($normalized, [
            'so sanh',
            'kiem tra trung',
            'check trung',
            'trung lap',
            'duplicate',
            'giong nhau',
            'tuong dong',
            'dao y tuong',
            'san pham trung',
            'do an trung',
        ])) {
            $features[] = 'compare';
        }

        if ($this->containsAny($normalized, [
            'bi loi',
            'loi upload',
            'upload loi',
            'khong upload',
            'khong dang nhap',
            'quen mat khau',
            'khong hien',
            'khong vao duoc',
            'server loi',
            'ai loi',
            'chatbot loi',
            'search loi',
            'so sanh loi',
        ])) {
            $features[] = 'technical_support';
        }

        return array_values(array_unique($features));
    }

    private function featureGuideResponse(string $message, array $features, string $role, ?int $majorId)
    {
        $parts = [];
        $products = [];

        if (in_array('search', $features, true)) {
            $parts[] = $this->searchGuideText();
            $products = $this->safeFindLocalProductsForMessage($message, $majorId, $role);
        }

        if (in_array('image_check', $features, true)) {
            $parts[] = $this->imageCheckGuideText();
        }

        if (in_array('compare', $features, true)) {
            $parts[] = $this->compareGuideText($role);
        }

        if (in_array('technical_support', $features, true)) {
            $parts[] = $this->technicalGuideText();
        }

        if (empty($parts)) {
            return null;
        }

        if (!empty($products)) {
            $parts[] = "Mình cũng tìm được vài sản phẩm liên quan:\n" . $this->formatProductList($products);
        }

        return response()->json([
            'reply' => implode("\n\n", $parts),
            'products' => $products,
            'source' => 'feature_guide',
        ]);
    }

    private function localProductSearchResponse(string $message, string $role, ?int $majorId, ?object $user = null)
    {
        $analysis = $this->analyzeLocalSearchQuery($message);

        $products = $this->safeFindLocalProductsForMessage($message, $majorId, $role);

        if (empty($products)) {
            return response()->json([
                'reply' => $this->buildNoLocalProductsReply($analysis),
                'products' => [],
                'source' => 'local_search',
                'analysis' => $analysis,
            ]);
        }

        $aiReply = $this->askOpenAiForRetrievedProducts($message, $role, $analysis, $products, $user);

        if ($aiReply) {
            return response()->json([
                'reply' => $aiReply,
                'products' => $products,
                'source' => 'mysql_rag',
                'analysis' => $analysis,
            ]);
        }

        return response()->json([
            'reply' => "AI đang tạm mất kết nối, nhưng mình vẫn thấy vài sản phẩm liên quan:\n" . $this->formatProductList($products, $role, $analysis),
            'products' => $products,
            'source' => 'local_search_fallback',
            'ai_unavailable' => true,
            'analysis' => $analysis,
        ]);
    }

    private function buildNoLocalProductsReply(array $analysis): string
    {
        $suggestions = $this->noResultSearchSuggestions($analysis);

        if (!empty($suggestions)) {
            return 'Mình chưa thấy sản phẩm này trong danh sách đã duyệt. Bạn thử tìm bằng: '
                . implode(', ', $suggestions)
                . '.';
        }

        return 'Mình chưa thấy sản phẩm này trong danh sách đã duyệt. Bạn thử gõ thêm tên chủ đề, công nghệ hoặc một phần tiêu đề sản phẩm nha.';
    }

    private function noResultSearchSuggestions(array $analysis): array
    {
        $majorCode = strtoupper((string) ($analysis['major_code'] ?? ''));
        $text = $this->normalizeSearchText(implode(' ', $analysis['terms'] ?? []));

        if ($this->containsAny($text, ['hybrid', 'cloud', 'dam may', 'vpn', 'zero trust'])) {
            return ['hybrid cloud', 'điện toán đám mây', 'VPN', 'Zero Trust'];
        }

        if ($this->containsAny($text, ['xam nhap', 'ids', 'intrusion', 'suricata'])) {
            return ['IDS', 'xâm nhập mạng', 'Suricata', 'bảo mật mạng'];
        }

        return match ($majorCode) {
            'AI' => ['AI Python', 'machine learning', 'nhận diện hình ảnh', 'dự đoán dữ liệu'],
            'CNTT' => ['web Laravel', 'React', 'quản lý bán hàng', 'API'],
            'MMT' => ['bảo mật mạng', 'VPN', 'Wi-Fi doanh nghiệp', 'Packet Tracer'],
            'GRAPHIC' => ['thiết kế Figma', 'UI/UX', 'logo', 'poster'],
            default => ['du lịch', 'AI Python', 'web Laravel', 'thiết kế Figma'],
        };
    }

    private function searchGuideText(): string
    {
        return 'Tìm kiếm: bạn có thể hỏi thẳng bằng tiếng Việt như "tìm sản phẩm du lịch", "đồ án AI Python", "web Laravel", "thiết kế Figma". Mình sẽ tìm theo tên, mô tả, ngành, danh mục, tag và công nghệ liên quan.';
    }

    private function imageCheckGuideText(): string
    {
        $status = $this->settings->enabled(SystemSettingService::AI_PRODUCT_CHECK)
            ? 'đang bật'
            : 'đang bị quản trị viên tắt';

        return "Kiểm tra hình ảnh: tính năng này {$status}. Khi sinh viên upload sản phẩm, hệ thống tự kiểm tra ảnh có liên quan tới sản phẩm/ngành không, có phải ảnh giao diện web/app/prototype/thiết kế hợp lệ không, và có nội dung nhạy cảm, bạo lực, spam, meme hay ảnh không phù hợp không. Chatbot hiện chỉ hướng dẫn và tra cứu; việc kiểm tra file ảnh thật diễn ra ở màn hình đăng/chỉnh sửa sản phẩm.";
    }

    private function compareGuideText(string $role): string
    {
        $status = $this->settings->enabled(SystemSettingService::AI_PRODUCT_CHECK)
            ? 'đang bật'
            : 'đang bị quản trị viên tắt';

        if ($role === 'teacher') {
            return "So sánh sản phẩm: tính năng này {$status}. Thầy/cô mở chi tiết sản phẩm cần kiểm tra rồi bấm nút \"So sánh trùng\". Hệ thống sẽ so với các sản phẩm gần giống, hiển thị mức tương đồng, trường bị trùng và phần so sánh hình ảnh/gallery nếu có.";
        }

        if ($role === 'admin') {
            return "So sánh sản phẩm: tính năng này {$status}. Luồng hiện dùng cho giảng viên khi duyệt/kiểm tra chi tiết sản phẩm, qua nút \"So sánh trùng\" để xem mức tương đồng, trường trùng và ảnh/gallery liên quan.";
        }

        return "So sánh sản phẩm: tính năng này {$status} nhưng luồng sử dụng chính dành cho giảng viên khi kiểm tra chi tiết sản phẩm. Nếu sản phẩm của bạn bị báo trùng, hãy đọc phản hồi, chỉnh lại nội dung/ảnh/minh chứng khác biệt rồi gửi lại.";
    }

    private function technicalGuideText(): string
    {
        return "Hỗ trợ lỗi hệ thống: bạn gửi giúp mình trang đang thao tác, nội dung lỗi nguyên văn và bước vừa bấm trước khi lỗi xảy ra. Nếu lỗi liên quan upload, kiểm tra ảnh JPG/PNG/WEBP, mỗi ảnh tối đa 5 MB và tối đa 10 ảnh. Nếu lỗi liên quan AI Search/chatbot/so sánh, có thể tính năng đang bị tắt hoặc kết nối AI đang chậm; thử lại sau hoặc báo quản trị viên.";
    }

    private function askOpenAiForRetrievedProducts(
        string $message,
        string $role,
        array $analysis,
        array $products,
        ?object $user = null
    ): ?string {
        $systemPrompt = $this->buildRetrievedProductsPrompt($role, $analysis, $products, $user);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type'  => 'application/json',
            ])->connectTimeout(10)->timeout(30)->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.text_model', 'gpt-4.1-mini'),
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

            if ($response->failed()) {
                Log::warning('AI chatbox retrieved-products request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $result = $response->json();
            $reply = data_get($result, 'output.0.content.0.text')
                ?? data_get($result, 'output_text');

            $reply = trim((string) $reply);

            return $reply !== '' ? $reply : null;
        } catch (ConnectionException $exception) {
            Log::warning('AI chatbox retrieved-products connection failed', [
                'message' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            Log::error('AI chatbox retrieved-products exception', [
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function buildRetrievedProductsPrompt(string $role, array $analysis, array $products, ?object $user = null): string
    {
        $payload = [
            'user' => [
                'role' => $role,
                'name' => $user?->name ?? $user?->username ?? null,
            ],
            'products' => $this->formatRetrievedProductsForPrompt($products, $role),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
        Bạn là trợ lý tiếng Việt của hệ thống đồ án sinh viên.

        Hãy trả lời dựa duy nhất trên danh sách sản phẩm được cung cấp.

        QUY TẮC BẮT BUỘC:
        1. Trả lời ngắn gọn, tự nhiên, thân thiện.
        2. Không bịa thêm sản phẩm, số liệu hoặc đường dẫn ngoài danh sách.
        3. Không nhắc cách hệ thống xử lý, tìm kiếm, lưu trữ hoặc tên kỹ thuật bên trong.
        4. Nếu có sản phẩm, liệt kê tối đa 3-5 sản phẩm. Mỗi sản phẩm gồm: tên sản phẩm, lý do liên quan ngắn, và link chi tiết nếu có.
        5. Nếu không có link chi tiết, nhắc người dùng bấm vào thẻ sản phẩm bên dưới chat để xem chi tiết.
        6. Không nói "không tìm thấy" khi danh sách sản phẩm đang có dữ liệu.

        DANH SÁCH SẢN PHẨM:
        {$json}
        PROMPT;
    }

    private function formatRetrievedProductsForPrompt(array $products, string $role = 'guest'): array
    {
        return collect($products)
            ->take(5)
            ->map(fn($product) => [
                'id' => $product->id ?? null,
                'title' => $product->title ?? null,
                'description' => $product->description ?? null,
                'major_name' => $product->major_name ?? null,
                'major_code' => $product->major_code ?? null,
                'category_name' => $product->category_name ?? null,
                'detail_url' => $this->productDetailUrl($product, $role),
                'github_link' => $product->github_link ?? null,
                'demo_link' => $product->demo_link ?? null,
                'highlights' => $this->productHighlightValues($product),
            ])
            ->values()
            ->all();
    }

    private function productHighlightValues(object $product): array
    {
        $values = [
            $product->model_used ?? null,
            $product->ai_framework ?? null,
            $product->ai_language ?? null,
            $product->dataset_used ?? null,
            $product->accuracy_score ?? null,
            $product->programming_language ?? null,
            $product->cntt_framework ?? null,
            $product->database_used ?? null,
            $product->network_protocol ?? null,
            $product->topology_type ?? null,
            $product->simulation_tool ?? null,
            $product->design_type ?? null,
            $product->tools_used ?? null,
        ];

        return collect($values)
            ->filter(fn($value) => $value !== null && $value !== '')
            ->flatMap(fn($value) => preg_split('/[,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique(fn($value) => mb_strtolower($value, 'UTF-8'))
            ->take(8)
            ->values()
            ->all();
    }

    private function shouldAnswerProductSearchLocally(string $message): bool
    {
        $normalized = $this->normalizeSearchText($message);

        if ($this->containsAny($normalized, [
            'bao nhieu',
            'thong ke',
            'tong',
            'so luong',
            'ti le',
            'phan tram',
            'report',
            'bao cao',
        ])) {
            return false;
        }

        return $this->looksLikeProductListingRequest($message)
            || !empty($this->extractLocalSearchTerms($message))
            || $this->analyzeLocalSearchQuery($message)['major_code'] !== null;
    }

    private function safeFindLocalProductsForMessage(string $message, ?int $majorId, string $role, int $limit = 5): array
    {
        try {
            return $this->findLocalProductsForMessage($message, $majorId, $role, $limit);
        } catch (Throwable $exception) {
            Log::error('AI chatbox local product search failed', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function formatProductList(array $products, string $role = 'guest', ?array $analysis = null): string
    {
        return collect($products)
            ->take(5)
            ->values()
            ->map(function ($product, $index) use ($role, $analysis) {
                $line = ($index + 1) . '. ' . ($product->title ?? 'Sản phẩm phù hợp')
                    . ' - ' . $this->productRelevanceText($product, $analysis);
                $detailUrl = $this->productDetailUrl($product, $role);

                return $detailUrl
                    ? "{$line} Xem: {$detailUrl}"
                    : "{$line} Bấm thẻ sản phẩm bên dưới để xem chi tiết.";
            })
            ->implode("\n");
    }

    private function productRelevanceText(object $product, ?array $analysis = null): string
    {
        $haystack = $this->normalizeSearchText(implode(' ', array_filter([
            $product->title ?? null,
            $product->description ?? null,
            $product->major_name ?? null,
            $product->major_code ?? null,
            $product->category_name ?? null,
            $product->model_used ?? null,
            $product->ai_framework ?? null,
            $product->ai_language ?? null,
            $product->dataset_used ?? null,
            $product->programming_language ?? null,
            $product->cntt_framework ?? null,
            $product->database_used ?? null,
            $product->network_protocol ?? null,
            $product->topology_type ?? null,
            $product->simulation_tool ?? null,
            $product->design_type ?? null,
            $product->tools_used ?? null,
        ])));

        if ($this->containsAny($haystack, ['hybrid', 'cloud', 'ipsec', 'bgp', 'vpn', 'zero trust'])) {
            return 'liên quan đến hybrid cloud, kết nối an toàn và bảo mật mạng.';
        }

        if ($this->containsAny($haystack, ['xam nhap', 'ids', 'intrusion', 'suricata', 'an ninh mang', 'bao mat mang'])) {
            return 'liên quan đến an ninh mạng, IDS và phát hiện xâm nhập.';
        }

        $majorCode = strtoupper((string) ($product->major_code ?? $analysis['major_code'] ?? ''));

        return match ($majorCode) {
            'AI' => 'phù hợp với chủ đề AI, học máy hoặc xử lý dữ liệu.',
            'CNTT', 'IT' => 'phù hợp với chủ đề phần mềm, web hoặc ứng dụng quản lý.',
            'MMT', 'NETWORK' => 'phù hợp với chủ đề mạng máy tính và hạ tầng kết nối.',
            'TKDH', 'GRAPHIC', 'GRAPHICS' => 'phù hợp với chủ đề thiết kế, đồ họa hoặc UI/UX.',
            default => 'có nội dung gần với chủ đề bạn đang hỏi.',
        };
    }

    private function productDetailUrl(object $product, string $role): ?string
    {
        $id = $product->id ?? $product->product_id ?? null;

        if (!$id) {
            return null;
        }

        $frontendUrl = rtrim(
            config('app.frontend_url')
                ?? env('FRONTEND_URL')
                ?? config('app.url'),
            '/'
        );

        if ($role === 'teacher') {
            return "{$frontendUrl}/giang-vien/chi-tiet-san-pham/{$id}";
        }

        return "{$frontendUrl}/chi-tiet-san-pham/{$id}";
    }

    private function containsAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function openAiFallbackResponse(string $message, ?int $majorId, string $role)
    {
        $products = $this->safeFindLocalProductsForMessage($message, $majorId, $role);
        $analysis = $this->analyzeLocalSearchQuery($message);

        if (!empty($products)) {
            return response()->json([
                'reply' => "AI đang tạm mất kết nối, nhưng mình vẫn thấy vài sản phẩm liên quan:\n" . $this->formatProductList($products, $role, $analysis),
                'products' => $products,
                'source' => 'local_fallback',
                'ai_unavailable' => true,
                'analysis' => $analysis,
            ]);
        }

        return response()->json([
            'reply' => $this->buildNoLocalProductsReply($analysis),
            'products' => [],
            'source' => 'local_fallback',
            'ai_unavailable' => true,
            'analysis' => $analysis,
        ]);
    }

    private function findLocalProductsForMessage(string $message, ?int $majorId, string $role, int $limit = 5): array
    {
        $analysis = $this->analyzeLocalSearchQuery($message);
        $stages = $this->fallbackProductSearchStages($message, $analysis);

        if (empty($stages)) {
            return [];
        }

        foreach ($stages as $stage) {
            $query = $this->localProductSearchBaseQuery();

            // ✅ Chatbot product search vẫn chỉ lấy sản phẩm đã duyệt
            // ✅ Student/Teacher vẫn bị khóa theo ngành của tài khoản
            // ✅ Guest/Admin được tìm toàn bộ sản phẩm đã duyệt
            if (in_array($role, ['student', 'teacher'], true)) {
                if (!$majorId) {
                    return [];
                }

                $query->where('products.major_id', $majorId);
            }

            $stageMajorCode = $stage['major_code'] ?? null;

            // ✅ Major semantic chỉ dùng để lọc thêm khi phù hợp
            // Ví dụ guest/admin hỏi CNTT thì lọc CNTT
            // Student/Teacher thì vẫn đã bị khóa bởi major_id bên trên
            if ($stageMajorCode) {
                $query->whereIn(
                    DB::raw('UPPER(majors.major_code)'),
                    $this->majorCodeAliasesForSearch($stageMajorCode)
                );
            }

            $stageTerms = $this->uniqueSearchTerms($stage['terms'] ?? []);
            $listAll = (bool) ($stage['list_all'] ?? false);

            if (!empty($stageTerms)) {
                $this->applyLocalProductSearchTerms($query, $stageTerms);
            } elseif (!$listAll) {
                continue;
            }

            $stageAnalysis = array_merge($analysis, [
                'terms' => !empty($stageTerms) ? $stageTerms : ($analysis['terms'] ?? []),
            ]);

            $this->orderLocalSearchByRelevance($query, $stageAnalysis);

            $products = $query
                ->limit($limit)
                ->get()
                ->unique('id')
                ->values()
                ->toArray();

            if (!empty($products)) {
                return $products;
            }
        }

        return [];
    }

    private function localProductSearchBaseQuery()
    {
        $tagSummary = DB::table('product_tags')
            ->select(
                'product_id',
                DB::raw('GROUP_CONCAT(DISTINCT tag_name SEPARATOR ", ") as tags')
            )
            ->groupBy('product_id');

        return DB::table('products')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->leftJoinSub($tagSummary, 'tag_summary', function ($join) {
                $join->on('products.product_id', '=', 'tag_summary.product_id');
            })
            ->leftJoin('product_ai', 'products.product_id', '=', 'product_ai.product_id')
            ->leftJoin('product_cntt', 'products.product_id', '=', 'product_cntt.product_id')
            ->leftJoin('product_mmt', 'products.product_id', '=', 'product_mmt.product_id')
            ->leftJoin('product_graphic', 'products.product_id', '=', 'product_graphic.product_id')
            ->select(
                'products.product_id as id',
                'products.product_id',
                'products.title',
                'products.description',
                'products.github_link',
                'products.demo_link',
                'majors.major_name',
                'majors.major_code',
                'categories.category_name',
                DB::raw('COALESCE(product_statistics.views, 0) as views'),
                DB::raw('COALESCE(product_statistics.likes, 0) as likes'),
                DB::raw('COALESCE(tag_summary.tags, "") as tags'),
                'product_ai.model_used',
                'product_ai.framework as ai_framework',
                'product_ai.language as ai_language',
                'product_ai.dataset_used',
                'product_ai.accuracy_score',
                'product_cntt.programming_language',
                'product_cntt.framework as cntt_framework',
                DB::raw('COALESCE(product_cntt.framework, product_ai.framework) as framework'),
                'product_cntt.database_used',
                'product_mmt.network_protocol',
                'product_mmt.topology_type',
                'product_mmt.simulation_tool',
                'product_graphic.design_type',
                'product_graphic.tools_used',
                'product_graphic.behance_link'
            )
            ->where('products.status', 'approved');
    }

    private function applyLocalProductSearchTerms($query, array $terms): void
    {
        $terms = $this->uniqueSearchTerms($terms);

        if (empty($terms)) {
            return;
        }

        $query->where(function ($subQuery) use ($terms) {
            foreach ($terms as $term) {
                $variants = $this->uniqueSearchTerms([
                    $term,
                    $this->normalizeSearchText($term),
                    $this->normalizeTagText($term),
                ]);

                foreach ($variants as $variant) {
                    if ($variant === '') {
                        continue;
                    }

                    $like = '%' . $variant . '%';

                    $subQuery
                        ->orWhere('products.title', 'like', $like)
                        ->orWhere('products.description', 'like', $like)
                        ->orWhere('products.github_link', 'like', $like)
                        ->orWhere('products.demo_link', 'like', $like)
                        ->orWhere('majors.major_name', 'like', $like)
                        ->orWhere('majors.major_code', 'like', $like)
                        ->orWhere('categories.category_name', 'like', $like)
                        ->orWhere('tag_summary.tags', 'like', $like)
                        ->orWhere('product_ai.model_used', 'like', $like)
                        ->orWhere('product_ai.framework', 'like', $like)
                        ->orWhere('product_ai.language', 'like', $like)
                        ->orWhere('product_ai.dataset_used', 'like', $like)
                        ->orWhere('product_cntt.programming_language', 'like', $like)
                        ->orWhere('product_cntt.framework', 'like', $like)
                        ->orWhere('product_cntt.database_used', 'like', $like)
                        ->orWhere('product_mmt.network_protocol', 'like', $like)
                        ->orWhere('product_mmt.topology_type', 'like', $like)
                        ->orWhere('product_mmt.simulation_tool', 'like', $like)
                        ->orWhere('product_graphic.design_type', 'like', $like)
                        ->orWhere('product_graphic.tools_used', 'like', $like);
                }
            }
        });
    }

    private function fallbackProductSearchStages(string $message, array $analysis): array
    {
        $terms = $this->uniqueSearchTerms($analysis['terms'] ?? []);
        $tokens = $this->importantSearchTokens($message);
        $majorCode = $analysis['major_code'] ?? null;

        $stages = [];

        if (!empty($terms)) {
            $stages[] = [
                'terms' => $terms,
                'major_code' => $majorCode,
                'list_all' => false,
            ];
        }

        if (!empty($tokens)) {
            $stages[] = [
                'terms' => $tokens,
                'major_code' => $majorCode,
                'list_all' => false,
            ];
        }

        if ($this->isClearlyCnttWebRelated($message)) {
            $stages[] = [
                'terms' => $this->uniqueSearchTerms([
                    'java',
                    'spring',
                    'spring boot',
                    'react',
                    'reactjs',
                    'mysql',
                    'api',
                    'web',
                    'website',
                    'web application',
                    'ứng dụng web',
                    'he thong quan ly',
                    'hệ thống quản lý',
                    'quan ly',
                    'quản lý',
                    'crud',
                    'backend',
                    'frontend',
                    'fullstack',
                    'cntt',
                    'công nghệ thông tin',
                ]),
                'major_code' => 'CNTT',
                'list_all' => false,
            ];
        }

        if (empty($stages) && $majorCode) {
            $stages[] = [
                'terms' => [],
                'major_code' => $majorCode,
                'list_all' => true,
            ];
        }

        return $stages;
    }

    private function importantSearchTokens(string $message): array
    {
        $normalized = $this->normalizeSearchText($message);

        $tokens = [
            'java',
            'spring',
            'spring boot',
            'react',
            'reactjs',
            'mysql',
            'api',
            'rest api',
            'restful api',
            'web',
            'website',
            'application',
            'web application',
            'web app',
            'quan ly',
            'he thong quan ly',
            'ban hang',
            'don hang',
            'san pham',
            'crud',
            'ecommerce',
            'e commerce',
            'shop online',
            'dashboard',
            'admin panel',
            'backend',
            'frontend',
            'fullstack',
            'jwt',
            'auth',
            'login',
        ];

        $matched = [];

        foreach ($tokens as $token) {
            if ($this->containsSemanticKeyword($normalized, $token)) {
                $matched[] = $token;
            }
        }

        foreach (preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if ($this->isUsefulSearchWord($word)) {
                $matched[] = $word;
            }
        }

        return array_slice($this->uniqueSearchTerms($matched), 0, 20);
    }

    private function isClearlyCnttWebRelated(string $message): bool
    {
        $normalized = $this->normalizeSearchText($message);

        return $this->containsAny($normalized, [
            'java',
            'spring',
            'spring boot',
            'react',
            'reactjs',
            'mysql',
            'api',
            'rest api',
            'restful api',
            'web',
            'website',
            'web application',
            'web app',
            'ung dung web',
            'he thong quan ly',
            'quan ly',
            'ban hang',
            'don hang',
            'san pham',
            'crud',
            'dashboard',
            'admin panel',
            'backend',
            'frontend',
            'fullstack',
            'ecommerce',
            'e commerce',
            'shop online',
            'jwt',
            'auth',
            'login',
        ]);
    }

    private function uniqueSearchTerms(array $terms): array
    {
        $unique = [];
        $result = [];

        foreach ($terms as $term) {
            $term = trim((string) $term);

            if ($term === '') {
                continue;
            }

            $key = $this->normalizeTagText($term);

            if ($key === '' || isset($unique[$key])) {
                continue;
            }

            $unique[$key] = true;
            $result[] = $term;
        }

        return array_values($result);
    }

    private function normalizeTagText(string $value): string
    {
        $value = str_replace('#', ' ', $value);

        return $this->normalizeSearchText($value);
    }

    private function extractLocalSearchTerms(string $message): array
    {
        return $this->analyzeLocalSearchQuery($message)['terms'];
    }

    private function analyzeLocalSearchQuery(string $message): array
    {
        $terms = $this->extractRawLocalSearchTerms($message);
        $majorCode = $this->detectSemanticMajorCode($message);
        $terms = array_merge($terms, $this->expandedSemanticTerms($message, $majorCode));

        $unique = [];
        $result = [];

        foreach ($terms as $term) {
            $term = trim((string) $term);
            $key = mb_strtolower($term, 'UTF-8');

            if ($term === '' || isset($unique[$key])) {
                continue;
            }

            $unique[$key] = true;
            $result[] = $term;
        }

        return [
            'major_code' => $majorCode,
            'major_name' => $majorCode ? $this->majorLabel($majorCode) : null,
            'terms' => array_slice($result, 0, 30),
        ];
    }

    private function extractRawLocalSearchTerms(string $message): array
    {
        $normalizedKeyword = $this->removeGenericSearchWords($this->normalizeSearchText($message));

        if ($normalizedKeyword === '') {
            return [];
        }

        $keywordWords = array_flip(preg_split('/\s+/', $normalizedKeyword, -1, PREG_SPLIT_NO_EMPTY));
        $originalWords = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($message, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
        $originalKeywordWords = [];

        foreach ($originalWords as $word) {
            $normalizedWord = $this->normalizeSearchText($word);

            if (isset($keywordWords[$normalizedWord])) {
                $originalKeywordWords[] = $word;
            }
        }

        $terms = [];
        $originalKeyword = trim(implode(' ', $originalKeywordWords));

        if ($originalKeyword !== '') {
            $terms[] = $originalKeyword;
        }

        $terms[] = $normalizedKeyword;

        foreach ($originalKeywordWords as $word) {
            if ($this->isUsefulSearchWord($word)) {
                $terms[] = $word;
            }
        }

        foreach (preg_split('/\s+/', $normalizedKeyword, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if ($this->isUsefulSearchWord($word)) {
                $terms[] = $word;
            }
        }

        $unique = [];
        $result = [];

        foreach ($terms as $term) {
            $term = trim((string) $term);
            $key = mb_strtolower($term, 'UTF-8');

            if ($term === '' || isset($unique[$key])) {
                continue;
            }

            $unique[$key] = true;
            $result[] = $term;
        }

        return array_slice($result, 0, 8);
    }

    private function isUsefulSearchWord(string $word): bool
    {
        $normalized = $this->normalizeSearchText($word);

        if (strlen($normalized) < 3) {
            return false;
        }

        $noisyWords = [
            'thong',
            'phat',
            'hien',
            'hoa',
            'dong',
            'thiet',
            'ke',
            'san',
            'pham',
            'nhung',
            'cac',
            'cho',
            'xem',
            'tim',
            'kiem',
            'he',
        ];

        return !in_array($normalized, $noisyWords, true);
    }

    private function detectSemanticMajorCode(string $message): ?string
    {
        $normalized = $this->normalizeSearchText($message);

        $majorKeywords = [
            'AI' => [
                'ai',
                'tri tue nhan tao',
                'artificial intelligence',
                'hoc may',
                'machine learning',
                'deep learning',
                'computer vision',
                'thi giac may tinh',
                'nlp',
                'chatbot',
                'nhan dien',
                'du doan',
                'phan loai',
                'tu dong hoa',
                'automation',
                'robot',
                'yolo',
                'tensorflow',
                'pytorch',
                'opencv',
                'dataset',
                'nang suat',
                'cay trong',
                'nong nghiep',
                'khi tuong',
                'du bao san luong',
                'uoc luong san luong',
                'crop',
                'yield',
                'agriculture',
            ],
            'MMT' => [
                'mmt',
                'mang',
                'mang may tinh',
                'an ninh mang',
                'bao mat mang',
                'xam nhap',
                'phat hien xam nhap',
                'intrusion detection',
                'intrusion',
                'ids',
                'suricata',
                'network security',
                'secure cloud',
                'firewall',
                'vlan',
                'ospf',
                'vpn',
                'ipsec',
                'bgp',
                'hybrid',
                'hybrid cloud',
                'ket noi hybrid',
                'ket noi hybrid cloud',
                'cloud',
                'cloud computing',
                'dien toan dam may',
                'private cloud',
                'public cloud',
                'cloud an toan',
                'aws vpc',
                'hub and spoke',
                'radius',
                'zero trust',
                'packet tracer',
                'cisco',
                'router',
                'switch',
                'zabbix',
                'wireshark',
                'netflow',
                'tcp ip',
                'giao thuc',
                'topology',
                'wifi',
                'wi fi',
                'wireless',
                'wlan',
                'ssid',
                'wpa2',
                'capwap',
                'roaming',
            ],
            'GRAPHIC' => [
                'tkdh',
                'do hoa',
                'thiet ke do hoa',
                'graphic',
                'graphics',
                'graphic design',
                'ui ux',
                'figma',
                'photoshop',
                'illustrator',
                'poster',
                'logo',
                'banner',
                'branding',
                'nhan dien thuong hieu',
                'bo nhan dien',
                'infographic',
                'motion graphic',
                'bao bi',
                'packaging',
            ],
            'CNTT' => [
                'cntt',
                'cong nghe thong tin',
                'it',
                'phan mem',
                'website',
                'web app',
                'ung dung',
                'mobile app',
                'he thong quan ly',
                'laravel',
                'react',
                'vue',
                'nodejs',
                'php',
                'javascript',
                'mysql',
                'api',
                'django',
                'dat phong',
                'ban hang',
                'thu vien',
            ],
        ];

        foreach ($majorKeywords as $majorCode => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->containsSemanticKeyword($normalized, $keyword)) {
                    return $majorCode;
                }
            }
        }

        return null;
    }

    private function containsSemanticKeyword(string $normalized, string $keyword): bool
    {
        if (strlen($keyword) <= 2) {
            return (bool) preg_match('/(^|\s)' . preg_quote($keyword, '/') . '($|\s)/u', $normalized);
        }

        return str_contains($normalized, $keyword);
    }

    private function expandedSemanticTerms(string $message, ?string $majorCode): array
    {
        $normalized = $this->normalizeSearchText($message);
        $terms = [];

        if ($this->containsAny($normalized, ['phat hien xam nhap', 'xam nhap', 'intrusion', 'ids'])) {
            $terms = array_merge($terms, [
                'phát hiện xâm nhập mạng',
                'xâm nhập mạng',
                'IDS',
                'Suricata',
                'an ninh mạng',
                'bảo mật mạng',
                'intrusion detection',
            ]);
        }

        if ($this->containsAny($normalized, [
            'phat hien xam nhap',
            'xam nhap',
            'intrusion',
            'ids',
        ])) {
            $terms = array_merge($terms, [
                'he thong phat hien xam nhap mang',
                'phat hien xam nhap mang',
                'xam nhap mang',
                'an ninh mang',
                'bao mat mang',
                'network security',
                'MMT',
            ]);
        }

        if ($this->containsAny($normalized, [
            'hybrid',
            'hybrid cloud',
            'ket noi hybrid',
            'ket noi hybrid cloud',
            'cloud',
            'cloud computing',
            'dien toan dam may',
            'dam may',
            'private cloud',
            'public cloud',
            'secure cloud',
            'network security',
            'zero trust',
            'vpn',
            'cloud an toan',
            'aws vpc',
            'ipsec',
            'bgp',
        ])) {
            $terms = array_merge($terms, [
                'Ket noi hybrid cloud an toan',
                'Kết nối hybrid cloud an toàn',
                'ket noi hybrid cloud an toan',
                'kết nối hybrid cloud',
                'ket noi hybrid cloud',
                'ket noi hybrid',
                'hybrid cloud',
                'điện toán đám mây',
                'dien toan dam may',
                'cloud computing',
                'bảo mật cloud',
                'bao mat cloud',
                'network security',
                'secure cloud',
                'cloud an toan',
                'VPN',
                'Zero Trust',
                'Mạng máy tính',
                'mang may tinh',
                'MMT',
                'AWS VPC',
                'IPsec',
                'BGP',
                'hub-and-spoke',
                'hub and spoke',
                'duong ham ma hoa',
                'mo hinh ket noi ha tang',
            ]);
        }

        if ($this->containsAny($normalized, ['wifi', 'wi fi', 'wireless', 'wlan', 'ssid'])) {
            $terms = array_merge($terms, [
                'Mạng Wi-Fi doanh nghiệp quản lý tập trung',
                'mạng Wi-Fi',
                'Wi-Fi',
                'wifi',
                'wireless',
                'WLAN',
                'SSID',
                'WPA2-Enterprise',
                'RADIUS',
                'CAPWAP',
                'roaming',
            ]);
        }

        if ($this->containsAny($normalized, ['do hoa', 'tkdh', 'graphic', 'thiet ke'])) {
            $terms = array_merge($terms, [
                'Thiết kế đồ họa',
                'TKDH',
                'graphic',
                'Figma',
                'Photoshop',
                'Illustrator',
            ]);
        }

        if ($this->containsAny($normalized, ['tu dong hoa', 'automation', 'tu dong'])) {
            $terms = array_merge($terms, [
                'tự động hóa',
                'tự động',
                'automation',
                'AI',
            ]);
        }

        if ($this->containsAny($normalized, [
            'nang suat',
            'cay trong',
            'cay',
            'nong nghiep',
            'du bao san luong',
            'uoc luong san luong',
            'crop',
            'yield',
            'agriculture',
        ])) {
            $terms = array_merge($terms, [
                'Ước lượng năng suất cây trồng',
                'năng suất cây trồng',
                'nang suat cay trong',
                'cây trồng',
                'nông nghiệp',
                'dự báo sản lượng',
                'Random Forest',
                'Scikit-learn',
                'khí tượng',
                'crop yield',
            ]);
        }

        if ($this->containsAny($normalized, ['web', 'website', 'laravel', 'react'])) {
            $terms = array_merge($terms, [
                'website',
                'web',
                'Laravel',
                'React',
            ]);
        }

        if ($majorCode) {
            $terms[] = $this->majorLabel($majorCode);
            $terms[] = $majorCode === 'GRAPHIC' ? 'TKDH' : $majorCode;
        }

        return $terms;
    }

    private function looksLikeProductTopicQuery(string $message): bool
    {
        $analysis = $this->analyzeLocalSearchQuery($message);

        if ($analysis['major_code']) {
            return true;
        }

        $normalized = $this->normalizeSearchText($message);

        return $this->containsAny($normalized, [
            'he thong',
            'ung dung',
            'website',
            'phan mem',
            'mo hinh',
            'giai phap',
            'cong cu',
            'dashboard',
            'poster',
            'bo nhan dien',
            'bao bi',
        ]) && !empty($analysis['terms']);
    }

    private function majorCodeAliasesForSearch(string $majorCode): array
    {
        return match (strtoupper($majorCode)) {
            'GRAPHIC' => ['TKDH', 'GRAPHIC', 'GRAPHICS', 'GR'],
            'CNTT' => ['CNTT', 'IT'],
            'MMT' => ['MMT', 'NETWORK'],
            'AI' => ['AI'],
            default => [strtoupper($majorCode)],
        };
    }

    private function majorLabel(string $majorCode): string
    {
        return match (strtoupper($majorCode)) {
            'GRAPHIC' => 'Thiết kế đồ họa',
            'CNTT' => 'Công nghệ thông tin',
            'MMT' => 'Mạng máy tính',
            'AI' => 'Trí tuệ nhân tạo',
            default => $majorCode,
        };
    }

    private function detectedMajorOutsideUserScope(array $analysis, string $role, ?int $majorId): ?array
    {
        if (!in_array($role, ['student', 'teacher'], true) || !$majorId || !$analysis['major_code']) {
            return null;
        }

        try {
            $major = DB::table('majors')
                ->where('major_id', $majorId)
                ->select('major_name', 'major_code')
                ->first();
        } catch (Throwable $exception) {
            Log::warning('AI chatbox major scope check failed', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if (!$major) {
            return null;
        }

        $userCode = strtoupper((string) $major->major_code);
        $allowedCodes = $this->majorCodeAliasesForSearch($analysis['major_code']);

        if (in_array($userCode, $allowedCodes, true)) {
            return null;
        }

        return [
            'user_major' => ($major->major_name ?? 'ngành của bạn') . ' (' . $userCode . ')',
            'detected_major' => $analysis['major_name'] . ' (' . ($analysis['major_code'] === 'GRAPHIC' ? 'TKDH' : $analysis['major_code']) . ')',
        ];
    }

    private function orderLocalSearchByRelevance($query, array $analysis): void
    {
        $terms = $this->uniqueSearchTerms($analysis['terms'] ?? []);

        if (!empty($terms)) {
            $scoreParts = [];
            $bindings = [];

            foreach (array_slice($terms, 0, 12) as $term) {
                $normalizedTerm = $this->normalizeTagText($term);

                if ($normalizedTerm === '') {
                    continue;
                }

                $like = '%' . $normalizedTerm . '%';

                $scoreParts[] = '
                CASE
                    WHEN products.title LIKE ? THEN 120
                    WHEN tag_summary.tags LIKE ? THEN 100
                    WHEN product_cntt.programming_language LIKE ? THEN 100
                    WHEN product_cntt.framework LIKE ? THEN 100
                    WHEN product_cntt.database_used LIKE ? THEN 100
                    WHEN product_ai.framework LIKE ? THEN 90
                    WHEN product_ai.language LIKE ? THEN 90
                    WHEN product_ai.model_used LIKE ? THEN 90
                    WHEN products.description LIKE ? THEN 70
                    WHEN categories.category_name LIKE ? THEN 45
                    WHEN products.github_link LIKE ? THEN 35
                    WHEN products.demo_link LIKE ? THEN 35
                    WHEN majors.major_name LIKE ? OR majors.major_code LIKE ? THEN 20
                    ELSE 0
                END
            ';

                array_push(
                    $bindings,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like
                );
            }

            if (!empty($scoreParts)) {
                $query->orderByRaw('(' . implode(' + ', $scoreParts) . ') DESC', $bindings);
            }
        }

        $query->orderByDesc('views')
            ->orderByDesc('likes')
            ->orderByDesc('products.submitted_at');
    }

    private function looksLikeProductListingRequest(string $message): bool
    {
        $normalized = $this->normalizeSearchText($message);

        foreach (['san pham', 'do an', 'du an', 'de tai', 'project', 'products'] as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function removeGenericSearchWords(string $normalized): string
    {
        $phrases = [
            'cho tui',
            'cho toi',
            'cho minh',
            'ne',
            'giup tui',
            'giup toi',
            'giup minh',
            'tim kiem',
            'danh sach',
            'san pham',
            'do an',
            'du an',
            'de tai',
            'tai lieu',
            'bai lam',
            'bai tap',
            'lien quan',
            'phu hop',
            'da duyet',
            'moi nhat',
            'nhieu luot xem',
            'huong dan',
            'lam sao',
            'cach',
            'chuc nang',
            'kiem tra',
            'check',
            'so sanh',
            'trung lap',
            'duplicate',
            'hinh anh',
            'anh',
            'hinh',
            'or',
            'hoac',
            'approved',
            'products',
            'product',
            'projects',
            'project',
            'search',
            'tim',
            'kiem',
            'xem',
            'lay',
            'can',
            'muon',
            'nhung',
            'cac',
            've',
            'thuoc',
            'trong',
            'cua',
            'cho',
        ];

        foreach ($phrases as $phrase) {
            $normalized = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/u', ' ', $normalized);
        }

        return trim(preg_replace('/\s+/', ' ', $normalized));
    }

    private function normalizeSearchText(string $value): string
    {
        $value = $this->removeVietnameseAccents(mb_strtolower($value, 'UTF-8'));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function removeVietnameseAccents(string $value): string
    {
        $map = [
            'à' => 'a',
            'á' => 'a',
            'ạ' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ứ' => 'u',
            'ự' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'đ' => 'd',
        ];

        return strtr($value, $map);
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

        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $userName = $user?->name ?? $user?->username ?? null;
        $userInfoLine = $userName
            ? "- Tên người dùng: {$userName}"
            : "- Người dùng: Khách chưa đăng nhập";

        $greetingRule = match ($role) {
            'admin' => $userName
                ? "Khi người dùng chào, hãy chào ngắn gọn: \"Xin chào {$userName}! Bạn cần tra cứu sản phẩm, thống kê hay quản lý dữ liệu nào?\""
                : "Khi người dùng chào, hãy chào ngắn gọn và hỏi họ cần tra cứu hay thống kê gì.",
            'teacher' => $userName
                ? "Khi người dùng chào, hãy chào ngắn gọn: \"Xin chào thầy/cô {$userName}! Thầy/cô cần xem sản phẩm, kiểm duyệt hay so sánh trùng không?\""
                : "Khi người dùng chào, hãy chào ngắn gọn theo vai trò giảng viên.",
            'student' => $userName
                ? "Khi người dùng chào, hãy chào ngắn gọn: \"Xin chào {$userName}! Bạn muốn tìm sản phẩm hay hỏi cách dùng hệ thống?\""
                : "Khi người dùng chào, hãy chào ngắn gọn và hỏi họ muốn tìm sản phẩm gì.",
            default => "Khi người dùng chào, hãy chào ngắn gọn và giới thiệu bạn là trợ lý tra cứu sản phẩm học tập.",
        };

        $roleRule = match ($role) {
            'admin' =>
            "- Admin có thể xem thông tin tổng hợp toàn hệ thống nếu dữ liệu được cung cấp.\n" .
                "- Có thể trả lời thống kê, danh sách sản phẩm, ngành, danh mục và hoạt động hệ thống.\n" .
                "- Không bịa số liệu nếu dữ liệu không có.",
            'teacher' =>
            "- Giảng viên có thể tra cứu sản phẩm đã duyệt, xem sản phẩm liên quan và hỏi về quy trình kiểm duyệt.\n" .
                "- Không tiết lộ thông tin cá nhân nhạy cảm hoặc dữ liệu không được cung cấp.\n" .
                "- Nếu câu hỏi thuộc quản lý/duyệt sản phẩm, trả lời theo dữ liệu được cung cấp.",
            'student' =>
            "- Sinh viên có thể tra cứu các sản phẩm đã duyệt để tham khảo.\n" .
                "- Không tiết lộ điểm số, tiến độ hoặc thông tin cá nhân của sinh viên khác.\n" .
                "- Nếu hỏi sản phẩm ngoài ngành, vẫn có thể giới thiệu sản phẩm đã duyệt nếu dữ liệu được cung cấp.",
            default =>
            "- Khách chỉ được xem thông tin công khai, sản phẩm đã duyệt, ngành, danh mục và link demo/github nếu dữ liệu có.\n" .
                "- Không cung cấp thông tin cá nhân hoặc dữ liệu quản trị.",
        };

        $scopeRule = "
QUY TẮC PHẠM VI:
- Chỉ trả lời dựa trên sản phẩm đã duyệt có trong dữ liệu được cung cấp.
- Với khách và quản trị viên, có thể tra cứu sản phẩm đã duyệt ở toàn bộ ngành nếu dữ liệu được cung cấp.
- Với sinh viên và giảng viên, chỉ tra cứu sản phẩm đã duyệt thuộc ngành của tài khoản.
- Không hiển thị hoặc gợi ý sản phẩm ngoài ngành của sinh viên/giảng viên.
- Chỉ giới hạn theo vai trò chặt hơn khi câu hỏi liên quan dữ liệu nội bộ, quản lý, kiểm duyệt, tiến độ, người dùng hoặc thông tin nhạy cảm.
- Nếu dữ liệu được cung cấp không có sản phẩm phù hợp, hãy nói không tìm thấy một cách ngắn gọn, không đổ lỗi cho hệ thống.
";

        $answerStyleRule = "
    PHONG CÁCH TRẢ LỜI:
    - Trả lời tiếng Việt tự nhiên, ngắn gọn, thân thiện.
    - Không nói lan man.
    - Không dùng văn phong máy móc.
    - Không lặp lại nguyên văn câu hỏi của người dùng nếu không cần.
    - Không nói các cụm như: \"mình đã phân tích\", \"mình đã tìm theo nhóm\", \"dữ liệu truy xuất\", \"backend\", \"database\", \"MySQL\", \"terms\", \"keyword\", \"retrieved_products\".
    - Không tiết lộ tên bảng, tên cột, cấu trúc database hoặc quá trình xử lý nội bộ.
    - Người dùng chỉ cần thấy kết quả cuối cùng: có sản phẩm hay không, tên sản phẩm, vì sao liên quan, và cách xem chi tiết.
    ";

        $productAnswerRule = "
    QUY TẮC KHI TRẢ LỜI VỀ SẢN PHẨM:
    - Nếu tìm thấy sản phẩm, hãy mở đầu thân thiện như: \"Có nha, mình tìm thấy sản phẩm phù hợp:\".
    - Liệt kê tối đa 3 đến 5 sản phẩm phù hợp nhất.
    - Mỗi sản phẩm chỉ nên gồm:
      1. Tên sản phẩm.
      2. Lý do liên quan ngắn gọn theo câu hỏi.
      3. Link xem chi tiết nếu dữ liệu có link/URL phù hợp.
    - Nếu có github_link hoặc demo_link, chỉ nhắc khi phù hợp với câu hỏi.
    - Nếu không có URL chi tiết, hãy nói: \"Bạn có thể bấm vào sản phẩm bên dưới để xem chi tiết.\".
    - Không bịa sản phẩm, không bịa link, không bịa số liệu.
    ";

        $noResultRule = "
    QUY TẮC KHI KHÔNG TÌM THẤY:
    - Trả lời ngắn gọn, không lộ kỹ thuật.
    - Mẫu tốt:
      \"Mình chưa thấy sản phẩm phù hợp trong danh sách đã duyệt. Bạn thử kiểm tra lại tên sản phẩm hoặc tìm bằng từ khóa gần hơn nha.\"
    - Nếu câu hỏi có chủ đề rõ, có thể gợi ý 3-4 từ khóa liên quan.
    - Không gợi ý lại y chang toàn bộ câu người dùng vừa nhập.
    - Gợi ý phải thông minh theo chủ đề:
      + Web bán hàng: website, ecommerce, đơn hàng, giỏ hàng, thanh toán.
      + Laravel/React: Laravel, ReactJS, API, MySQL.
      + Xâm nhập mạng: IDS, Suricata, bảo mật mạng, firewall.
      + Hybrid cloud: hybrid cloud, điện toán đám mây, VPN, Zero Trust.
      + AI: Python, machine learning, dataset, TensorFlow.
      + Thiết kế: Figma, UI/UX, poster, nhận diện thương hiệu.
    ";

        $featureRule = "
    QUY TẮC KHI HỎI VỀ CHỨC NĂNG:
    - Nếu hỏi tìm kiếm/search: hướng dẫn gõ tên sản phẩm, chủ đề, công nghệ hoặc ngành. Ví dụ: web bán hàng, AI Python, xâm nhập mạng, thiết kế Figma.
    - Nếu hỏi kiểm tra hình ảnh: nói ngắn gọn rằng hệ thống kiểm tra ảnh khi đăng/chỉnh sửa sản phẩm, ảnh phải liên quan và không chứa nội dung không phù hợp.
    - Nếu hỏi so sánh/trùng lặp: nói giảng viên có thể dùng nút \"So sánh trùng\" để xem mức tương đồng nội dung, trường trùng và hình ảnh/gallery liên quan.
    - Nếu hỏi lỗi hệ thống: yêu cầu người dùng gửi màn hình đang thao tác, nội dung lỗi và bước vừa thực hiện.
    ";

        $dataMeaningRule = "
    CÁCH HIỂU DỮ LIỆU:
    - Dữ liệu bên dưới là nguồn thông tin duy nhất được phép dùng.
    - Với sản phẩm CNTT: có thể dùng title, description, programming_language, framework, database_used, github_link, demo_link.
    - Với sản phẩm AI: có thể dùng title, description, model_used, framework, language, dataset_used, accuracy_score.
    - Với sản phẩm MMT: có thể dùng title, description, simulation_tool, network_protocol, topology_type.
    - Với sản phẩm Graphic: có thể dùng title, description, design_type, tools_used, behance_link.
    - Nếu một trường không có dữ liệu, bỏ qua trường đó, không nói \"null\" hoặc \"chưa cập nhật\" trừ khi thật sự cần.
    ";

        return <<<PROMPT
Bạn là trợ lý AI của hệ thống quản lý và trưng bày sản phẩm học tập/sản phẩm nghiên cứu của sinh viên.

NHIỆM VỤ CHÍNH:
- Giúp người dùng tìm sản phẩm phù hợp.
- Giải thích ngắn gọn sản phẩm liên quan gì đến câu hỏi.
- Hướng dẫn sử dụng các chức năng như tìm kiếm, kiểm tra ảnh, so sánh trùng, duyệt sản phẩm.
- Trả lời dựa trên dữ liệu được cung cấp, không bịa thêm.

THÔNG TIN NGƯỜI DÙNG:
- Vai trò: {$roleLabel} ({$role})
{$userInfoLine}

QUY TẮC BẮT BUỘC:
1. Chỉ sử dụng dữ liệu được cung cấp trong phần DỮ LIỆU HỆ THỐNG.
2. Không bịa sản phẩm, link, số liệu, tác giả, ngành, công nghệ hoặc trạng thái.
3. Không tiết lộ cấu trúc database, tên bảng, tên cột hoặc quá trình xử lý nội bộ.
4. Không nhắc các từ kỹ thuật nội bộ như backend, MySQL, database, retrieved_products, terms, normalized query, keyword, analysis.
5. Trả lời ngắn gọn, thân thiện, đúng trọng tâm.
6. Khi có sản phẩm phù hợp, ưu tiên trả lời bằng danh sách rõ ràng.
7. Khi không có dữ liệu phù hợp, nói không tìm thấy một cách lịch sự và gợi ý từ khóa gần hơn nếu cần.
8. Không tự nhận đã tìm theo ngành nào, nhóm nào hoặc cụm từ nào.
9. Không nói chắc chắn nếu dữ liệu không đủ.
10. Không đưa thông tin cá nhân nhạy cảm hoặc dữ liệu không được cung cấp.

{$greetingRule}

QUY TẮC THEO VAI TRÒ:
{$roleRule}

{$scopeRule}

{$answerStyleRule}

{$productAnswerRule}

{$noResultRule}

{$featureRule}

{$dataMeaningRule}

DỮ LIỆU HỆ THỐNG:
{$dataJson}
PROMPT;
    }
}

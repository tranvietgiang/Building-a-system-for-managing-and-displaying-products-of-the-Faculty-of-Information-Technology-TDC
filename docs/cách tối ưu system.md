# Cách tối ưu system

Danh sách các vấn đề cần tối ưu trong hệ thống backend Laravel. **Chỉ liệt kê, không sửa code.**

---

## 1. AuthService - Thiếu kiểm tra key array

**File:** `backend-laravel/app/Services/AuthService.php`

- Dòng 23: `$data['username']` không kiểm tra key tồn tại trước → crash nếu gọi `login([])`
- Dòng 47: `$data['password']` không kiểm tra key tồn tại trước → crash nếu thiếu password
- **Cần:** Dùng `$data['username'] ?? ''` hoặc validate từ đầu

---

## 2. ProductRepository - N+1 queries + duplicate code

**File:** `backend-laravel/app/Repositories/ProductRepository.php`

### 2.1. $majorCode query
- Dòng 28: Gọi `$this->majorRepository->getMajorCodeByProductId()` → thêm 1 query riêng biệt. Có thể JOIN thẳng trong query chính

### 2.2. JOIN tất cả bảng detail mỗi lần
- Dòng 37-41: LEFT JOIN `product_ai`, `product_cntt`, `product_mmt`, `product_graphic` trong cùng 1 câu query → load dư thừa 3 bảng không cần thiết mỗi lần. Chỉ nên JOIN bảng tương ứng với major của sản phẩm

### 2.3. 3 function increment giống hệt nhau
- `incrementView()` (dòng 899), `incrementLike()` (dòng 929), `incrementShare()` (dòng 959) → **90% code giống nhau**, chỉ khác tên cột. Có thể gộp thành 1 function `incrementStat(int $productId, string $column)`

### 2.4. getVisitorProductById() - N+1 queries
- Dòng 692-897: Function này chạy **4-5+ queries riêng biệt** (product, images, tags, reviews, major_detail). Có thể dùng `with()` hoặc JOIN + map để giảm xuống 2-3 queries

---

## 3. UploadRepository - Duplicate logic

**File:** `backend-laravel/app/Repositories/UploadRepository.php`

- Dòng 85-169: Switch-case giống với switch-case trong `ProductRepository::findProductById()` (dòng 201-237). Cùng logic map major_code → insert/fetch detail → code bị copy-paste. Cần tạo 1 helper class

---

## 4. SystemSettingService - Wrong boolean cast

**File:** `backend-laravel/app/Services/SystemSettingService.php`

- Dòng 57: `'value' => (bool) $values[$key]` — PHP cast `(bool) 'false'` = `true` vì string không rỗng. Nếu client gửi JSON `"false"` (string) thì sẽ bị sai
- **Cần:** Dùng `filter_var($values[$key], FILTER_VALIDATE_BOOLEAN)` thay vì `(bool)`

---

## 5. NormalizeMajorCode - Partial match không dấu

**File:** `backend-laravel/app/Http/Common/NormalizeMajorCode.php`

- Dòng 40: `str_contains($v, $keyword)` chỉ match chính xác từ khóa có dấu. Người dùng gõ `'tri tue nhan tao'` (không dấu) hoặc `'hoc ve tri tue nhan tao'` (partial) → không match
- **Cần:** Thêm bước normalize dấu tiếng Việt (remove accents) trước khi so khớp

---

## 6. ProductDuplicateService - Phụ thuộc config() helper

**File:** `backend-laravel/app/Services/ProductDuplicateService.php`

- Dòng 57: Gọi `config('services.openai.key')` trực tiếp → không thể khởi tạo class bằng `new` ngoài container
- Dòng 157: `config('product.duplicate_similarity_threshold', 95)` — dùng config helper
- **Cần:** Inject config qua constructor hoặc dùng `app(config)` thay vì global helper

---

## 7. ChatBoxAi - File quá lớn

**File:** `backend-laravel/app/Http/Ai/ChatBoxAi.php` (2458 dòng)

- Hàm `isRelevantQuestion()` có danh sách keywords cứng rất dài (dòng 23-200)
- Có thể tách keywords ra 1 class riêng hoặc config file
- Reply bank (`nonRelevantReplyBank`) chiếm ~500-700 dòng
- **Cần:** Chia nhỏ file thành nhiều class: `ChatKeywords.php`, `ReplyBank.php`, `ChatAnalyzer.php`

---

## 8. SearchAi - File quá lớn

**File:** `backend-laravel/app/Http/Ai/SearchAi.php` (1049 dòng)

- Tương tự ChatBoxAi, cần chia nhỏ thành các module
- Logic search intent và multi-table search có thể tách riêng

---

## 9. ProductController - searchProducts() quá dài

**File:** `backend-laravel/app/Http/Controllers/Api/ProductController.php`

- `searchProducts()` (dòng 57-199): ~140 dòng, xử lý quá nhiều logic (validate, detect major, scout search, sort, paginate)
- Các method private `detectMajorCodeFromKeyword()`, `cleanKeywordForMajor()`, `majorAliases()`, `majorKeywordAliases()`, `normalizeSearchText()` → có thể tách thành 1 SearchService riêng
- **Cần:** Tạo `App\Services\ProductSearchService` để DI

---

## 10. AdminController - File quá lớn

**File:** `backend-laravel/app/Http/Controllers/Api/AdminController.php` (772 dòng)

- `dashboard()` + `buildDashboardAiInsights()` → có thể tách thành `DashboardService`
- `users()`, `storeUser()`, `updateUser()`, `destroyUser()` → có thể tách thành `AdminUserService`
- `products()`, `updateProductStatus()`, `destroyProduct()` → có thể tách thành `AdminProductService`
- `majors()`, `storeMajor()`, `updateMajor()`, `destroyMajor()` → có thể tách thành `AdminMajorService`
- `supportRequests()`, `markSupportProcessed()`, `lookupPasswordRecoveryUser()`, `sendPasswordRecovery()` → có thể tách thành `AdminSupportService`

---

## 11. ProductRepository - Xử lý major_code thủ công

**File:** `backend-laravel/app/Repositories/ProductRepository.php`

- Dòng 751-767: Detect major bằng `str_contains()` thủ công (check `'AI'`, `'cntt'`, `'mmt'`, `'tkdh'` trong major name). Logic này lặp lại ở `getVisitorProductById()` và `productViewIdTeacher()`
- **Cần:** Dùng `MajorRepository` hoặc cột `major_code` có sẵn thay vì string matching

---

## 12. JSON response format không đồng nhất

- Một số endpoint trả về `{ success: true, data: {...} }`
- Một số endpoint trả về thẳng object
- `productViewId()` trả về thẳng result object, trong khi `getProductsVisitor()` trả về `{ message, count, products, data, stats }`
- **Cần:** Chuẩn hóa response format toàn bộ API (dùng API Resource class)

---

## 13. BaseRepository không thực sự là repository

**File:** `backend-laravel/app/Repositories/BaseRepository.php`

- Chỉ có 1 method `getCurrentUserId()` gọi `Auth::id()`
- Các Service lại extends `BaseRepository` thay vì dùng trait → thiết kế sai pattern
- **Cần:** Tạo `HasCurrentUser trait` thay vì extends

---

## 14. UserService.php rỗng

**File:** `backend-laravel/app/Services/UserService.php` (0 dòng)

- File được tạo nhưng không có code → dead file

---

## 15. Refresh token không có cleanup

**File:** `backend-laravel/app/Services/AuthService.php::refresh()`

- Dòng 119: `$refreshToken->delete()` — expired/revoked tokens không được dọn dẹp định kỳ
- **Cần:** Thêm cron job hoặc `prune` command để xóa refresh token hết hạn

---

## 16. Thiếu rate limiting trên các endpoint nhạy cảm

**Routes:** `backend-laravel/routes/api.php`

- `POST /upload` — không có rate limit. Sinh viên có thể spam upload
- `POST /v1/visitor/product/{id}/view` — không có rate limit. Có thể view bot
- **Cần:** Thêm `->middleware('throttle:30,1')` cho upload, `throttle:60,1` cho view/like/share

---

## 17. ChatBoxAi sử dụng caching cơ bản

- Chat context không được persist → mỗi request là 1 conversation mới
- OpenAI API được gọi toàn bộ mỗi lần, không có caching cho reply bank
- **Cần:** Cache reply bank, cache context theo session

---

## 18. ContentModeration tích hợp trong UploadService

**File:** `backend-laravel/app/Services/UploadService.php`

- Dòng 81: `$this->contentModeration->moderateUploadedImage()` gọi API moderation ngay trong upload flow → user phải chờ cả AI check lẫn Cloudinary upload
- **Cần:** Xử lý bất đồng bộ (queue job) cho moderation, không block upload

---

## 19. MajorRepository.getMajorCodeByProductId() - N+1

**File:** `backend-laravel/app/Repositories/MajorRepository.php`

- Function này join products → majors để lấy major_code. Được gọi từ ProductRepository mỗi khi cần major_code. Nếu list products, mỗi product gọi 1 lần → N+1
- **Cần:** JOIN sẵn major_code trong query products chính

---

## 20. View/Like/Share không có duplicate protection

**File:** `backend-laravel/app/Repositories/ProductRepository.php`

- `incrementView()`, `incrementLike()`, `incrementShare()` — không check IP/user, không check duplicate. 1 user có thể spam view/like/share không giới hạn
- **Cần:** Thêm IP check hoặc session check, hoặc tối thiểu là rate limiting

---

## 21. Config không có file riêng

- `config('product.duplicate_similarity_threshold', 95)` — tham chiếu đến `config/product.php` nhưng chưa chắc có file này
- **Cần:** Tạo `config/product.php` nếu chưa có, hoặc dùng service provider

---

## 22. Thiếu validation cho team_members JSON

**File:** `backend-laravel/app/Http/Requests/UploadRequest.php`

- `team_members` chỉ là `nullable|string|max:2000` — không validate JSON structure
- `UploadRepository` dùng `preg_split` để parse → dễ sai format
- **Cần:** Validate là JSON array of objects/strings hoặc dùng format cụ thể

---

## 23. Sử dụng DB::table() thay vì Eloquent

- Nhiều repository dùng `DB::table()` (Query Builder) thay vì Eloquent Model
- Mất đi lợi ích của: relationships, events, accessors/mutators, serialization
- **Cần:** Chuyển dần sang Eloquent, chỉ dùng Query Builder cho các query thực sự phức tạp

---

## 24. Không có unit test cho services

- Các service class không có unit test (chỉ có test feature qua HTTP)
- `AuthService`, `ProductService`, `UploadService`, `TeacherService` — logic nghiệp vụ nên được test riêng
- **Cần:** Viết unit test cho từng service method

---

## 25. Không có type hint cho return type

- Nhiều function thiếu `: array`, `: ?array`, `: string` return type
- Ví dụ: `AuthService::login()` trả về array nhưng không có return type
- **Cần:** Thêm return type declarations

---

## 26. PHPStan / Larastan level

- Codebase không có static analysis tool
- Có thể tiềm ẩn nhiều lỗi type mismatch không được phát hiện
- **Cần:** Thêm `larastan` (PHPStan cho Laravel), chạy level 5+

---

## 27. Quản lý dependency injection

- `CloudinaryService::class` được khởi tạo bằng `new CloudinaryService()` trong `UploadService` thay vì DI
- **Cần:** Inject qua constructor

---

## 28. double logging khi AI fail

- `ContentModeration` + `UploadService` đều Log error → log trùng lặp
- **Cần:** Thống nhất chỉ 1 nơi log

---

## Tổng hợp ưu tiên

```plaintext
Priority HIGH (có thể gây crash/security):
├── 1. AuthService - Undefined array key (crash)
├── 16. Thiếu rate limiting trên upload/like/share
├── 20. View/Like/Share không có duplicate protection
├── 4. SystemSettingService - (bool) 'false' bug
└── 25. Thiếu type hints (dẫn đến lỗi runtime)

Priority MEDIUM (performance):
├── 2. ProductRepository - N+1 queries, duplicate code
├── 7. ChatBoxAi file quá lớn (2458 dòng)
├── 10. AdminController file quá lớn (772 dòng)
├── 12. JSON response không đồng nhất
├── 19. MajorRepository.getMajorCodeByProductId N+1
└── 17. ChatBoxAi không caching

Priority LOW (code quality):
├── 14. UserService.php rỗng
├── 24. Thiếu unit test cho services
├── 26. Thiếu static analysis
└── 27. new CloudinaryService() thay vì DI
```

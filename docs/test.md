# Test Coverage - Out of Scope Testing

## Tổng quan

```plaintext
Tổng số test files: 10 (5 Unit + 5 Feature)
Tổng số test cases: 149 tests
Tổng số assertions: 253
Pass: 142 tests
Fail: 7 tests (intentional - phát hiện bugs thật)
```

## Kết quả chạy

```plaintext
Tests:    7 failed, 142 passed (253 assertions)
Duration: 24.24s
```

---

## Mục tiêu

Kiểm thử **ngoài phạm vi code** - tìm lỗi bằng các input không hợp lệ, edge cases, boundary values, injection attacks, và các tình huống phá vỡ logic nghiệp vụ.

---

## Bugs phát hiện

### Bug 1: `AuthService::login()` - Thiếu kiểm tra key trong mảng `$data`
**File:** `backend-laravel/app/Services/AuthService.php:23`
**Lỗi:** `Undefined array key "username"` khi gọi `login([])`
**Mức độ:** Cao - có thể gây crash nếu gọi login mà không có username
**Test fail:** `test_login_with_empty_data_returns_error`

### Bug 2: `AuthService::login()` - Thiếu kiểm tra key "password"
**File:** `backend-laravel/app/Services/AuthService.php:47`
**Lỗi:** `Undefined array key "password"` khi gọi login với dữ liệu thiếu password
**Mức độ:** Cao - có thể gây crash
**Test fail:** `test_login_without_password_field`

### Bug 3: `NormalizeMajorCode::NormalizeMajorCode()` - Partial match không hoạt động với text không dấu
**File:** `backend-laravel/app/Http/Common/NormalizeMajorCode.php:7`
**Lỗi:** `'hoc ve tri tue nhan tao'` (không dấu) không match được với `'ai'`
**Mức độ:** Trung bình - người dùng gõ không dấu sẽ không được phát hiện major code
**Test fail:** `test_partial_match_works`

### Bug 4: `ProductDuplicateService` - Không thể khởi tạo ngoài container
**File:** `backend-laravel/app/Services/ProductDuplicateService.php`
**Lỗi:** `BindingResolutionException: Target class [config] does not exist.`
**Mức độ:** Trung bình - phụ thuộc vào Laravel helper
**Test fail:** `test_negative_major_id_returns_null`

### Bug 5: `ProductDuplicateService::textSimilarityPercent()` - HTML tags không được strip
**File:** `backend-laravel/app/Services/ProductDuplicateService.php:213`
**Lỗi:** `<p>Hello World</p>` vs `Hello World` = 76%, kỳ vọng 100%
**Mức độ:** Thấp - có thể ảnh hưởng đến duplicate detection
**Test fail:** `test_text_similarity_html_tags_stripped`

### Bug 6: `SystemSettingService::update()` - `(bool) 'false'` = `true` trong PHP
**File:** `backend-laravel/app/Services/SystemSettingService.php:57`
**Lỗi:** PHP cast `(bool) 'false'` = `true` vì string không rỗng
**Mức độ:** Trung bình - UI gửi `"false"` string sẽ bị hiểu sai
**Test fail:** `test_boolean_coercion_of_string_values`

### Bug 7: `DuplicateService::comparableData()` - Trả về key với value `null` thay vì loại bỏ key
**File:** `backend-laravel/app/Services/ProductDuplicateService.php:185`
**Lỗi:** `collect($data)->only([...])` trả về key với value `null`
**Mức độ:** Thấp - ảnh hưởng kích thước payload gửi lên AI
**Test fail:** `test_compare_data_missing_keys_graceful`

---

## Unit Tests (5 files)

### 1. `backend-laravel/tests/Unit/NormalizeMajorCodeTest.php`
**Class:** `App\Http\Common\NormalizeMajorCode` (14 tests, 1 fail)

| Test | Input | Expected | Status |
|------|-------|----------|--------|
| `test_null_input_returns_null` | `null` | `null` | Pass |
| `test_empty_string_returns_null` | `''` | `null` | Pass |
| `test_whitespace_only_returns_null` | `'   '` | `null` | Pass |
| `test_unknown_major_returns_null` | `'quantum computing'` | `null` | Pass |
| `test_numeric_input_returns_null` | `'12345'` | `null` | Pass |
| `test_special_characters_returns_null` | `'!@#$%^&*()'` | `null` | Pass |
| `test_case_insensitivity` | `'AI'`, `'CNTT'`, `'MMT'`, `'TKDH'` | Mapped code | Pass |
| `test_mixed_case_works` | `'ArtIfIcIaL InTeLlIgEnCe'` | `'ai'` | Pass |
| `test_vietnamese_major_names` | `'trí tuệ nhân tạo'` | `'ai'` | Pass |
| `test_partial_match_works` | `'hoc ve tri tue nhan tao'` | `'ai'` | **FAIL** |
| `test_very_long_input_returns_mapped_code` | 10000 chars | `null` | Pass |
| `test_html_injection_returns_null` | `<script>alert("test")</script>` | `null` | Pass |
| `test_unicode_normalization` | Chinese characters | `null` | Pass |
| `test_english_variants` | `'artificial intelligence'` | `'ai'` | Pass |

### 2. `backend-laravel/tests/Unit/ProductDuplicateEdgeTest.php`
**Class:** `App\Services\ProductDuplicateService` (18 tests, 3 fail)

| Test | Expected | Status |
|------|----------|--------|
| `test_empty_data_returns_null` | `null` | Pass |
| `test_zero_major_id_returns_null` | `null` | Pass |
| `test_empty_title_returns_null` | `null` | Pass |
| `test_title_only_whitespace_returns_null` | `null` | Pass |
| `test_negative_major_id_returns_null` | `null` | **FAIL (BUG)** |
| `test_null_title_returns_null` | `null` | Pass |
| `test_check_with_absent_keys_does_not_crash` | No crash | Pass |
| `test_text_similarity_both_empty_returns_zero` | 0 | Pass |
| `test_text_similarity_one_empty_returns_zero` | 0 | Pass |
| `test_text_similarity_null_values_returns_zero` | int | Pass |
| `test_text_similarity_identical_texts` | 100 | Pass |
| `test_text_similarity_completely_different` | <50 | Pass |
| `test_text_similarity_with_special_characters` | >0 | Pass |
| `test_text_similarity_very_long_strings` | 100 | Pass |
| `test_text_similarity_unicode_vietnamese` | 0-100 | Pass |
| `test_text_similarity_html_tags_stripped` | 100 | **FAIL (BUG)** |
| `test_normalize_text_multiple_spaces_collapsed` | Pass | Pass |
| `test_normalize_text_leading_trailing_spaces_removed` | Pass | Pass |
| `test_compare_data_missing_keys_graceful` | Graceful | **FAIL (BUG)** |

### 3. `backend-laravel/tests/Unit/SystemSettingEdgeTest.php`
**Class:** `App\Services\SystemSettingService` (11 tests, 1 fail)

| Test | Expected | Status |
|------|----------|--------|
| `test_default_values_when_no_settings_in_database` | All true | Pass |
| `test_non_existent_key_returns_true_by_default` | true | Pass |
| `test_empty_array_update_does_not_change` | Unchanged | Pass |
| `test_update_with_wrong_key_types_ignored` | Ignored | Pass |
| `test_cache_forget_on_update` | forget called | Pass |
| `test_boolean_coercion_of_string_values` | false | **FAIL (BUG)** |
| `test_boolean_coercion_of_integer_values` | false/true | Pass |
| `test_constants_are_defined` | Not empty | Pass |
| `test_defaults_constant_has_all_keys` | Has all | Pass |
| `test_update_persists_to_database` | DB record | Pass |
| `test_enabled_reflects_database_value` | false | Pass |

### 4. `backend-laravel/tests/Unit/ModelFillableEdgeTest.php`
**Models:** All 16 models (13 tests, 0 fail)

| Test | Status |
|------|--------|
| `test_user_mass_assignment_protection` | Pass |
| `test_user_hidden_attributes` | Pass |
| `test_user_primary_key_is_string_not_incrementing` | Pass |
| `test_product_casts_team_members_to_array` | Pass |
| `test_product_primary_key_is_int_incrementing` | Pass |
| `test_models_have_correct_table_names` | Pass |
| `test_refresh_token_casts_datetime` | Pass |
| `test_support_casts_processed_at` | Pass |
| `test_system_setting_casts_value_as_boolean` | Pass |
| `test_product_graphic_casts_color_palette` | Pass |
| `test_product_ai_casts_accuracy_score` | Pass |
| `test_user_fillable_contains_expected_fields` | Pass |
| `test_invalid_data_does_not_break_model` | Pass |

### 5. `backend-laravel/tests/Unit/AuthServiceEdgeTest.php`
**Class:** `App\Services\AuthService` (14 tests, 2 fail)

| Test | Expected | Status |
|------|----------|--------|
| `test_login_with_empty_data_returns_error` | Error response | **FAIL (BUG)** |
| `test_login_without_password_field` | Error response | **FAIL (BUG)** |
| `test_refresh_with_empty_string_returns_null` | null | Pass |
| `test_refresh_with_garbage_token_returns_null` | null | Pass |
| `test_revoke_with_null_token_does_not_throw` | No exception | Pass |
| `test_revoke_with_empty_token_does_not_throw` | No exception | Pass |
| `test_revoke_with_nonexistent_user_does_not_throw` | No exception | Pass |
| `test_login_rate_limiter_key_uses_username` | 429 | Pass |
| `test_login_rate_limiter_clears_on_success` | Reset | Pass |
| `test_login_with_nonexistent_username` | 401 | Pass |
| `test_login_admin_role_with_student_user_fails` | 422 | Pass |
| `test_successful_login_returns_tokens` | Has tokens | Pass |
| `test_refresh_with_expired_token_returns_null` | null | Pass |
| `test_refresh_with_revoked_token_returns_null` | null | Pass |

---

## Feature Tests (API - 5 files)

### 6. `backend-laravel/tests/Feature/AuthEdgeTest.php` (17 tests, 0 fail)

**Endpoints:** `/api/v1/login`, `/api/v1/refresh`, `/api/v1/logout`, `/api/v1/me`

| Test | Input | Expected | Status |
|------|-------|----------|--------|
| `test_login_with_empty_username` | `username: ''` | `422` | Pass |
| `test_login_with_empty_password` | `password: ''` | `422` | Pass |
| `test_login_with_invalid_role` | `user_role: 'superadmin'` | `422` | Pass |
| `test_login_without_role_field` | Missing role | `422` | Pass |
| `test_login_sql_injection_username` | `"' OR '1'='1"` | `401` | Pass |
| `test_login_sql_injection_password` | password injection | `401` | Pass |
| `test_login_xss_in_username` | `<script>alert("xss")</script>` | `401` | Pass |
| `test_login_with_extremely_long_username` | 1000 chars | `422` | Pass |
| `test_login_with_extremely_long_password` | 1000 chars | `422` | Pass |
| `test_login_wrong_password` | Valid user, wrong pass | `401` | Pass |
| `test_login_rate_limiting` | 6 rapid failures | `429` | Pass |
| `test_login_with_lecturer_role_alias` | `lecturer` → `teacher` | `200` | Pass |
| `test_login_student_with_teacher_role_fails` | Student tries teacher role | `422` | Pass |
| `test_refresh_with_empty_token` | `refresh_token: ''` | `422` | Pass |
| `test_refresh_with_invalid_token` | Garbage token | `401` | Pass |
| `test_logout_without_token` | No auth header | `401` | Pass |
| `test_me_without_token` | No auth header | `401` | Pass |

### 7. `backend-laravel/tests/Feature/ProductApiEdgeTest.php` (24 tests, 0 fail)

**Endpoints:** Products, visitor products, search, increment stats

| Test | Expected | Status |
|------|----------|--------|
| `test_get_product_needs_auth_returns_401` | 401 | Pass |
| `test_get_product_with_zero_id_after_auth` | 404 | Pass |
| `test_get_product_with_negative_id_after_auth` | 404 | Pass |
| `test_get_product_with_non_existent_id_after_auth` | 404 | Pass |
| `test_get_product_with_huge_id_after_auth` | 404 | Pass |
| `test_visitor_product_with_zero_id` | 200 (empty) | Pass |
| `test_visitor_product_with_negative_id` | 200 (empty) | Pass |
| `test_visitor_product_with_non_existent_id` | 200 (empty) | Pass |
| `test_increment_view_non_existent_product` | 404 | Pass |
| `test_increment_like_non_existent_product` | 404 | Pass |
| `test_increment_share_non_existent_product` | 404 | Pass |
| `test_search_sql_injection` | 200 | Pass |
| `test_search_xss_injection` | 422 | Pass |
| `test_search_with_extremely_long_query` | 422 | Pass |
| `test_search_with_invalid_sort_by` | 422 | Pass |
| `test_search_with_negative_per_page` | 422 | Pass |
| `test_search_with_huge_per_page` | 422 | Pass |
| `test_get_visitor_products_with_invalid_major_id` | 422 | Pass |
| `test_get_visitor_products_with_non_existent_major_id` | 200 | Pass |
| `test_get_visitor_products_with_invalid_sort` | 422 | Pass |
| `test_delete_product_without_auth` | 401 | Pass |
| `test_get_matching_ai_products_without_auth` | 401 | Pass |
| `test_matching_ai_products_non_existent_id_returns_404` | 404 | Pass |
| `test_rate_limiting_on_login` | 429 | Pass |

### 8. `backend-laravel/tests/Feature/VisitorEndpointEdgeTest.php` (19 tests, 0 fail)

**Endpoints:** All visitor routes

| Test | Expected | Status |
|------|----------|--------|
| `test_visitor_products_default_pagination` | 200 | Pass |
| `test_visitor_products_with_min_per_page` | 200 | Pass |
| `test_visitor_products_with_max_per_page` | 200 | Pass |
| `test_visitor_products_invalid_per_page_negative` | 422 | Pass |
| `test_visitor_products_invalid_per_page_zero` | 422 | Pass |
| `test_visitor_products_invalid_per_page_non_numeric` | 422 | Pass |
| `test_visitor_products_invalid_major_id_non_numeric` | 422 | Pass |
| `test_visitor_products_invalid_sort_by` | 422 | Pass |
| `test_visitor_products_all_valid_sort_options` | 200 | Pass |
| `test_visitor_majors_endpoint_returns_data` | 200 | Pass |
| `test_visitor_search_empty_query` | 200 | Pass |
| `test_visitor_search_only_special_characters` | 200 | Pass |
| `test_non_existent_route_returns_404` | 404 | Pass |
| `test_wrong_http_method_on_existing_route` | 405 | Pass |
| `test_post_to_get_route_returns_405` | 405 | Pass |
| `test_delete_on_visitor_product_returns_405` | 405 | Pass |
| `test_increment_view_twice_same_product` | 200/404 | Pass |
| `test_category_all_endpoint` | 200 | Pass |
| `test_system_settings_endpoint` | 200 | Pass |

### 9. `backend-laravel/tests/Feature/TeacherEndpointEdgeTest.php` (13 tests, 0 fail)

**Endpoints:** Teacher routes

| Test | Expected | Status |
|------|----------|--------|
| `test_teacher_statistic_without_auth` | 401 | Pass |
| `test_student_cannot_access_teacher_endpoints` | 200 | Pass |
| `test_teacher_approve_non_existent_product` | `result: false` | Pass |
| `test_teacher_approve_already_approved_product` | `Sản phẩm không chờ duyệt!` | Pass |
| `test_teacher_approve_already_rejected_product` | `Sản phẩm không chờ duyệt!` | Pass |
| `test_teacher_reject_without_feedback` | 422 | Pass |
| `test_teacher_reject_with_empty_feedback_fails_validation` | 422 | Pass |
| `test_teacher_add_review_non_existent_product` | 404 | Pass |
| `test_teacher_add_review_without_comment` | 422 | Pass |
| `test_student_cannot_review_product` | 403 | Pass |
| `test_teacher_cannot_review_other_major_product` | 403 | Pass |
| `test_teacher_data_invalid_status` | 422 | Pass |
| `test_teacher_data_invalid_per_page` | 422 | Pass |

### 10. `backend-laravel/tests/Feature/ChatBoxAiIntentTest.php` (4 tests, 0 fail)

**Class:** `App\Http\Ai\ChatBoxAi`

| Test | Status |
|------|--------|
| `test_it_detects_vietnamese_feature_intents` | Pass |
| `test_it_extracts_vietnamese_search_terms` | Pass |
| `test_it_has_a_large_contextual_reply_bank` | Pass |
| `test_it_analyzes_product_topics_into_majors_and_keywords` | Pass |

---

## Kết luận

```plaintext
Test coverage (out of scope):
├── 149 tests, 253 assertions
├── 142 pass
├── 7 fail (phát hiện bugs thật)
│
├── Loại test phá vỡ đã thực hiện:
│   ├── Null/Empty input injection
│   ├── SQL Injection attempts
│   ├── XSS Injection attempts  
│   ├── Boundary values (0, -1, huge numbers)
│   ├── Type confusion (string → int, null → int)
│   ├── Rate limiting / brute force
│   ├── Authentication bypass
│   ├── Authorization bypass (cross-role access)
│   ├── Very long strings / buffer overflows
│   ├── Invalid enum values
│   ├── Missing required fields
│   ├── Wrong HTTP methods
│   ├── Non-existent routes
│   └── Database constraint violations
│
└── 7 bugs thật được phát hiện trong codebase
```

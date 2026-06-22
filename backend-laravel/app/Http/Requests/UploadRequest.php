<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Common\NormalizeMajorCode;

class UploadRequest extends FormRequest
{

    public function __construct(
        protected NormalizeMajorCode $normalizeMajorCode
    ) {}

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */


    public function rules(): array
    {
        $rules = [
            // CHUNG
            'title' => 'required|string|min:5|max:100',
            'description' => 'nullable|string|min:10|max:300',

            'cate_id' => 'required|exists:categories,cate_id',

            // đổi từ major_id → major_code
            'major_code' => 'required|string',

            'major_id' => 'required',

            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'image_meta' => 'nullable|array|max:10',
            'image_meta.*' => 'nullable|string',

            'github_link' => 'nullable|url',
            'demo_link' => 'nullable|url',

            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];

        $majorCode = $this->major_code;

        $check  = $this->normalizeMajorCode->NormalizeMajorCode($majorCode);

        switch ($check) {

            case 'ai':
                // DB: NOT NULL
                $rules['model_used'] = 'required|string|max:100';
                $rules['framework'] = 'required|string|max:100';
                $rules['dataset_used'] = 'required|string|max:100';

                // DB: NULLABLE
                $rules['language'] = 'nullable|string|max:50';
                $rules['accuracy_score'] = 'nullable|numeric|min:0|max:100';
                break;

            case 'cntt':
                // DB: tất cả NOT NULL → bắt buộc hết
                $rules['programming_language'] = 'required|string|max:50';
                $rules['framework'] = 'required|string|max:100';
                $rules['database_used'] = 'required|string|max:100';
                break;

            case 'tkdh':
                // DB: NOT NULL
                $rules['design_type'] = 'required|string|max:50';
                $rules['tools_used'] = 'required|string|max:150';

                // DB: NULLABLE
                $rules['drive_link'] = 'nullable|url|max:255';
                $rules['behance_link'] = 'nullable|url|max:255';
                break;

            case 'mmt':
                // DB: NOT NULL
                $rules['network_protocol'] = 'required|string|max:100';
                $rules['topology_type'] = 'required|string|max:50';
                $rules['simulation_tool'] = 'required|string|max:100';
                break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            // TITLE
            'title.required' => 'Vui lòng nhập tên sản phẩm',
            'title.min' => 'Tên phải ≥ 5 ký tự',
            'title.max' => 'Tên tối đa 100 ký tự',

            // DESCRIPTION
            'description.min' => 'Mô tả phải ≥ 10 ký tự',
            'description.max' => 'Mô tả tối đa 300 ký tự',

            // CATEGORY
            'cate_id.required' => 'Chọn danh mục',

            // MAJOR
            'major_code.required' => 'Không xác định được ngành',
            'major_id.required' => 'Thiếu thông tin ngành',

            // IMAGES
            'images.required' => 'Cần ít nhất 1 ảnh',
            'images.min' => 'Cần ít nhất 1 ảnh',
            'images.max' => 'Tối đa 10 ảnh',
            'images.*.image' => 'Tệp đã chọn phải là hình ảnh',
            'images.*.mimes' => 'Ảnh chỉ hỗ trợ định dạng JPG, JPEG, PNG hoặc WEBP',
            'images.*.max' => 'Mỗi ảnh không được vượt quá 5 MB',

            // LINKS
            'github_link.url' => 'Link GitHub không hợp lệ',
            'demo_link.url' => 'Link demo không hợp lệ',

            // ================= AI =================
            'model_used.required' => 'Nhập mô hình hoặc thuật toán sử dụng',
            'model_used.max' => 'Mô hình hoặc thuật toán tối đa 100 ký tự',

            'framework.required' => 'Nhập framework',
            'framework.max' => 'Framework tối đa 100 ký tự',

            'dataset_used.required' => 'Nhập dataset sử dụng',
            'dataset_used.max' => 'Dataset tối đa 100 ký tự',

            'language.max' => 'Ngôn ngữ tối đa 50 ký tự',

            'accuracy_score.numeric' => 'Accuracy phải là số',
            'accuracy_score.min' => 'Accuracy ≥ 0',
            'accuracy_score.max' => 'Accuracy ≤ 100',

            // ================= CNTT =================
            'programming_language.required' => 'Nhập ngôn ngữ lập trình',
            'programming_language.max' => 'Ngôn ngữ tối đa 50 ký tự',

            'framework.required' => 'Nhập framework',
            'framework.max' => 'Framework tối đa 100 ký tự',

            'database_used.required' => 'Nhập database',
            'database_used.max' => 'Database tối đa 100 ký tự',

            // ================= TKĐH =================
            'design_type.required' => 'Chọn loại thiết kế',
            'design_type.max' => 'Loại thiết kế tối đa 50 ký tự',

            'tools_used.required' => 'Nhập công cụ sử dụng',
            'tools_used.max' => 'Công cụ tối đa 150 ký tự',

            'drive_link.url' => 'Link drive không hợp lệ',
            'drive_link.max' => 'Link drive tối đa 255 ký tự',

            'behance_link.url' => 'Link behance không hợp lệ',
            'behance_link.max' => 'Link behance tối đa 255 ký tự',

            // ================= MMT =================
            'simulation_tool.required' => 'Nhập công cụ mô phỏng',
            'simulation_tool.max' => 'Tối đa 100 ký tự',

            'network_protocol.required' => 'Nhập giao thức mạng',
            'network_protocol.max' => 'Tối đa 100 ký tự',

            'topology_type.required' => 'Nhập topology',
            'topology_type.max' => 'Tối đa 50 ký tự',
        ];
    }
}

# DỮ LIỆU DEMO — UPLOAD SẢN PHẨM

> **4 ngành — 1 sản phẩm/ngành — Dùng để test import & ChatBoxAi**

---

## Hướng dẫn sử dụng

1. Insert vào bảng `products` trước → lấy `product_id`
2. Insert vào bảng detail theo ngành (`product_ai` / `product_cntt` / `product_mmt` / `product_graphic`)
3. Insert `product_statistics` (1 dòng/sản phẩm, `product_id` là unique)
4. Insert `product_images` (nhiều dòng/sản phẩm)
5. Insert `product_tags` (nhiều dòng/sản phẩm)
6. Insert `reviews` (nhiều dòng/sản phẩm, `teacher_id`)

---

## 1. Ngành AI — Trí tuệ nhân tạo (major_id = 1)

### 1.1 Bảng `products`

| Field | Value |
|-------|-------|
| title | Hệ thống phát hiện xâm nhập mạng bằng AI |
| description | Hệ thống sử dụng mô hình học máy để phát hiện tấn công mạng từ luồng dữ liệu mạng thực tế. Hỗ trợ cảnh báo sớm và dashboard trực quan. |
| team_members | `["23211AI0101 - Nguyễn Văn A", "23211AI0102 - Trần Thị B"]` |
| thumbnail | `https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg` |
| status | approved |
| user_id | 23211AI2 |
| major_id | 1 |
| cate_id | 1 (Đồ án tốt nghiệp) |
| advisor_name | ThS. Giang vien AI |
| awards | null |
| github_link | `https://github.com/tdc-ai/network-intrusion-detection` |
| demo_link | `https://ai-nids-demo.tdc.edu.vn` |
| submitted_at | 2026-04-15 |
| approved_at | 2026-04-20 |

### 1.2 Bảng `product_ai`

| Field | Value |
|-------|-------|
| product_id | (ID từ bảng products) |
| model_used | Random Forest + LSTM |
| framework | Scikit-learn, TensorFlow |
| language | Python |
| dataset_used | NSL-KDD + CIC-IDS2017 |
| accuracy_score | 96.50 |

### 1.3 Bảng `product_statistics`

| Field | Value |
|-------|-------|
| product_id | (ID từ bảng products) |
| views | 156 |
| likes | 23 |
| downloads | 12 |
| shares | 5 |

### 1.4 Bảng `product_images`

| product_id | image_url |
|------------|-----------|
| (ID từ products) | `https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/8386441/pexels-photo-8386441.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/8386442/pexels-photo-8386442.jpeg` |

### 1.5 Bảng `product_tags`

| product_id | tag_name |
|------------|----------|
| (ID từ products) | IDS |
| (ID từ products) | An ninh mạng |
| (ID từ products) | Machine Learning |
| (ID từ products) | Phát hiện tấn công |

### 1.6 Bảng `reviews`

| product_id | teacher_id | comment |
|------------|------------|---------|
| (ID từ products) | GVAI | Đồ án tốt, mô hình kết hợp Random Forest và LSTM cho độ chính xác cao. Cần bổ sung thêm phần so sánh với các phương pháp khác. |

---

## 2. Ngành CNTT — Công nghệ thông tin (major_id = 2)

### 2.1 Bảng `products`

| Field | Value |
|-------|-------|
| title | Hệ thống quản lý sinh viên thực tập doanh nghiệp |
| description | Nền tảng kết nối sinh viên, giảng viên và doanh nghiệp. Quản lý hồ sơ, vị trí thực tập, nhật ký, báo cáo và đánh giá kết quả thực tập. |
| team_members | `["23211CNTT0201 - Lê Văn C", "23211CNTT0202 - Phạm Thị D", "23211CNTT0203 - Hoàng Văn E"]` |
| thumbnail | `https://images.pexels.com/photos/1181671/pexels-photo-1181671.jpeg` |
| status | approved |
| user_id | 23211CNTT1 |
| major_id | 2 |
| cate_id | 2 (Nghiên cứu khoa học) |
| advisor_name | ThS. Giang vien CNTT |
| awards | Sản phẩm tiêu biểu cấp khoa |
| github_link | `https://github.com/tdc-cntt/internship-management` |
| demo_link | `https://internship.tdc.edu.vn` |
| submitted_at | 2026-05-10 |
| approved_at | 2026-05-15 |

### 2.2 Bảng `product_cntt`

| Field | Value |
|-------|-------|
| product_id | (ID từ bảng products) |
| programming_language | PHP, JavaScript |
| framework | Laravel, React |
| database_used | MySQL |

### 2.3 Bảng `product_statistics`

| Field | Value |
|-------|-------|
| product_id | (ID từ bảng products) |
| views | 234 |
| likes | 45 |
| downloads | 28 |
| shares | 15 |

### 2.4 Bảng `product_images`

| product_id | image_url |
|------------|-----------|
| (ID từ products) | `https://images.pexels.com/photos/1181671/pexels-photo-1181671.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/270404/pexels-photo-270404.jpeg` |

### 2.5 Bảng `product_tags`

| product_id | tag_name |
|------------|----------|
| (ID từ products) | Thực tập |
| (ID từ products) | Doanh nghiệp |
| (ID từ products) | Laravel |
| (ID từ products) | Quản lý |

### 2.6 Bảng `reviews`

| product_id | teacher_id | comment |
|------------|------------|---------|
| (ID từ products) | GVCNTT | Hệ thống hoàn chỉnh, quy trình nghiệp vụ rõ ràng. Cần tối ưu thêm hiệu năng phần tìm kiếm. |

---

## 3. Ngành MMT — Mạng máy tính (major_id = 3)

### 3.1 Bảng `products`

| Field | Value |
|-------|-------|
| title | Triển khai hệ thống mạng doanh nghiệp đa chi nhánh với bảo mật nâng cao |
| description | Mô hình kết nối 3 chi nhánh qua VPN, phân vùng mạng bằng VLAN, định tuyến OSPF, giám sát tập trung với Zabbix. Áp dụng Zero Trust cho phân quyền truy cập nội bộ. |
| team_members | `["23211MMT0301 - Võ Thị F", "23211MMT0302 - Đặng Văn G"]` |
| thumbnail | `https://images.pexels.com/photos/325229/pexels-photo-325229.jpeg` |
| status | approved |
| user_id | 23211MMT3 |
| major_id | 3 |
| cate_id | 3 (Thực tập doanh nghiệp) |
| advisor_name | ThS. Giang vien MMT |
| awards | null |
| github_link | `https://github.com/tdc-mmt/enterprise-network-zero-trust` |
| demo_link | null |
| submitted_at | 2026-03-20 |
| approved_at | 2026-03-25 |

### 3.2 Bảng `product_mmt`

| Field | Value |
|-------|-------|
| product_id | (ID từ bảng products) |
| network_protocol | VLAN, OSPF, IPsec, SNMP, 802.1X |
| topology_type | Hybrid Star |
| simulation_tool | Cisco Packet Tracer, GNS3, EVE-NG |
| config_file | null |

### 3.3 Bảng `product_statistics`

| Field | Value |
|-------|-------|
| product_id | (ID từ bảng products) |
| views | 89 |
| likes | 12 |
| downloads | 8 |
| shares | 3 |

### 3.4 Bảng `product_images`

| product_id | image_url |
|------------|-----------|
| (ID từ products) | `https://images.pexels.com/photos/325229/pexels-photo-325229.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/2588757/pexels-photo-2588757.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/442150/pexels-photo-442150.jpeg` |

### 3.5 Bảng `product_tags`

| product_id | tag_name |
|------------|----------|
| (ID từ products) | VPN |
| (ID từ products) | Zero Trust |
| (ID từ products) | OSPF |
| (ID từ products) | Zabbix |

### 3.6 Bảng `reviews`

| product_id | teacher_id | comment |
|------------|------------|---------|
| (ID từ products) | GVMMT | Thiết kế mạng chi tiết, đầy đủ các tầng bảo mật. Mô hình Zero Trust được áp dụng phù hợp. |

---

## 4. Ngành TKDH — Thiết kế đồ họa (major_id = 4)

### 4.1 Bảng `products`

| Field | Value |
|-------|-------|
| title | Bộ nhận diện thương hiệu Cà phê Việt truyền thống |
| description | Hệ thống nhận diện thương hiệu gồm logo, ấn phẩm, bao bì và template mạng xã hội. Thiết kế lấy cảm hứng từ văn hóa cà phê Việt Nam, kết hợp chất liệu thủ công và hiện đại. |
| team_members | `["23211TKDH0401 - Ngô Thị H"]` |
| thumbnail | `https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg` |
| status | approved |
| user_id | 23211TKDH4 |
| major_id | 4 |
| cate_id | 5 (Thiết kế sáng tạo) |
| advisor_name | ThS. Giang vien Thiet ke do hoa |
| awards | Giải nhất thiết kế đồ họa cấp trường |
| github_link | null |
| demo_link | `https://behance.net/tdc/viet-coffee-branding` |
| submitted_at | 2026-06-01 |
| approved_at | 2026-06-05 |

### 4.2 Bảng `product_graphic`

| Field | Value |
|-------|-------|
| product_id | (ID từ bảng products) |
| design_type | Brand Identity |
| tools_used | Adobe Illustrator, Adobe Photoshop, Adobe InDesign |
| color_palette | `["#3D2B1F", "#B77945", "#F3E9DC", "#2F5D50"]` |
| behance_link | `https://behance.net/tdc/viet-coffee-branding` |

### 4.3 Bảng `product_statistics`

| Field | Value |
|-------|-------|
| product_id | (ID từ bảng products) |
| views | 412 |
| likes | 67 |
| downloads | 0 |
| shares | 34 |

### 4.4 Bảng `product_images`

| product_id | image_url |
|------------|-----------|
| (ID từ products) | `https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/1779487/pexels-photo-1779487.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/4348404/pexels-photo-4348404.jpeg` |
| (ID từ products) | `https://images.pexels.com/photos/326503/pexels-photo-326503.jpeg` |

### 4.5 Bảng `product_tags`

| product_id | tag_name |
|------------|----------|
| (ID từ products) | Branding |
| (ID từ products) | Logo |
| (ID từ products) | Bao bì |
| (ID từ products) | Nhận diện thương hiệu |

### 4.6 Bảng `reviews`

| product_id | teacher_id | comment |
|------------|------------|---------|
| (ID từ products) | GVTKDH | Sản phẩm đầu tư chỉn chu, màu sắc hài hòa, bộ nhận diện có tính ứng dụng cao. Đạt giải nhất cấp trường xứng đáng. |
| (ID từ products) | GVCNTT | Thiết kế đẹp, thể hiện được bản sắc văn hóa Việt. Phần mockup sản phẩm rất thuyết phục. |

---

## File ảnh (thumbnails) — Pexels

### AI
- `https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg`
- `https://images.pexels.com/photos/8386441/pexels-photo-8386441.jpeg`
- `https://images.pexels.com/photos/8386442/pexels-photo-8386442.jpeg`

### CNTT
- `https://images.pexels.com/photos/1181671/pexels-photo-1181671.jpeg`
- `https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg`
- `https://images.pexels.com/photos/270404/pexels-photo-270404.jpeg`

### MMT
- `https://images.pexels.com/photos/325229/pexels-photo-325229.jpeg`
- `https://images.pexels.com/photos/2588757/pexels-photo-2588757.jpeg`
- `https://images.pexels.com/photos/442150/pexels-photo-442150.jpeg`

### TKDH
- `https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg`
- `https://images.pexels.com/photos/1779487/pexels-photo-1779487.jpeg`
- `https://images.pexels.com/photos/4348404/pexels-photo-4348404.jpeg`
- `https://images.pexels.com/photos/326503/pexels-photo-326503.jpeg`

# 🎓 Hệ thống Quản lý và Trưng bày Sản phẩm Khoa CNTT

<div align="center">

![Banner](https://img.shields.io/badge/Trường-CĐ%20Công%20nghệ%20Thủ%20Đức-0066CC?style=for-the-badge)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![React](https://img.shields.io/badge/ReactJS-19-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![MySQL](https://img.shields.io/badge/MySQL-8.2-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-ver2496ED?style=for-the-badge&logo=docker&logoColor=white)

**Đề tài nghiên cứu khoa học – Khoa Công nghệ Thông tin**  
Trường Cao đẳng Công nghệ Thủ Đức (TDC)

</div>

---
website: https://tvg.adtech.pro.vn/nckh-visitor

## 📌 Giới thiệu

Hệ thống được xây dựng nhằm **quản lý, lưu trữ và trưng bày sản phẩm học tập** của sinh viên Khoa Công nghệ Thông tin, bao gồm đồ án, dự án, sản phẩm thiết kế và phần mềm.

### Vấn đề giải quyết

Hiện tại, sản phẩm của sinh viên Khoa CNTT chưa được lưu trữ tập trung, dẫn đến:

- Khó tìm kiếm và tham khảo sản phẩm theo chuyên ngành
- Thiếu nền tảng để quảng bá hoạt động đào tạo của nhà trường
- Giảng viên mất nhiều thời gian quản lý và duyệt nội dung thủ công

---

## 🚀 Công nghệ sử dụng

| Thành phần | Công nghệ | Phiên bản |
|---|---|---|
| Backend API | Laravel | 12.x |
| Frontend | ReactJS | 19.x |
| Database | MySQL | 8.2 |
| Container | Docker + Docker Compose ver3 | latest |
| Môi trường ảo | Vagrant + VirtualBox | – |
| AI Integration | gpt-4.1-mini |

---

## ✨ Tính năng chính

### 👤 Sinh viên
- Đăng tải sản phẩm học tập (đồ án, dự án, phần mềm, thiết kế)
- Tìm kiếm và tham khảo sản phẩm của các khoá trước
- Cập nhật thông tin và hình ảnh sản phẩm

### 👨‍🏫 Giảng viên
- Duyệt và phê duyệt sản phẩm trước khi công bố
- Phản hồi và nhận xét nội dung sản phẩm
- Quản lý sản phẩm theo lớp, khoá học

### 🛠️ Quản trị viên
- Quản lý toàn bộ hệ thống
- Phân quyền người dùng (sinh viên / giảng viên / admin)
- Quản lý danh mục, thống kê, xuất báo cáo

### 🤖 Tính năng AI tích hợp
- **AI Search** – Tìm kiếm ngôn ngữ tự nhiên, không cần từ khoá chính xác
- **AI Chatbox** – Hỗ trợ tra cứu, tìm kiếm và giải đáp thắc mắc
- **Kiểm tra ảnh AI** – Phân tích ảnh sản phẩm: chất lượng, nội dung phù hợp, vi phạm bản quyền
- **AI Auto-tagging** – Tự động gợi ý danh mục và tag kỹ thuật khi đăng sản phẩm
- **AI Review Assist** – Hỗ trợ giảng viên tóm tắt và đề xuất phản hồi

---

## 🏗️ Kiến trúc hệ thống

```
┌─────────────────────────────────────────────────┐
│                   Client Browser                 │
│              ReactJS (Port 3000)                 │
└──────────────────────┬──────────────────────────┘
                       │ HTTP / REST API
┌──────────────────────▼──────────────────────────┐
│              Laravel API (Port 8000)             │
│         Authentication · Business Logic          │
│              Anthropic Claude API                │
└──────────┬───────────────────────┬──────────────┘
           │                       │
┌──────────▼──────┐     ┌──────────▼──────────────┐
│  MySQL Database │     │   File Storage (local /  │
│   (Port 3306)   │     │       S3-compatible)     │
└─────────────────┘     └─────────────────────────┘

Môi trường: Docker Compose · Vagrant + VirtualBox
```

---

## 📁 Cấu trúc project

```bash
├── backend-laravel/                 # Laravel 12 API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Ai/                  # AI xử lý nội dung / vision
│   │   │   ├── Common/              # Helpers / constants / utils
│   │   │   ├── Controllers/         # API Controllers
│   │   │   ├── Middleware/          # Middleware
│   │   │   ├── Requests/            # Validate Requests
│   │   │   └── Resources/           # API Resources
│   │   │
│   │   ├── Models/                  # Eloquent Models
│   │   ├── Providers/               # Service Providers
│   │   ├── Repositories/            # Database layer
│   │   └── Services/                # Business Logic Services
│   │
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   │
│   └── routes/
│       └── api.php
│
├── frontend-reactjs/                # ReactJS Frontend 19 UI 
│   ├── src/
│   │   ├── api/                     # Axios / API configs
│   │   ├── assets/                  # Images / icons / styles
│   │   ├── components/              # Shared components
│   │   ├── contexts/                # React contexts
│   │   ├── hooks/                   # Custom hooks
│   │   ├── layouts/                 # Layout components
│   │   ├── pages/                   # Application pages
│   │   │   ├── admin/               # Admin pages
│   │   │   ├── student/             # Student pages
│   │   │   └── teacher/             # Teacher pages
│   │   │
│   │   ├── routes/                  # React Router config
│   │   ├── shared/                  # Shared constants / configs
│   │   ├── utils/                   # Utility functions
│   │   │
│   │   ├── App.jsx                  # Root component
│   │   ├── App.css                  # Global app styles
│   │   ├── index.css                # Base styles
│   │   └── main.jsx                 # Entry point
│   │
│   └── public/
│
├── docker-compose.yml               # Docker services config
├── Vagrantfile                      # Vagrant VM config
└── README.md
```

---

## ⚙️ Hướng dẫn cài đặt

### Yêu cầu hệ thống

- Docker Desktop ≥ 24.x
- Node.js ≥ 18.x
- PHP ≥ 8.2
- Composer ≥ 2.x

### 1. Clone repository

```bash
git clone https://github.com/<your-username>/<repo-name>.git
cd <repo-name>
```

### 2. Cấu hình môi trường

```bash
# Backend
cp backend/.env.example backend/.env

# Điền thông tin trong backend/.env:
# DB_DATABASE=cntt_products
# DB_USERNAME=root
# DB_PASSWORD=secret
# ANTHROPIC_API_KEY=sk-ant-...
```

### 3. Khởi động với Docker

```bash
docker-compose up -d
```

### 4. Khởi tạo database

```bash
docker-compose exec backend php artisan migrate --seed
```

### 5. Truy cập ứng dụng

| Service | URL |
|---|---|
| Frontend (ReactJS) | http://192.168.33.11:5173 |
| Backend API | http://192.168.33.11:8000/api |
| phpMyAdmin | http://192.168.33.11:8080 |

### Tùy chọn: Dùng Vagrant

```bash
vagrant up
vagrant ssh
cd /vagrant
```

---

## 🔌 API Endpoints chính

```
GET    /api/products          # Danh sách sản phẩm
POST   /api/products          # Đăng sản phẩm mới
GET    /api/products/{id}     # Chi tiết sản phẩm
PUT    /api/products/{id}     # Cập nhật sản phẩm
DELETE /api/products/{id}     # Xoá sản phẩm
PATCH  /api/products/{id}/approve   # Duyệt sản phẩm
PATCH  /api/products/{id}/reject    # Từ chối sản phẩm

POST   /api/ai/chat           # AI Chatbox
POST   /api/ai/scan-image     # Kiểm tra ảnh AI
POST   /api/ai/suggest-tags   # Gợi ý danh mục

GET    /api/stats             # Thống kê hệ thống
GET    /api/export/excel      # Xuất Excel
GET    /api/export/pdf        # Xuất PDF
```

---

## 📊 Thống kê demo

| Chỉ số | Giá trị |
|---|---|
| Tổng sản phẩm | 247 |
| Sinh viên đăng tải | 134 |
| Chờ duyệt | 18 |
| Tỷ lệ hợp lệ AI | 91% |

---


| Họ tên | Vai trò | MSSV |
|---|---|---|
| Nguyễn Văn A | Trưởng nhóm · Backend | XXXXXXXX |
| Trần Thị B | Frontend · UI/UX | XXXXXXXX |
| Lê Văn C | AI Integration · DevOps | XXXXXXXX |

> **Giảng viên hướng dẫn:** ...

---

## 📄 Giấy phép

Đề tài nghiên cứu khoa học – Khoa CNTT, Trường CĐ Công nghệ Thủ Đức.  
Không sử dụng cho mục đích thương mại.

---

<div align="center">
  Made with ❤️ by Tran Viet Giang
</div>

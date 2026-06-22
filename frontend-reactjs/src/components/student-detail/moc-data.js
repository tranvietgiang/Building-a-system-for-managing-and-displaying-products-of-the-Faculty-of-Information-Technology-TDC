// mockData.js

// Data mẫu cho ngành AI (Trí tuệ nhân tạo)
export const mockAiProduct = {
  product_id: "AI001",
  title: "Hệ thống nhận diện khuôn mặt thông minh với CNN",
  description:
    "Đề tài nghiên cứu và xây dựng hệ thống nhận diện khuôn mặt sử dụng mạng nơ-ron tích chập (CNN) với độ chính xác cao. Hệ thống có thể nhận diện khuôn mặt trong điều kiện ánh sáng khác nhau và góc nghiêng đa dạng. Ứng dụng trong việc chấm công tự động, an ninh giám sát.",
  thumbnail: "https://picsum.photos/id/1/800/450",
  status: "approved",
  awards:
    "Giải Nhất NCKH cấp trường 2024, Giải Ba cuộc thi Sinh viên với AI Toàn quốc",
  github_link: "https://github.com/example/face-recognition-ai",
  demo_link: "https://demo-face-ai.vercel.app",
  submitted_at: "2024-11-15",
  approved_at: "2024-11-20",
  created_at: "2024-11-10T08:00:00Z",
  updated_at: "2024-11-20T10:30:00Z",
  user_id: "SV2024001",
  fullname: "Nguyễn Văn An",

  major: {
    major_id: "MAJ001",
    major_name: "Trí tuệ nhân tạo",
    major_code: "AI",
  },
  category: {
    cate_id: "CAT001",
    name: "Xử lý ảnh",
  },
  approved_by_user: {
    fullname: "TS. Trần Thị Bình",
  },
  activity_logs: {
    views: 245,
    shares: 32,
  },
  images: [
    { product_image_id: 1, image_url: "https://picsum.photos/id/2/400/400" },
    { product_image_id: 2, image_url: "https://picsum.photos/id/3/400/400" },
    { product_image_id: 3, image_url: "https://picsum.photos/id/4/400/400" },
  ],
  tags: [
    { tag_id: 1, tag_name: "Machine Learning" },
    { tag_id: 2, tag_name: "CNN" },
    { tag_id: 3, tag_name: "Python" },
    { tag_id: 4, tag_name: "TensorFlow" },
  ],
  reviews: [
    {
      review_id: 1,
      teacher: { fullname: "TS. Trần Thị Bình" },
      comment:
        "Sản phẩm rất ấn tượng, model hoạt động tốt. Có thể phát triển thêm tính năng nhận diện cảm xúc.",
      created_at: "2024-11-21T09:00:00Z",
    },
    {
      review_id: 2,
      teacher: { fullname: "PGS. Lê Văn Cường" },
      comment:
        "Dataset đa dạng, độ chính xác cao. Nên cải thiện thời gian xử lý real-time.",
      created_at: "2024-11-22T14:30:00Z",
    },
  ],
  // AI specific fields
  ai_detail: {
    model_used: "ResNet-50 + VGG16",
    framework: "TensorFlow 2.15, PyTorch 2.0",
    language: "Python 3.10",
    dataset_used: "FaceScrub + LFW (15,000 images)",
    accuracy_score: 96.8,
  },
};

// Data mẫu cho ngành CNTT
export const mockItProduct = {
  product_id: "CNTT001",
  title: "Xây dựng hệ thống quản lý bán hàng đa kênh",
  description:
    "Hệ thống quản lý bán hàng tích hợp đa kênh: website, Facebook, Shopee, TikTok Shop. Hỗ trợ quản lý kho, đơn hàng, khách hàng, báo cáo doanh thu real-time. Có tính năng tự động đồng bộ tồn kho và xử lý đơn hàng thông minh.",
  thumbnail: "https://picsum.photos/id/20/800/450",
  status: "approved",
  awards: "Giải thưởng Sinh viên NCKH toàn quốc 2024",
  github_link: "https://github.com/example/ecommerce-system",
  demo_link: "https://demo-ecommerce.vercel.app",
  submitted_at: "2024-12-01",
  approved_at: "2024-12-05",
  created_at: "2024-11-25T08:00:00Z",
  updated_at: "2024-12-05T10:30:00Z",
  user_id: "SV2024002",
  fullname: "Trần Thị Lan",
  major: {
    major_id: "MAJ002",
    major_name: "Công nghệ thông tin",
    major_code: "IT",
  },
  category: {
    cate_id: "CAT002",
    name: "Web Development",
  },
  approved_by_user: {
    fullname: "TS. Phạm Văn Dũng",
  },
  activity_logs: {
    views: 189,
    shares: 28,
  },
  images: [
    { product_image_id: 4, image_url: "https://picsum.photos/id/21/400/400" },
    { product_image_id: 5, image_url: "https://picsum.photos/id/22/400/400" },
    { product_image_id: 6, image_url: "https://picsum.photos/id/23/400/400" },
  ],
  tags: [
    { tag_id: 5, tag_name: "ReactJS" },
    { tag_id: 6, tag_name: "NodeJS" },
    { tag_id: 7, tag_name: "MongoDB" },
    { tag_id: 8, tag_name: "Express" },
  ],
  reviews: [
    {
      review_id: 3,
      teacher: { fullname: "TS. Phạm Văn Dũng" },
      comment:
        "Hệ thống hoạt động ổn định, giao diện thân thiện. Nên thêm tính năng phân tích dữ liệu bán hàng.",
      created_at: "2024-12-06T10:00:00Z",
    },
  ],
  // CNTT specific fields
  it_detail: {
    programming_language: "JavaScript/TypeScript",
    framework: "ReactJS + NodeJS/Express",
    database_used: "MongoDB + Redis",
  },
};

// Data mẫu cho ngành Graphic Design
export const mockGraphicProduct = {
  product_id: "GD001",
  title:
    "Bộ nhận diện thương hiệu GreenLife - Thiết kế bao bì thân thiện môi trường",
  description:
    "Dự án thiết kế bộ nhận diện thương hiệu cho sản phẩm túi giấy, ống hút tre, hộp đựng thực phẩm từ bã mía. Sử dụng tông màu xanh lá và nâu đất, thể hiện thông điệp bảo vệ môi trường. Sản phẩm đã được ứng dụng thực tế cho 5 doanh nghiệp.",
  thumbnail: "https://picsum.photos/id/30/800/450",
  status: "approved",
  awards: "Giải Vàng thiết kế sinh viên 2024, Top 10 cuộc thi Logo Việt Nam",
  submitted_at: "2024-10-20",
  approved_at: "2024-10-25",
  created_at: "2024-10-15T08:00:00Z",
  updated_at: "2024-10-25T10:30:00Z",
  user_id: "SV2024003",
  fullname: "Lê Minh Hoàng",
  major: {
    major_id: "MAJ003",
    major_name: "Thiết kế đồ họa",
    major_code: "GD",
  },
  category: {
    cate_id: "CAT003",
    name: "Brand Identity",
  },
  approved_by_user: {
    fullname: "ThS. Nguyễn Thị Hồng",
  },
  activity_logs: {
    views: 567,
    shares: 89,
  },
  images: [
    { product_image_id: 7, image_url: "https://picsum.photos/id/31/400/400" },
    { product_image_id: 8, image_url: "https://picsum.photos/id/32/400/400" },
    { product_image_id: 9, image_url: "https://picsum.photos/id/33/400/400" },
    { product_image_id: 10, image_url: "https://picsum.photos/id/34/400/400" },
  ],
  tags: [
    { tag_id: 9, tag_name: "Logo Design" },
    { tag_id: 10, tag_name: "Packaging" },
    { tag_id: 11, tag_name: "Illustrator" },
    { tag_id: 12, tag_name: "Photoshop" },
  ],
  reviews: [
    {
      review_id: 4,
      teacher: { fullname: "ThS. Nguyễn Thị Hồng" },
      comment:
        "Bộ nhận diện đẹp, ý tưởng sáng tạo, phù hợp với xu hướng xanh. Có thể phát triển thêm các ấn phẩm truyền thông.",
      created_at: "2024-10-26T14:00:00Z",
    },
    {
      review_id: 5,
      teacher: { fullname: "TS. Trần Văn Minh" },
      comment: "Màu sắc hài hòa, bố cục tốt. Ứng dụng thực tế hiệu quả.",
      created_at: "2024-10-27T09:30:00Z",
    },
  ],
  // Graphic specific fields
  graphic_detail: {
    design_type: "Brand Identity, Packaging Design",
    tools_used: "Adobe Illustrator, Photoshop, After Effects",
    behance_link: "https://behance.net/gallery/example",
  },
};

// Data mẫu cho ngành Network/Mạng máy tính
export const mockNetworkProduct = {
  product_id: "NET001",
  title: "Mô phỏng và tối ưu mạng SDN cho trung tâm dữ liệu",
  description:
    "Dự án mô phỏng mạng SDN (Software-Defined Networking) cho trung tâm dữ liệu sử dụng phần mềm Mininet và Ryu controller. Tối ưu hóa lưu lượng và cân bằng tải cải thiện hiệu suất mạng lên 35%. Hỗ trợ bảo mật với các policy kiểm soát truy cập.",
  thumbnail: "https://picsum.photos/id/40/800/450",
  status: "approved",
  awards: "Giải Nhất NCKH cấp Khoa 2024",
  submitted_at: "2024-09-10",
  approved_at: "2024-09-15",
  created_at: "2024-09-05T08:00:00Z",
  updated_at: "2024-09-15T10:30:00Z",
  user_id: "SV2024004",
  fullname: "Phạm Quốc Bảo",
  major: {
    major_id: "MAJ004",
    major_name: "Mạng máy tính",
    major_code: "NET",
  },
  category: {
    cate_id: "CAT004",
    name: "Network Simulation",
  },
  approved_by_user: {
    fullname: "TS. Lê Hoàng Nam",
  },
  activity_logs: {
    views: 134,
    shares: 17,
  },
  images: [
    { product_image_id: 11, image_url: "https://picsum.photos/id/41/400/400" },
    { product_image_id: 12, image_url: "https://picsum.photos/id/42/400/400" },
    { product_image_id: 13, image_url: "https://picsum.photos/id/43/400/400" },
  ],
  tags: [
    { tag_id: 13, tag_name: "SDN" },
    { tag_id: 14, tag_name: "Mininet" },
    { tag_id: 15, tag_name: "Python" },
    { tag_id: 16, tag_name: "Ryu Controller" },
    { tag_id: 17, tag_name: "OpenFlow" },
  ],
  reviews: [
    {
      review_id: 6,
      teacher: { fullname: "TS. Lê Hoàng Nam" },
      comment:
        "Mô phỏng chính xác, giải pháp tối ưu hiệu quả. Có thể mở rộng áp dụng cho mạng lớn hơn.",
      created_at: "2024-09-16T11:00:00Z",
    },
  ],
  // Network specific fields
  network_detail: {
    simulation_tool: "Mininet, Ryu Controller",
    network_protocol: "OpenFlow 1.3, TCP/IP",
    topology_type: "Fat-Tree với 16 switch",
    config_file: "https://example.com/sdn_config.py",
  },
};

// Product đang chờ duyệt - mẫu
export const mockPendingProduct = {
  product_id: "PEN001",
  title: "Ứng dụng di động quản lý chi tiêu cá nhân (AI - Chờ duyệt)",
  description:
    "App quản lý chi tiêu sử dụng AI đề xuất ngân sách thông minh và dự đoán xu hướng chi tiêu.",
  thumbnail: "https://picsum.photos/id/50/800/450",
  status: "pending",
  awards: null,
  github_link: "https://github.com/example/personal-finance",
  demo_link: "https://demo-finance.vercel.app",
  submitted_at: "2024-12-20",
  approved_at: null,
  created_at: "2024-12-18T08:00:00Z",
  updated_at: "2024-12-20T10:30:00Z",
  user_id: "SV2024005",
  fullname: "Hoàng Thị Mai",
  major: {
    major_id: "MAJ001",
    major_name: "Trí tuệ nhân tạo",
    major_code: "AI",
  },
  category: {
    cate_id: "CAT005",
    name: "Mobile App",
  },
  approved_by_user: null,
  activity_logs: {
    views: 45,
    shares: 5,
  },
  images: [
    { product_image_id: 14, image_url: "https://picsum.photos/id/51/400/400" },
  ],
  tags: [
    { tag_id: 18, tag_name: "React Native" },
    { tag_id: 19, tag_name: "AI" },
    { tag_id: 20, tag_name: "Financial Tech" },
  ],
  reviews: [],
  ai_detail: {
    model_used: "LSTM Neural Network",
    framework: "TensorFlow Lite",
    language: "Python, JavaScript",
    dataset_used: "Personal Finance Dataset",
    accuracy_score: 89.5,
  },
};

// Product bị từ chối - mẫu
export const mockRejectedProduct = {
  product_id: "REJ001",
  title: "Website giới thiệu sản phẩm đơn giản (CNTT - Bị từ chối)",
  description:
    "Website giới thiệu sản phẩm cơ bản chưa hoàn thiện, thiếu tính năng tương tác và responsive.",
  thumbnail: "https://picsum.photos/id/60/800/450",
  status: "rejected",
  awards: null,
  github_link: "https://github.com/example/basic-website",
  demo_link: null,
  submitted_at: "2024-12-10",
  approved_at: null,
  created_at: "2024-12-08T08:00:00Z",
  updated_at: "2024-12-08T10:30:00Z",
  user_id: "SV2024006",
  fullname: "Đỗ Văn Tuấn",
  major: {
    major_id: "MAJ002",
    major_name: "Công nghệ thông tin",
    major_code: "IT",
  },
  category: {
    cate_id: "CAT002",
    name: "Web Development",
  },
  approved_by_user: {
    fullname: "TS. Phạm Văn Dũng",
  },
  activity_logs: {
    views: 23,
    shares: 1,
  },
  images: [],
  tags: [
    { tag_id: 21, tag_name: "HTML/CSS" },
    { tag_id: 22, tag_name: "JavaScript" },
  ],
  reviews: [
    {
      review_id: 7,
      teacher: { fullname: "TS. Phạm Văn Dũng" },
      comment:
        "Sản phẩm chưa đáp ứng yêu cầu về chất lượng và tính năng. Cần bổ sung responsive và hoàn thiện giao diện trước khi nộp lại.",
      created_at: "2024-12-12T09:00:00Z",
    },
  ],
  it_detail: {
    programming_language: "JavaScript",
    framework: "None",
    database_used: "None",
  },
};

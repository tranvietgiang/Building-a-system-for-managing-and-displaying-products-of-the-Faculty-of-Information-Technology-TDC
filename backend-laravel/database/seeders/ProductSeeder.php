<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Major;
use App\Models\Product;
use App\Models\ProductAi;
use App\Models\ProductCNTT;
use App\Models\ProductGraphic;
use App\Models\ProductMMT;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['reviews', 'product_tags', 'product_images', 'product_statistics', 'product_ai', 'product_cntt', 'product_mmt', 'product_graphic'] as $table) {
                DB::table($table)->delete();
            }
            DB::table('products')->delete();

            foreach ($this->catalog() as $majorCode => $definitions) {
                $this->seedMajor($majorCode, $definitions);
            }
        });

        $this->command->info('Đã tạo 80 sản phẩm chất lượng (20 sản phẩm/ngành).');
    }

    private function seedMajor(string $majorCode, array $definitions): void
    {
        $major = Major::where('major_code', $majorCode)->firstOrFail();
        $student = User::where('role', 'student')->where('major_id', $major->major_id)->firstOrFail();
        $teacher = User::where('role', 'teacher')->where('major_id', $major->major_id)->firstOrFail();
        $categories = Category::pluck('cate_id', 'category_name');

        foreach ($definitions as $index => $definition) {
            $status = $index < 16 ? 'approved' : ($index < 18 ? 'pending' : 'rejected');
            $submittedAt = now()->subDays(80 - $index);
            $categoryName = $majorCode === 'TKDH'
                ? ($index % 4 === 0 ? 'Ứng dụng thực tế' : 'Thiết kế sáng tạo')
                : ['Đồ án tốt nghiệp', 'Nghiên cứu khoa học', 'Ứng dụng thực tế'][$index % 3];
            $images = ProductSeedImages::for($majorCode, $index);

            $product = Product::create([
                'title' => $definition[0],
                'description' => $definition[1],
                'team_members' => [sprintf('23211%s%04d - Thành viên nhóm %02d', $majorCode, $index + 1, $index + 1)],
                'thumbnail' => $images[0],
                'status' => $status,
                'user_id' => $student->user_id,
                'major_id' => $major->major_id,
                'cate_id' => $categories[$categoryName],
                'approved_by' => $status === 'approved' ? $teacher->user_id : null,
                'advisor_name' => 'ThS. ' . $teacher->name,
                'awards' => $index % 7 === 0 ? 'Sản phẩm tiêu biểu cấp khoa' : null,
                'submitted_at' => $submittedAt,
                'approved_at' => $status === 'approved' ? (clone $submittedAt)->addDays(5) : null,
            ]);

            match ($majorCode) {
                'AI' => ProductAi::create([
                    'product_id' => $product->product_id,
                    'model_used' => $definition[2],
                    'framework' => $definition[3],
                    'language' => $definition[4],
                    'dataset_used' => $definition[5],
                    'accuracy_score' => $definition[6],
                ]),
                'CNTT' => ProductCNTT::create([
                    'product_id' => $product->product_id,
                    'programming_language' => $definition[2],
                    'framework' => $definition[3],
                    'database_used' => $definition[4],
                ]),
                'MMT' => ProductMMT::create([
                    'product_id' => $product->product_id,
                    'simulation_tool' => $definition[2],
                    'network_protocol' => $definition[3],
                    'topology_type' => $definition[4],
                    'config_file' => null,
                ]),
                'TKDH' => ProductGraphic::create([
                    'product_id' => $product->product_id,
                    'design_type' => $definition[2],
                    'tools_used' => $definition[3],
                    'color_palette' => $definition[4],
                    'behance_link' => null,
                ]),
            };
        }
    }

    private function catalog(): array
    {
        return [
            'AI' => $this->aiProducts(),
            'CNTT' => $this->itProducts(),
            'MMT' => $this->networkProducts(),
            'TKDH' => $this->graphicProducts(),
        ];
    }

    private function aiProducts(): array
    {
        return [
            ['Điểm danh sinh viên bằng nhận diện khuôn mặt', 'Hệ thống nhận diện sinh viên theo thời gian thực, đối chiếu lịch học và xuất báo cáo chuyên cần. Giải pháp có cơ chế cảnh báo ảnh giả và xử lý trong điều kiện ánh sáng lớp học.', 'YOLOv8 Face', 'PyTorch, Ultralytics', 'Python', 'WIDER FACE và dữ liệu lớp học TDC', 94.80],
            ['Phân tích mật độ giao thông từ camera', 'Mô hình phát hiện và đếm phương tiện theo từng làn đường, hỗ trợ thống kê giờ cao điểm và gợi ý điều chỉnh chu kỳ đèn tín hiệu tại các nút giao.', 'YOLOv8', 'PyTorch, OpenCV', 'Python', 'UA-DETRAC', 92.40],
            ['Chatbot tư vấn tuyển sinh tiếng Việt', 'Trợ lý hội thoại trả lời câu hỏi về ngành học, học phí, hồ sơ và thời gian tuyển sinh. Câu trả lời được truy xuất từ bộ tài liệu chính thức và có dẫn nguồn rõ ràng.', 'PhoBERT', 'Transformers, FastAPI', 'Python', 'UIT-ViQuAD và FAQ tuyển sinh', 90.70],
            ['Nhận diện bệnh trên lá cây', 'Ứng dụng phân loại bệnh phổ biến trên lá rau và cây ăn quả từ ảnh điện thoại. Hệ thống đưa ra mức độ tin cậy cùng khuyến nghị chăm sóc cơ bản.', 'EfficientNet-B0', 'TensorFlow, Keras', 'Python', 'PlantVillage', 95.20],
            ['Phân loại rác tái chế trên thiết bị di động', 'Mô hình gọn nhẹ phân loại giấy, nhựa, kim loại, thủy tinh và rác hữu cơ. Phiên bản TensorFlow Lite chạy ngoại tuyến để hỗ trợ phân loại tại nguồn.', 'MobileNetV3', 'TensorFlow Lite', 'Python', 'TrashNet mở rộng', 93.60],
            ['Gợi ý sách theo sở thích người đọc', 'Hệ thống đề xuất sách kết hợp lịch sử mượn, thể loại yêu thích và đánh giá của người dùng. Kết quả được giải thích bằng các đặc trưng tương đồng dễ hiểu.', 'LightFM', 'Scikit-learn, FastAPI', 'Python', 'Lịch sử mượn sách thư viện', 88.90],
            ['Dự đoán nguy cơ học tập sa sút', 'Mô hình phân tích điểm thành phần, mức độ chuyên cần và tiến độ nộp bài để phát hiện sớm sinh viên cần hỗ trợ, đồng thời bảo vệ các thông tin cá nhân nhạy cảm.', 'XGBoost', 'Scikit-learn', 'Python', 'UCI Student Performance', 89.50],
            ['Phân tích cảm xúc phản hồi sinh viên', 'Công cụ phân loại phản hồi tích cực, trung tính và tiêu cực từ khảo sát môn học. Dashboard tổng hợp chủ đề nổi bật giúp giảng viên cải thiện nội dung giảng dạy.', 'PhoBERT', 'PyTorch, Transformers', 'Python', 'UIT-VSFC và phản hồi ẩn danh', 92.80],
            ['Phát hiện người điều khiển xe không đội mũ bảo hiểm', 'Hệ thống xử lý video giao thông để phát hiện xe máy, người lái và trạng thái đội mũ. Kết quả được lưu theo khung thời gian phục vụ thống kê an toàn.', 'YOLOv8', 'Ultralytics, OpenCV', 'Python', 'Dữ liệu giao thông Việt Nam', 96.10],
            ['Hỗ trợ phát hiện viêm phổi trên ảnh X-quang', 'Mô hình học sâu khoanh vùng vùng phổi nghi ngờ và cung cấp xác suất tham khảo. Sản phẩm phục vụ mục đích học thuật, không thay thế kết luận của bác sĩ.', 'DenseNet121', 'TensorFlow, Grad-CAM', 'Python', 'Chest X-Ray Pneumonia', 94.30],
            ['Nhận diện ngôn ngữ ký hiệu cơ bản', 'Ứng dụng nhận diện chuỗi cử chỉ tay qua webcam và chuyển thành văn bản tiếng Việt. Mô hình kết hợp đặc trưng bàn tay theo thời gian để tăng độ ổn định.', 'MediaPipe LSTM', 'TensorFlow, MediaPipe', 'Python', 'Bộ cử chỉ ký hiệu tự thu thập', 91.60],
            ['Trích xuất thông tin hóa đơn tiếng Việt', 'Giải pháp OCR nhận diện tên đơn vị, ngày lập, mã số thuế và tổng tiền từ ảnh hóa đơn. Quy trình có bước hiệu chỉnh góc chụp và kiểm tra định dạng dữ liệu.', 'VietOCR', 'PyTorch, OpenCV', 'Python', 'Hóa đơn bán lẻ ẩn danh', 93.80],
            ['Phát hiện website giả mạo', 'Mô hình đánh giá URL, chứng chỉ, cấu trúc tên miền và đặc trưng nội dung để cảnh báo trang có dấu hiệu lừa đảo trước khi người dùng nhập thông tin.', 'XGBoost', 'Scikit-learn, FastAPI', 'Python', 'PhishTank và URL hợp lệ', 97.20],
            ['Dự báo nhu cầu điện theo giờ', 'Hệ thống dự báo phụ tải ngắn hạn dựa trên lịch sử tiêu thụ, thời tiết và ngày lễ. Biểu đồ so sánh dự báo với số liệu thực tế hỗ trợ theo dõi sai số.', 'LSTM', 'Keras, Pandas', 'Python', 'Household Power Consumption', 90.40],
            ['Ước lượng năng suất cây trồng', 'Mô hình kết hợp dữ liệu thời tiết, loại đất, giống cây và lịch canh tác để dự báo sản lượng. Kết quả hỗ trợ lập kế hoạch vật tư và thời điểm thu hoạch.', 'Random Forest', 'Scikit-learn', 'Python', 'Dữ liệu nông nghiệp và khí tượng', 87.90],
            ['Nhận diện chỗ đậu xe còn trống', 'Hệ thống phân tích camera bãi xe, xác định trạng thái từng ô và cập nhật bản đồ chỗ trống gần thời gian thực cho người quản lý và người gửi xe.', 'YOLOv8 Segmentation', 'PyTorch, OpenCV', 'Python', 'PKLot', 95.00],
            ['Phân loại văn bản hành chính', 'Công cụ tự động phân loại công văn theo lĩnh vực, mức độ ưu tiên và đơn vị xử lý. Hệ thống hỗ trợ tìm kiếm ngữ nghĩa và giảm thao tác nhập liệu thủ công.', 'PhoBERT', 'Transformers', 'Python', 'Văn bản hành chính đã ẩn danh', 91.90],
            ['Nhận diện cảm xúc âm nhạc', 'Mô hình phân tích phổ Mel và nhịp điệu để phân loại bản nhạc theo nhóm cảm xúc. Sản phẩm cung cấp danh sách phát phù hợp với trạng thái người nghe.', 'CNN Audio', 'TensorFlow, Librosa', 'Python', 'DEAM', 86.70],
            ['Phát hiện té ngã cho người cao tuổi', 'Giải pháp theo dõi khung xương cơ thể trên camera, phát hiện chuyển động té ngã và gửi cảnh báo. Hình ảnh chỉ được xử lý cục bộ nhằm tăng tính riêng tư.', 'Pose LSTM', 'MediaPipe, TensorFlow', 'Python', 'UR Fall Detection', 93.10],
            ['Trợ lý tạo câu hỏi ôn tập từ giáo trình', 'Hệ thống tóm tắt nội dung bài học và sinh câu hỏi trắc nghiệm kèm đáp án gợi ý. Giảng viên có thể duyệt, chỉnh sửa trước khi đưa vào ngân hàng câu hỏi.', 'mT5', 'Transformers, FastAPI', 'Python', 'Giáo trình CNTT tiếng Việt', 89.80],
        ];
    }

    private function itProducts(): array
    {
        return [
            ['Hệ thống quản lý đồ án sinh viên', 'Nền tảng quản lý đề tài, nhóm thực hiện, mốc tiến độ và nhận xét của giảng viên. Dashboard tổng hợp trạng thái giúp khoa theo dõi toàn bộ vòng đời đồ án.', 'PHP, JavaScript', 'Laravel, React', 'MySQL'],
            ['Cổng đăng ký thực tập doanh nghiệp', 'Ứng dụng kết nối sinh viên với doanh nghiệp, quản lý CV, vị trí ứng tuyển và nhật ký thực tập. Quy trình duyệt được phân quyền rõ cho sinh viên, giảng viên và đơn vị tuyển dụng.', 'TypeScript', 'NestJS, React', 'PostgreSQL'],
            ['Website thương mại điện tử đặc sản Việt', 'Hệ thống bán hàng hỗ trợ giỏ hàng, mã giảm giá, tồn kho, thanh toán và theo dõi đơn. Trang quản trị cung cấp báo cáo doanh thu và sản phẩm bán chạy.', 'PHP, JavaScript', 'Laravel, Vue.js', 'MySQL'],
            ['Ứng dụng đặt lịch khám trực tuyến', 'Người dùng tìm bác sĩ, chọn khung giờ và nhận nhắc lịch. Phòng khám quản lý lịch làm việc, hồ sơ hẹn và thống kê tỷ lệ vắng mặt trên một giao diện thống nhất.', 'C#', 'ASP.NET Core, React', 'SQL Server'],
            ['Hệ thống thư viện số', 'Nền tảng quản lý tài liệu, phiên bản, quyền truy cập và lịch sử mượn. Tìm kiếm toàn văn giúp người học tiếp cận giáo trình, luận văn và tài nguyên nội bộ nhanh hơn.', 'Java', 'Spring Boot, Angular', 'PostgreSQL'],
            ['Ứng dụng quản lý chi tiêu cá nhân', 'Ứng dụng phân loại giao dịch, lập ngân sách tháng và cảnh báo khi chi tiêu vượt ngưỡng. Báo cáo trực quan giúp người dùng theo dõi dòng tiền theo thời gian.', 'Dart', 'Flutter', 'Firebase'],
            ['Hệ thống quản lý ký túc xá', 'Phần mềm quản lý phòng, hợp đồng, điện nước, phản ánh và thanh toán. Sinh viên tra cứu công nợ trong khi ban quản lý theo dõi tỷ lệ sử dụng phòng.', 'PHP', 'Laravel, Alpine.js', 'MySQL'],
            ['Nền tảng học lập trình tương tác', 'Website cung cấp bài học, trình chạy mã an toàn, bộ kiểm thử và lộ trình cá nhân. Giảng viên tạo bài tập và theo dõi tiến độ theo lớp học.', 'TypeScript', 'Next.js, Node.js', 'PostgreSQL'],
            ['Ứng dụng gọi món tại bàn bằng QR', 'Khách quét mã QR để xem thực đơn, gọi món và theo dõi trạng thái. Bếp nhận đơn theo thời gian thực, còn quản lý kiểm soát bàn và doanh thu cuối ca.', 'JavaScript', 'Express, React', 'MongoDB'],
            ['Hệ thống quản lý kho đa chi nhánh', 'Giải pháp theo dõi nhập xuất, chuyển kho, lô hàng và mức tồn tối thiểu. Nhật ký thao tác và phân quyền giúp kiểm soát sai lệch dữ liệu.', 'Java', 'Spring Boot, Vue.js', 'MySQL'],
            ['Cổng hỗ trợ sinh viên trực tuyến', 'Hệ thống tiếp nhận yêu cầu theo danh mục, ưu tiên và đơn vị phụ trách. Sinh viên theo dõi tiến độ, còn quản trị viên đo thời gian phản hồi và mức độ hài lòng.', 'PHP', 'Laravel, React', 'PostgreSQL'],
            ['Ứng dụng quản lý sự kiện và vé điện tử', 'Nền tảng tạo sự kiện, sơ đồ vé, mã QR check-in và thống kê khách tham dự. Mã vé được kiểm tra thời gian thực để hạn chế sử dụng trùng.', 'TypeScript', 'NestJS, Next.js', 'PostgreSQL'],
            ['Hệ thống tuyển dụng nội bộ', 'Ứng dụng quản lý tin tuyển dụng, hồ sơ ứng viên, lịch phỏng vấn và đánh giá theo vòng. Báo cáo hỗ trợ theo dõi nguồn ứng viên hiệu quả.', 'C#', 'ASP.NET Core, Vue.js', 'SQL Server'],
            ['Website đặt phòng homestay', 'Người dùng tìm phòng theo địa điểm, tiện nghi và ngày trống; chủ nhà quản lý giá và lịch đặt. Hệ thống xử lý tránh trùng lịch và gửi xác nhận tự động.', 'Python', 'Django, React', 'PostgreSQL'],
            ['Ứng dụng giao việc cho nhóm dự án', 'Công cụ quản lý bảng công việc, sprint, thời hạn và trao đổi theo nhiệm vụ. Thành viên nhận thông báo thời gian thực và xem báo cáo tiến độ cá nhân.', 'TypeScript', 'Next.js, Socket.IO', 'MongoDB'],
            ['Hệ thống quản lý trung tâm ngoại ngữ', 'Phần mềm quản lý lớp, học viên, học phí, điểm danh và kết quả kiểm tra. Phụ huynh nhận thông báo lịch học và tiến độ định kỳ.', 'Java', 'Spring Boot, React', 'MySQL'],
            ['Ứng dụng theo dõi bảo trì thiết bị', 'Mỗi thiết bị có hồ sơ, lịch bảo dưỡng, linh kiện thay thế và mã QR. Hệ thống tự động nhắc việc và thống kê chi phí theo phòng ban.', 'PHP', 'Laravel, Vue.js', 'MySQL'],
            ['Nền tảng quyên góp minh bạch', 'Website công khai chiến dịch, giao dịch đóng góp và tiến độ giải ngân. Quản trị viên xác minh chứng từ, còn người ủng hộ theo dõi báo cáo sử dụng quỹ.', 'TypeScript', 'NestJS, Angular', 'PostgreSQL'],
            ['Hệ thống quản lý vận tải giao nhận', 'Giải pháp phân công tài xế, tối ưu tuyến, cập nhật trạng thái giao hàng và đối soát. Dashboard theo dõi tỷ lệ giao đúng hẹn theo khu vực.', 'Go', 'Gin, React', 'PostgreSQL'],
            ['Ứng dụng đặt lịch sân thể thao', 'Người dùng xem lịch trống, đặt sân, thanh toán và mời bạn cùng chơi. Chủ sân quản lý khung giá, dịch vụ đi kèm và doanh thu theo ngày.', 'Kotlin, TypeScript', 'Spring Boot, React', 'MySQL'],
        ];
    }

    private function networkProducts(): array
    {
        return [
            ['Thiết kế mạng doanh nghiệp đa chi nhánh', 'Mô hình kết nối trụ sở và ba chi nhánh, phân vùng người dùng bằng VLAN, định tuyến động và VPN site-to-site. Thiết kế có dự phòng đường truyền và tài liệu kiểm thử.', 'Cisco Packet Tracer', 'VLAN, OSPF, IPsec, DHCP', 'Mô hình cây kết hợp hình sao'],
            ['Hệ thống mạng an toàn cho trường học', 'Giải pháp tách mạng phòng máy, giảng viên, hành chính và khách; áp dụng ACL, DHCP Snooping và cổng truy cập không dây riêng cho từng nhóm.', 'Cisco Packet Tracer', 'VLAN, STP, ACL, DHCP Snooping', 'Hình sao phân cấp'],
            ['Mô phỏng định tuyến OSPF nhiều vùng', 'Mô hình OSPF multi-area có tổng hợp tuyến, xác thực láng giềng và liên kết dự phòng. Kịch bản đo thời gian hội tụ được ghi nhận khi đường truyền gặp sự cố.', 'GNS3', 'OSPF, HSRP, SNMP', 'Mesh bán phần'],
            ['Triển khai tường lửa cho doanh nghiệp nhỏ', 'Kiến trúc firewall ba vùng LAN, WAN và DMZ; công bố web server có kiểm soát, chặn truy cập trái phép và lưu nhật ký phục vụ kiểm tra sự cố.', 'EVE-NG', 'NAT, ACL, HTTPS, Syslog', 'Ba vùng bảo mật'],
            ['Giám sát hạ tầng bằng Zabbix', 'Hệ thống theo dõi CPU, bộ nhớ, băng thông và trạng thái dịch vụ của máy chủ cùng thiết bị mạng. Cảnh báo được gửi theo ngưỡng và mức độ nghiêm trọng.', 'VMware, Zabbix', 'SNMP, ICMP, SMTP', 'Client–Server'],
            ['VPN làm việc từ xa có xác thực', 'Giải pháp cho nhân viên truy cập tài nguyên nội bộ qua VPN, kết hợp xác thực nhiều lớp và phân quyền theo nhóm người dùng.', 'EVE-NG, pfSense', 'OpenVPN, RADIUS, DNS', 'Hub-and-spoke'],
            ['Mạng bệnh viện phân vùng an toàn', 'Thiết kế tách thiết bị y tế, khu hành chính, Wi-Fi khách và cụm máy chủ. Chính sách ưu tiên lưu lượng và kiểm soát truy cập giúp bảo vệ dữ liệu nội bộ.', 'Cisco Packet Tracer', 'VLAN, QoS, OSPF, ACL', 'Hình sao phân cấp'],
            ['Cân bằng tải cho cụm web server', 'Mô hình hai web server phía sau load balancer, có health check và chuyển dịch vụ khi một nút lỗi. Kết quả thử tải thể hiện khả năng phân phối kết nối.', 'VMware, HAProxy', 'HTTP, HTTPS, Keepalived', 'Cụm dự phòng'],
            ['Hệ thống phát hiện xâm nhập mạng', 'Triển khai IDS phân tích lưu lượng, phát hiện quét cổng và mẫu tấn công phổ biến. Sự kiện được tập trung trên dashboard để phục vụ điều tra.', 'EVE-NG, Suricata', 'IDS, Syslog, TCP/IP', 'Giám sát song song'],
            ['Mạng Wi-Fi doanh nghiệp quản lý tập trung', 'Thiết kế vùng phủ sóng cho văn phòng nhiều tầng, tách SSID nhân viên và khách, áp dụng roaming cùng xác thực tập trung.', 'Ekahau, Cisco Packet Tracer', 'WPA2-Enterprise, RADIUS, CAPWAP', 'Cellular kết hợp hình sao'],
            ['Dịch vụ DNS và DHCP dự phòng', 'Hệ thống cấp phát địa chỉ và phân giải tên miền có máy chủ dự phòng, nhật ký tập trung và kịch bản khôi phục khi dịch vụ chính gián đoạn.', 'VMware, Ubuntu Server', 'DNS, DHCP Failover, NTP', 'Client–Server dự phòng'],
            ['Mạng SDN điều khiển tập trung', 'Mô hình mạng định nghĩa bằng phần mềm cho phép bộ điều khiển quản lý luồng và thay đổi chính sách tập trung. Dashboard hiển thị topology cùng thống kê cổng.', 'Mininet, OpenDaylight', 'OpenFlow, REST API', 'Spine–Leaf'],
            ['Bảo mật mạng nội bộ theo Zero Trust', 'Thiết kế kiểm soát truy cập dựa trên danh tính, phân đoạn mạng và nguyên tắc tối thiểu. Các kịch bản truy cập sai quyền được ghi log và cảnh báo.', 'EVE-NG, pfSense', '802.1X, RADIUS, VLAN, TLS', 'Phân đoạn vi mô'],
            ['Mạng trung tâm dữ liệu Spine–Leaf', 'Mô hình data center có hai spine và nhiều leaf, định tuyến ECMP và liên kết dự phòng. Thiết kế giảm độ trễ đông-tây giữa các cụm dịch vụ.', 'EVE-NG', 'BGP, ECMP, VXLAN', 'Spine–Leaf'],
            ['Hệ thống VoIP cho văn phòng', 'Triển khai tổng đài IP, máy nhánh, hàng đợi và ghi âm cuộc gọi; lưu lượng thoại được tách VLAN và ưu tiên QoS để đảm bảo chất lượng.', 'Cisco Packet Tracer, Asterisk', 'SIP, RTP, QoS, VLAN', 'Hình sao'],
            ['Kết nối hybrid cloud an toàn', 'Mô hình kết nối hạ tầng tại chỗ với mạng cloud qua đường hầm mã hóa. Route, firewall và giám sát được cấu hình để đảm bảo tính liên tục.', 'GNS3, AWS VPC', 'IPsec, BGP, HTTPS', 'Hybrid hub-and-spoke'],
            ['Phân tích lưu lượng bất thường', 'Hệ thống thu thập gói tin, thống kê phiên và phát hiện các đột biến băng thông. Dashboard hỗ trợ lọc theo máy nguồn, đích và giao thức.', 'Wireshark, ntopng', 'NetFlow, SNMP, TCP/IP', 'Giám sát tập trung'],
            ['Mạng IoT cho tòa nhà thông minh', 'Thiết kế kết nối cảm biến, gateway và máy chủ điều khiển; tách mạng IoT khỏi mạng người dùng và mã hóa dữ liệu truyền.', 'Cisco Packet Tracer IoT', 'MQTT, VLAN, WPA2, HTTPS', 'Mesh kết hợp hình sao'],
            ['Hệ thống sao lưu qua mạng', 'Mô hình backup theo lịch từ máy chủ ứng dụng sang kho lưu trữ, có mã hóa, kiểm tra toàn vẹn và thử nghiệm phục hồi dữ liệu.', 'VMware, TrueNAS', 'NFS, SMB, SSH, Rsync', 'Client–Server'],
            ['Xác thực truy cập mạng bằng RADIUS', 'Giải pháp quản lý tài khoản truy cập Wi-Fi và switch qua máy chủ RADIUS. Chính sách VLAN động được áp dụng theo nhóm người dùng.', 'EVE-NG, FreeRADIUS', 'RADIUS, 802.1X, LDAP', 'AAA tập trung'],
        ];
    }

    private function graphicProducts(): array
    {
        return [
            ['Bộ nhận diện thương hiệu cà phê Mộc Nhiên', 'Hệ thống nhận diện lấy cảm hứng từ vùng nguyên liệu Việt Nam, gồm logo, kiểu chữ, bao bì, menu và mẫu ứng dụng tại cửa hàng. Thiết kế ưu tiên cảm giác mộc mạc nhưng hiện đại.', 'Brand Identity', 'Adobe Illustrator, Photoshop', ['#3D2B1F', '#B77945', '#F3E9DC', '#2F5D50']],
            ['Poster tuyển sinh Khoa Công nghệ Thông tin', 'Bộ poster truyền thông trình bày rõ ngành học, thời gian đăng ký và kênh liên hệ. Bố cục lưới giúp nội dung dễ đọc trên cả bản in và mạng xã hội.', 'Poster', 'Adobe Illustrator, Photoshop', ['#0057B8', '#00A6D6', '#FFFFFF', '#FFC72C']],
            ['Giao diện ứng dụng học ngoại ngữ Lingua', 'Thiết kế UI/UX cho lộ trình học, bài luyện nghe nói, bảng xếp hạng và theo dõi tiến độ. Prototype được kiểm thử với luồng người dùng mới.', 'UI/UX Mobile', 'Figma, FigJam', ['#6C63FF', '#FFB703', '#F8F9FF', '#20243A']],
            ['Bao bì trà thảo mộc An Lành', 'Dòng bao bì phân biệt năm hương vị bằng màu sắc và họa tiết thực vật. Thông tin thành phần, cách pha và nhãn phụ được tổ chức rõ ràng để thuận tiện sản xuất.', 'Packaging', 'Adobe Illustrator, Dimension', ['#31572C', '#90A955', '#ECF39E', '#FFF8E7']],
            ['Bộ infographic phân loại rác tại nguồn', 'Infographic chuyển quy trình phân loại rác thành hệ thống biểu tượng trực quan, màu nhận diện nhất quán và phiên bản dùng cho bảng tin, tờ gấp, mạng xã hội.', 'Infographic', 'Adobe Illustrator, InDesign', ['#2A9D8F', '#E9C46A', '#F4A261', '#264653']],
            ['Tạp chí ẩm thực đường phố Sài Gòn', 'Ấn phẩm 32 trang giới thiệu món ăn, câu chuyện người bán và bản đồ trải nghiệm. Thiết kế kết hợp ảnh tư liệu, typography lớn và hệ lưới linh hoạt.', 'Editorial Design', 'Adobe InDesign, Photoshop', ['#D62828', '#F77F00', '#FCBF49', '#F5F1E8']],
            ['Bộ nhận diện thương hiệu thời trang NÉT', 'Nhận diện thời trang tối giản gồm logo chữ, nhãn mác, túi giấy, thẻ thành viên và template mạng xã hội. Hệ thống có hướng dẫn khoảng cách và kích thước sử dụng.', 'Brand Identity', 'Adobe Illustrator, Photoshop', ['#111111', '#F5F1EA', '#C9ADA7', '#9A8C98']],
            ['Giao diện website du lịch Việt Nam', 'Thiết kế trải nghiệm khám phá điểm đến, lập lịch trình và lưu địa điểm yêu thích. Giao diện responsive chú trọng hình ảnh, khả năng đọc và thao tác đặt dịch vụ.', 'UI/UX Website', 'Figma, Adobe Photoshop', ['#0077B6', '#00B4D8', '#CAF0F8', '#FFB703']],
            ['Poster chiến dịch bảo vệ đại dương', 'Chuỗi poster sử dụng hình ảnh tương phản để truyền tải tác động của rác nhựa. Thông điệp ngắn, mạnh và có phiên bản phù hợp màn hình kỹ thuật số.', 'Social Poster', 'Adobe Photoshop, Illustrator', ['#023E8A', '#0077B6', '#90E0EF', '#FF6B6B']],
            ['Bao bì mỹ phẩm thuần chay LÁ', 'Thiết kế bao bì cho bộ chăm sóc da thuần chay với cấu trúc nhãn dễ đọc, hệ màu dịu và vật liệu thân thiện môi trường. Mockup thể hiện đầy đủ chai, hộp và túi.', 'Packaging', 'Adobe Illustrator, Dimension', ['#6B705C', '#A5A58D', '#DDBEA9', '#FFE8D6']],
            ['Giao diện ứng dụng quản lý tài chính Finly', 'Thiết kế dashboard dòng tiền, ngân sách, mục tiêu tiết kiệm và cảnh báo giao dịch. Màu trạng thái được kiểm tra độ tương phản và hạn chế gây nhầm lẫn.', 'UI/UX Mobile', 'Figma, Principle', ['#4361EE', '#4CC9F0', '#F8F9FA', '#1B263B']],
            ['Bộ nhận diện giải chạy TDC Run', 'Hệ thống hình ảnh năng động cho giải chạy sinh viên gồm logo, số báo danh, áo, huy chương, cổng sự kiện và bài đăng truyền thông.', 'Event Branding', 'Adobe Illustrator, Photoshop', ['#FF4D00', '#FFB703', '#023047', '#FFFFFF']],
            ['Sách ảnh kiến trúc Sài Gòn', 'Ấn phẩm ghi lại chi tiết kiến trúc cũ và mới qua bố cục tối giản. Chú thích, bản đồ và mốc thời gian được thiết kế nhất quán để hỗ trợ tra cứu.', 'Photo Book', 'Adobe InDesign, Lightroom', ['#2B2D42', '#8D99AE', '#EDF2F4', '#D9A441']],
            ['Giao diện ứng dụng đặt món GreenBite', 'Trải nghiệm đặt món lành mạnh với bộ lọc dinh dưỡng, tùy chọn khẩu phần và theo dõi đơn. Prototype bao gồm đầy đủ trạng thái rỗng, lỗi và xác nhận.', 'UI/UX Mobile', 'Figma, Illustrator', ['#2D6A4F', '#74C69D', '#D8F3DC', '#FFB703']],
            ['Bộ poster lễ hội văn hóa Việt', 'Ba poster khai thác họa tiết dân gian theo ngôn ngữ đồ họa hiện đại, đảm bảo tiêu đề và lịch hoạt động rõ ở nhiều kích thước.', 'Cultural Poster', 'Adobe Illustrator, Photoshop', ['#9D0208', '#DC2F02', '#F48C06', '#FFBA08']],
            ['Bao bì bánh thủ công Bếp Nhà', 'Thiết kế hộp bánh, tem niêm phong và thiệp cảm ơn mang cảm giác gần gũi. Hệ thống có biến thể theo mùa nhưng vẫn giữ nhận diện cốt lõi.', 'Packaging', 'Adobe Illustrator, Photoshop', ['#7F5539', '#B08968', '#E6CCB2', '#FFF3E0']],
            ['Dashboard quản lý năng lượng thông minh', 'Thiết kế giao diện theo dõi điện năng theo thời gian, khu vực và thiết bị. Biểu đồ, cảnh báo và chế độ tối được xây dựng thành design system thống nhất.', 'Dashboard UI', 'Figma, FigJam', ['#0B132B', '#1C2541', '#5BC0BE', '#F4F7F5']],
            ['Bộ nhận diện không gian sáng tạo The Hub', 'Nhận diện linh hoạt cho không gian làm việc và sự kiện, gồm logo biến thể, hệ icon, biển chỉ dẫn và template chương trình hàng tháng.', 'Brand Identity', 'Adobe Illustrator, After Effects', ['#5A189A', '#9D4EDD', '#E0AAFF', '#10002B']],
            ['Motion graphic hướng dẫn an toàn giao thông', 'Video đồ họa chuyển động 60 giây giải thích các nguyên tắc qua đường, đội mũ bảo hiểm và nhận biết biển báo. Chuyển động được tối ưu cho màn hình lớp học.', 'Motion Graphic', 'After Effects, Illustrator', ['#E63946', '#F1FAEE', '#457B9D', '#1D3557']],
            ['Bộ template truyền thông câu lạc bộ sinh viên', 'Hệ thống template cho lịch hoạt động, tuyển thành viên, recap và thành tích. Thành phần được tổ chức để người không chuyên vẫn thay nội dung dễ dàng.', 'Social Media Kit', 'Figma, Photoshop', ['#7209B7', '#3A0CA3', '#4CC9F0', '#F8F9FA']],
        ];
    }
}

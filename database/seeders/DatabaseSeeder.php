<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Super Admin
        User::factory()->withRole(UserRole::SuperAdmin)->create([
            'name' => 'Quản trị viên cấp cao Demo',
            'email' => 'admin@example.test',
            'password' => 'password',
        ]);

        // Managers — mỗi phòng ban 1 manager riêng (phục vụ test phân quyền)
        $managerCSD = User::factory()->withRole(UserRole::Manager)->create([
            'name' => 'Nguyễn Văn Quản (CSD)',
            'email' => 'manager.csd@example.test',
            'password' => 'password',
        ]);
        $managerTID = User::factory()->withRole(UserRole::Manager)->create([
            'name' => 'Trần Thị Lan (TID)',
            'email' => 'manager.tid@example.test',
            'password' => 'password',
        ]);
        $managerEHD = User::factory()->withRole(UserRole::Manager)->create([
            'name' => 'Lê Văn Hùng (EHD)',
            'email' => 'manager.ehd@example.test',
            'password' => 'password',
        ]);

        // Legacy alias để test cũ vẫn pass (phòng ban đầu dùng manager.csd)
        $manager = $managerCSD;

        // Staff — 3-4 staff mỗi phòng ban, tên tiếng Việt
        $staffCSD = collect([
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Phạm Thị Mai', 'email' => 'staff.csd1@example.test', 'password' => 'password']),
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Hoàng Văn Nam', 'email' => 'staff.csd2@example.test', 'password' => 'password']),
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Vũ Thị Hương', 'email' => 'staff.csd3@example.test', 'password' => 'password']),
        ]);
        $staffTID = collect([
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Bùi Văn Thắng', 'email' => 'staff.tid1@example.test', 'password' => 'password']),
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Đặng Thị Linh', 'email' => 'staff.tid2@example.test', 'password' => 'password']),
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Ngô Văn Cường', 'email' => 'staff.tid3@example.test', 'password' => 'password']),
        ]);
        $staffEHD = collect([
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Phan Thị Anh', 'email' => 'staff.ehd1@example.test', 'password' => 'password']),
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Nguyễn Thị Thu', 'email' => 'staff.ehd2@example.test', 'password' => 'password']),
            User::factory()->withRole(UserRole::Staff)->create(['name' => 'Trịnh Văn Phúc', 'email' => 'staff.ehd3@example.test', 'password' => 'password']),
        ]);

        // Citizens — dữ liệu đa dạng hơn
        User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Nguyễn Văn An',
            'email' => 'citizen1@example.test',
            'password' => 'password',
            'citizen_id' => 'CITIZEN-0001',
        ]);
        User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Trần Thị Bình',
            'email' => 'citizen2@example.test',
            'password' => 'password',
            'citizen_id' => 'CITIZEN-0002',
        ]);
        User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Lê Hoàng Long',
            'email' => 'citizen3@example.test',
            'password' => 'password',
            'citizen_id' => 'CITIZEN-0003',
        ]);
        User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Phạm Minh Trang',
            'email' => 'citizen4@example.test',
            'password' => 'password',
            'citizen_id' => 'CITIZEN-0004',
        ]);

        // Departments
        $deptCSD = Department::query()->create([
            'name' => 'Phòng Dịch vụ Công dân',
            'code' => 'CSD',
            'address' => 'Trung tâm Phục vụ Hành chính công, số 1 Ngô Quyền',
            'leader_id' => $managerCSD->id,
        ]);
        $deptTID = Department::query()->create([
            'name' => 'Phòng Kỹ thuật và Hạ tầng',
            'code' => 'TID',
            'address' => 'Tòa nhà Hành chính Kỹ thuật, Khu đô thị mới',
            'leader_id' => $managerTID->id,
        ]);
        $deptEHD = Department::query()->create([
            'name' => 'Phòng Giáo dục và Y tế',
            'code' => 'EHD',
            'address' => 'Trung tâm Dịch vụ Cộng đồng, số 12 Lê Lợi',
            'leader_id' => $managerEHD->id,
        ]);

        // Gán manager + staff vào phòng ban tương ứng (1 manager không kiêm nhiều phòng ban nữa)
        $deptCSD->users()->attach(collect([$managerCSD->id])->merge($staffCSD->pluck('id'))->all());
        $deptTID->users()->attach(collect([$managerTID->id])->merge($staffTID->pluck('id'))->all());
        $deptEHD->users()->attach(collect([$managerEHD->id])->merge($staffEHD->pluck('id'))->all());

        // Alias cho code cũ
        $citizenServices = $deptCSD;
        $technicalServices = $deptTID;
        $socialServices = $deptEHD;
        $staffOne = $staffCSD->first();
        $staffTwo = $staffEHD->first();

        // Categories
        $categories = collect([
            ['name' => 'Hành chính', 'code' => 'ADMINISTRATION'],
            ['name' => 'Giáo dục', 'code' => 'EDUCATION'],
            ['name' => 'Y tế', 'code' => 'HEALTHCARE'],
            ['name' => 'Xây dựng', 'code' => 'CONSTRUCTION'],
            ['name' => 'Tài nguyên và Môi trường', 'code' => 'NATURAL_RESOURCES'],
        ])->mapWithKeys(function (array $category): array {
            $model = ServiceCategory::query()->create($category);

            return [$category['code'] => $model];
        });

        // === Services với form_schema + document_requirements hợp lý ===

        // CSD - Hành chính
        $civilStatus = ServiceType::query()->create([
            'category_id' => $categories['ADMINISTRATION']->id,
            'responsible_department_id' => $deptCSD->id,
            'name' => 'Cấp giấy xác nhận hộ tịch',
            'code' => 'CIVIL_STATUS_CERTIFICATE',
            'description' => 'Cấp giấy xác nhận thông tin hộ tịch cho công dân theo dữ liệu đã đăng ký.',
            'requirements' => 'Công dân phải có giấy tờ tùy thân còn hiệu lực và thông tin hộ khẩu rõ ràng.',
            'form_schema' => [
                ['name' => 'ho_ten', 'label' => 'Họ và tên', 'type' => 'text', 'required' => true],
                ['name' => 'ngay_sinh', 'label' => 'Ngày sinh', 'type' => 'date', 'required' => true],
                ['name' => 'so_cccd', 'label' => 'Số CCCD', 'type' => 'text', 'required' => true],
                ['name' => 'dia_chi_thuong_tru', 'label' => 'Địa chỉ thường trú', 'type' => 'text', 'required' => true],
                ['name' => 'ly_do_xin_xac_nhan', 'label' => 'Lý do xin xác nhận', 'type' => 'text', 'required' => true],
                ['name' => 'cam_ket_thong_tin', 'label' => 'Tôi cam kết thông tin khai là đúng sự thật', 'type' => 'boolean', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'cccd_copy', 'label' => 'Bản sao CCCD (mặt trước/sau)', 'required' => true, 'type' => 'pdf'],
                ['code' => 'so_ho_khau', 'label' => 'Sổ hộ khẩu / Xác nhận cư trú', 'required' => true, 'type' => 'pdf'],
            ],
            'processing_time_days' => 3,
            'fee' => 0,
        ]);

        $birthCertificate = ServiceType::query()->create([
            'category_id' => $categories['ADMINISTRATION']->id,
            'responsible_department_id' => $deptCSD->id,
            'name' => 'Cấp giấy khai sinh',
            'code' => 'BIRTH_CERTIFICATE',
            'description' => 'Đăng ký khai sinh cho trẻ mới sinh và cấp giấy khai sinh bản chính.',
            'requirements' => 'Thực hiện trong 60 ngày kể từ ngày sinh; cha/mẹ xuất trình giấy chứng sinh.',
            'form_schema' => [
                ['name' => 'ten_tre', 'label' => 'Họ tên trẻ', 'type' => 'text', 'required' => true],
                ['name' => 'ngay_sinh_tre', 'label' => 'Ngày sinh của trẻ', 'type' => 'date', 'required' => true],
                ['name' => 'gioi_tinh', 'label' => 'Giới tính', 'type' => 'text', 'required' => true],
                ['name' => 'noi_sinh', 'label' => 'Nơi sinh (bệnh viện/xã)', 'type' => 'text', 'required' => true],
                ['name' => 'ho_ten_cha', 'label' => 'Họ tên cha', 'type' => 'text', 'required' => false],
                ['name' => 'ho_ten_me', 'label' => 'Họ tên mẹ', 'type' => 'text', 'required' => true],
                ['name' => 'so_cccd_nguoi_khai', 'label' => 'Số CCCD người đi khai', 'type' => 'text', 'required' => true],
                ['name' => 'cam_ket', 'label' => 'Cam kết thông tin đúng sự thật', 'type' => 'boolean', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'giay_chung_sinh', 'label' => 'Giấy chứng sinh', 'required' => true, 'type' => 'pdf'],
                ['code' => 'cccd_bo_me', 'label' => 'CCCD của cha/mẹ', 'required' => true, 'type' => 'image'],
                ['code' => 'giay_dang_ky_ket_hon', 'label' => 'Giấy đăng ký kết hôn (nếu có)', 'required' => false, 'type' => 'pdf'],
            ],
            'processing_time_days' => 3,
        ]);

        $marriageStatus = ServiceType::query()->create([
            'category_id' => $categories['ADMINISTRATION']->id,
            'responsible_department_id' => $deptCSD->id,
            'name' => 'Xác nhận tình trạng hôn nhân',
            'code' => 'MARRIAGE_STATUS_CONFIRMATION',
            'description' => 'Cấp giấy xác nhận tình trạng hôn nhân để thực hiện thủ tục hành chính.',
            'requirements' => 'Công dân cư trú hợp pháp trên địa bàn phường/xã.',
            'form_schema' => [
                ['name' => 'ho_ten', 'label' => 'Họ tên người yêu cầu', 'type' => 'text', 'required' => true],
                ['name' => 'ngay_sinh', 'label' => 'Ngày sinh', 'type' => 'date', 'required' => true],
                ['name' => 'so_cccd', 'label' => 'Số CCCD', 'type' => 'text', 'required' => true],
                ['name' => 'muc_dich_su_dung', 'label' => 'Mục đích sử dụng giấy xác nhận', 'type' => 'text', 'required' => true],
                ['name' => 'cam_ket_doc_than', 'label' => 'Cam kết đang độc thân / đã ly hôn', 'type' => 'boolean', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'cccd_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'pdf'],
                ['code' => 'so_ho_khau', 'label' => 'Sổ hộ khẩu / Xác nhận cư trú', 'required' => true, 'type' => 'pdf'],
            ],
            'processing_time_days' => 5,
        ]);

        // TID - Xây dựng
        $constructionPermit = ServiceType::query()->create([
            'category_id' => $categories['CONSTRUCTION']->id,
            'responsible_department_id' => $deptTID->id,
            'name' => 'Cấp giấy phép xây dựng nhà ở riêng lẻ',
            'code' => 'CONSTRUCTION_PERMIT',
            'description' => 'Tiếp nhận và thẩm định hồ sơ đề nghị cấp giấy phép xây dựng công trình nhà ở riêng lẻ.',
            'requirements' => 'Công trình phù hợp quy hoạch, đảm bảo hành lang an toàn và chỉ giới xây dựng.',
            'form_schema' => [
                ['name' => 'dien_tich_xay_dung', 'label' => 'Diện tích xây dựng (m²)', 'type' => 'number', 'required' => true],
                ['name' => 'so_tang', 'label' => 'Số tầng dự kiến', 'type' => 'number', 'required' => true],
                ['name' => 'chieu_cao_cong_trinh', 'label' => 'Chiều cao công trình (m)', 'type' => 'number', 'required' => false],
                ['name' => 'dia_chi_cong_trinh', 'label' => 'Địa chỉ thửa đất xây dựng', 'type' => 'text', 'required' => true],
                ['name' => 'so_to_so_thua', 'label' => 'Số tờ - số thửa', 'type' => 'text', 'required' => true],
                ['name' => 'cam_ket_pccc', 'label' => 'Cam kết đảm bảo an toàn PCCC', 'type' => 'boolean', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'so_do_thua_dat', 'label' => 'Sổ đỏ / Giấy chứng nhận QSDĐ', 'required' => true, 'type' => 'pdf'],
                ['code' => 'ban_ve_thiet_ke', 'label' => 'Bản vẽ thiết kế (mặt bằng, mặt cắt)', 'required' => true, 'type' => 'pdf'],
                ['code' => 'cmnd_chu_dau_tu', 'label' => 'CCCD chủ đầu tư', 'required' => true, 'type' => 'image'],
            ],
            'processing_time_days' => 15,
            'fee' => 100000,
        ]);

        $renovationPermit = ServiceType::query()->create([
            'category_id' => $categories['CONSTRUCTION']->id,
            'responsible_department_id' => $deptTID->id,
            'name' => 'Cấp phép sửa chữa, cải tạo nhà ở',
            'code' => 'RENOVATION_PERMIT',
            'description' => 'Cấp phép sửa chữa, cải tạo nhà ở có thay đổi kết cấu chịu lực.',
            'requirements' => 'Không vi phạm chỉ giới, không ảnh hưởng công trình lân cận.',
            'form_schema' => [
                ['name' => 'hang_muc_sua_chua', 'label' => 'Hạng mục sửa chữa', 'type' => 'text', 'required' => true],
                ['name' => 'dien_tich_sua_chua', 'label' => 'Diện tích sửa chữa (m²)', 'type' => 'number', 'required' => true],
                ['name' => 'du_kien_thoi_gian', 'label' => 'Thời gian thi công dự kiến', 'type' => 'text', 'required' => false],
                ['name' => 'dia_chi_cong_trinh', 'label' => 'Địa chỉ công trình', 'type' => 'text', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'so_do_thua_dat', 'label' => 'Sổ đỏ / GCN QSDĐ', 'required' => true, 'type' => 'pdf'],
                ['code' => 'anh_hien_trang', 'label' => 'Ảnh hiện trạng công trình', 'required' => true, 'type' => 'image'],
                ['code' => 'ban_ve_sua_chua', 'label' => 'Bản vẽ phương án sửa chữa', 'required' => true, 'type' => 'pdf'],
            ],
            'processing_time_days' => 10,
            'fee' => 50000,
        ]);

        $fireSafetyCert = ServiceType::query()->create([
            'category_id' => $categories['CONSTRUCTION']->id,
            'responsible_department_id' => $deptTID->id,
            'name' => 'Thẩm duyệt an toàn PCCC',
            'code' => 'FIRE_SAFETY_APPROVAL',
            'description' => 'Thẩm duyệt hồ sơ đảm bảo an toàn phòng cháy chữa cháy cho công trình.',
            'requirements' => 'Hồ sơ thiết kế PCCC phải do đơn vị có chứng chỉ hành nghề lập.',
            'form_schema' => [
                ['name' => 'ten_cong_trinh', 'label' => 'Tên công trình', 'type' => 'text', 'required' => true],
                ['name' => 'dia_diem', 'label' => 'Địa điểm xây dựng', 'type' => 'text', 'required' => true],
                ['name' => 'quy_mo_dien_tich', 'label' => 'Quy mô / diện tích sàn (m²)', 'type' => 'number', 'required' => true],
                ['name' => 'don_vi_thiet_ke_pccc', 'label' => 'Đơn vị thiết kế PCCC', 'type' => 'text', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'ban_ve_pccc', 'label' => 'Bản vẽ hệ thống PCCC', 'required' => true, 'type' => 'pdf'],
                ['code' => 'thuyet_minh_pccc', 'label' => 'Thuyết minh thiết kế PCCC', 'required' => true, 'type' => 'pdf'],
                ['code' => 'chung_chi_thiet_ke', 'label' => 'Chứng chỉ hành nghề thiết kế', 'required' => true, 'type' => 'pdf'],
            ],
            'processing_time_days' => 12,
            'fee' => 200000,
        ]);

        $landRecord = ServiceType::query()->create([
            'category_id' => $categories['NATURAL_RESOURCES']->id,
            'responsible_department_id' => $deptTID->id,
            'name' => 'Yêu cầu cung cấp thông tin hồ sơ đất đai',
            'code' => 'LAND_RECORD_INFORMATION',
            'description' => 'Cung cấp thông tin lưu trữ về thửa đất và quyền sử dụng đất theo yêu cầu hợp lệ.',
            'requirements' => 'Người yêu cầu phải xác định rõ thửa đất và mục đích khai thác thông tin.',
            'form_schema' => [
                ['name' => 'so_to', 'label' => 'Số tờ bản đồ', 'type' => 'text', 'required' => true],
                ['name' => 'so_thua', 'label' => 'Số thửa đất', 'type' => 'text', 'required' => true],
                ['name' => 'dia_chi_thua_dat', 'label' => 'Địa chỉ thửa đất', 'type' => 'text', 'required' => true],
                ['name' => 'muc_dich_su_dung', 'label' => 'Mục đích khai thác thông tin', 'type' => 'text', 'required' => true],
                ['name' => 'cam_ket_su_dung', 'label' => 'Cam kết sử dụng thông tin đúng mục đích', 'type' => 'boolean', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'cccd_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'pdf'],
                ['code' => 'giay_uy_quyen', 'label' => 'Giấy ủy quyền (nếu làm thay)', 'required' => false, 'type' => 'pdf'],
            ],
            'processing_time_days' => 10,
            'fee' => 50000,
        ]);

        // EHD - Giáo dục & Y tế
        $schoolEnrollment = ServiceType::query()->create([
            'category_id' => $categories['EDUCATION']->id,
            'responsible_department_id' => $deptEHD->id,
            'name' => 'Đăng ký nhập học trường công lập',
            'code' => 'PUBLIC_SCHOOL_ENROLLMENT',
            'description' => 'Đăng ký nhập học trực tuyến cho học sinh tại trường công lập theo tuyến.',
            'requirements' => 'Học sinh thuộc đối tượng và địa bàn tuyển sinh theo quy định của Sở GD&ĐT.',
            'form_schema' => [
                ['name' => 'ho_ten_hoc_sinh', 'label' => 'Họ tên học sinh', 'type' => 'text', 'required' => true],
                ['name' => 'ngay_sinh_hoc_sinh', 'label' => 'Ngày sinh học sinh', 'type' => 'date', 'required' => true],
                ['name' => 'gioi_tinh', 'label' => 'Giới tính', 'type' => 'text', 'required' => true],
                ['name' => 'truong_dang_ky', 'label' => 'Trường đăng ký', 'type' => 'text', 'required' => true],
                ['name' => 'lop_dang_ky', 'label' => 'Lớp đăng ký', 'type' => 'text', 'required' => true],
                ['name' => 'ho_ten_phu_huynh', 'label' => 'Họ tên phụ huynh', 'type' => 'text', 'required' => true],
                ['name' => 'so_dien_thoai', 'label' => 'Số điện thoại liên hệ', 'type' => 'text', 'required' => true],
                ['name' => 'cam_ket_hoc_tuyen', 'label' => 'Cam kết đúng tuyến tuyển sinh', 'type' => 'boolean', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'giay_khai_sinh', 'label' => 'Bản sao giấy khai sinh', 'required' => true, 'type' => 'pdf'],
                ['code' => 'xac_nhan_cu_tru', 'label' => 'Giấy xác nhận cư trú', 'required' => true, 'type' => 'pdf'],
                ['code' => 'hoc_ba', 'label' => 'Học bạ (nếu chuyển trường)', 'required' => false, 'type' => 'pdf'],
            ],
            'processing_time_days' => 5,
        ]);

        $healthcareSupport = ServiceType::query()->create([
            'category_id' => $categories['HEALTHCARE']->id,
            'responsible_department_id' => $deptEHD->id,
            'name' => 'Đăng ký hỗ trợ chăm sóc sức khỏe',
            'code' => 'HEALTHCARE_SUPPORT_REGISTRATION',
            'description' => 'Đăng ký hưởng chính sách hỗ trợ chăm sóc sức khỏe dành cho đối tượng đủ điều kiện.',
            'requirements' => 'Người đăng ký thuộc nhóm đối tượng được hưởng hỗ trợ theo quy định của Bộ Y tế.',
            'form_schema' => [
                ['name' => 'ho_ten', 'label' => 'Họ tên người đăng ký', 'type' => 'text', 'required' => true],
                ['name' => 'ngay_sinh', 'label' => 'Ngày sinh', 'type' => 'date', 'required' => true],
                ['name' => 'so_cccd', 'label' => 'Số CCCD', 'type' => 'text', 'required' => true],
                ['name' => 'doi_tuong', 'label' => 'Đối tượng hưởng hỗ trợ', 'type' => 'text', 'required' => true],
                ['name' => 'so_bhyt', 'label' => 'Số thẻ BHYT (nếu có)', 'type' => 'text', 'required' => false],
                ['name' => 'dia_chi', 'label' => 'Địa chỉ liên hệ', 'type' => 'text', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'cccd_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'pdf'],
                ['code' => 'giay_xac_nhan_doi_tuong', 'label' => 'Giấy tờ chứng minh đối tượng', 'required' => true, 'type' => 'pdf'],
                ['code' => 'the_bhyt', 'label' => 'Thẻ BHYT (nếu có)', 'required' => false, 'type' => 'image'],
            ],
            'processing_time_days' => 7,
        ]);

        $vaccinationCert = ServiceType::query()->create([
            'category_id' => $categories['HEALTHCARE']->id,
            'responsible_department_id' => $deptEHD->id,
            'name' => 'Cấp chứng nhận tiêm chủng',
            'code' => 'VACCINATION_CERTIFICATE',
            'description' => 'Cấp giấy chứng nhận đã tiêm chủng theo chương trình tiêm chủng mở rộng.',
            'requirements' => 'Có sổ tiêm chủng hoặc dữ liệu tiêm trên hệ thống quốc gia.',
            'form_schema' => [
                ['name' => 'ho_ten', 'label' => 'Họ tên người tiêm', 'type' => 'text', 'required' => true],
                ['name' => 'ngay_sinh', 'label' => 'Ngày sinh', 'type' => 'date', 'required' => true],
                ['name' => 'loai_vac_xin', 'label' => 'Loại vắc xin', 'type' => 'text', 'required' => true],
                ['name' => 'ngay_tiem', 'label' => 'Ngày tiêm gần nhất', 'type' => 'date', 'required' => true],
                ['name' => 'co_so_tiem', 'label' => 'Cơ sở tiêm', 'type' => 'text', 'required' => true],
            ],
            'document_requirements' => [
                ['code' => 'so_tiem_chung', 'label' => 'Sổ tiêm chủng / Phiếu tiêm', 'required' => true, 'type' => 'image'],
                ['code' => 'cccd_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'pdf'],
            ],
            'processing_time_days' => 3,
        ]);

        // Gán service cho staff theo phòng ban
        foreach ($staffCSD as $staff) {
            $staff->serviceTypes()->attach([$civilStatus->id, $birthCertificate->id, $marriageStatus->id]);
        }
        foreach ($staffTID as $staff) {
            $staff->serviceTypes()->attach([$constructionPermit->id, $renovationPermit->id, $fireSafetyCert->id, $landRecord->id]);
        }
        foreach ($staffEHD as $staff) {
            $staff->serviceTypes()->attach([$schoolEnrollment->id, $healthcareSupport->id, $vaccinationCert->id]);
        }

        $citizenOne = User::query()->where('email', 'citizen1@example.test')->firstOrFail();
        $citizenTwo = User::query()->where('email', 'citizen2@example.test')->firstOrFail();
        $citizenThree = User::query()->where('email', 'citizen3@example.test')->firstOrFail();
        $citizenFour = User::query()->where('email', 'citizen4@example.test')->firstOrFail();

        $seedApplication = static function (
            string $code,
            User $citizen,
            ServiceType $service,
            ApplicationStatus $status,
            int $daysAgo,
            ?User $assignedStaff = null,
        ): void {
            $submittedAt = now()->subDays($daysAgo);
            $attributes = [
                'application_code' => $code,
                'citizen_id' => $citizen->id,
                'service_type_id' => $service->id,
                'status' => $status,
                'form_data' => ['ho_ten' => $citizen->name, 'so_cccd' => fake()->numerify('############')],
                'submitted_at' => $submittedAt,
            ];

            if ($assignedStaff !== null) {
                $attributes['assigned_staff_id'] = $assignedStaff->id;
            }

            if (in_array($status, [ApplicationStatus::Assigned, ApplicationStatus::Processing, ApplicationStatus::SupplementRequired, ApplicationStatus::PendingApproval, ApplicationStatus::Approved, ApplicationStatus::Rejected], true)) {
                $attributes['processing_started_at'] = now()->subDays(max(1, $daysAgo - 1));
            }

            if ($status === ApplicationStatus::Approved) {
                $attributes['completed_at'] = now()->subDay();
                $attributes['result_note'] = 'Hồ sơ hợp lệ, đã cấp giấy chứng nhận theo quy định.';
            }

            if ($status === ApplicationStatus::Rejected) {
                $attributes['completed_at'] = now()->subDay();
                $attributes['rejection_reason'] = 'Hồ sơ không đủ điều kiện theo quy định hiện hành.';
            }

            $application = Application::query()->create($attributes);

            $application->statusHistories()->create([
                'from_status' => null,
                'to_status' => ApplicationStatus::Received,
                'changed_by' => $citizen->id,
                'note' => 'Hồ sơ được nộp trực tuyến.',
            ]);

            // Phân công
            if ($assignedStaff !== null) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::Received,
                    'to_status' => ApplicationStatus::Assigned,
                    'changed_by' => $service->responsibleDepartment->leader_id,
                    'note' => 'Đã phân công cán bộ xử lý.',
                ]);
            }

            if (in_array($status, [ApplicationStatus::Processing, ApplicationStatus::SupplementRequired, ApplicationStatus::PendingApproval, ApplicationStatus::Approved, ApplicationStatus::Rejected], true)) {
                $application->statusHistories()->create([
                    'from_status' => $assignedStaff ? ApplicationStatus::Assigned : ApplicationStatus::Received,
                    'to_status' => ApplicationStatus::Processing,
                    'changed_by' => $assignedStaff?->id ?? $citizen->id,
                    'note' => 'Cán bộ bắt đầu xử lý.',
                ]);
            }

            if ($status === ApplicationStatus::SupplementRequired) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::Processing,
                    'to_status' => ApplicationStatus::SupplementRequired,
                    'changed_by' => $assignedStaff?->id ?? $citizen->id,
                    'note' => 'Yêu cầu bổ sung: thiếu Sổ hộ khẩu và bản sao CCCD rõ nét.',
                ]);
            }

            if ($status === ApplicationStatus::PendingApproval) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::Processing,
                    'to_status' => ApplicationStatus::PendingApproval,
                    'changed_by' => $assignedStaff?->id ?? $citizen->id,
                    'note' => 'Đã hoàn tất xử lý, gửi chờ quản lý duyệt.',
                ]);
            }

            if ($status === ApplicationStatus::Approved) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::PendingApproval,
                    'to_status' => ApplicationStatus::Approved,
                    'changed_by' => $service->responsibleDepartment->leader_id,
                    'note' => 'Quản lý đã duyệt.',
                ]);
            }

            if ($status === ApplicationStatus::Rejected) {
                $application->statusHistories()->create([
                    'from_status' => ApplicationStatus::PendingApproval,
                    'to_status' => ApplicationStatus::Rejected,
                    'changed_by' => $service->responsibleDepartment->leader_id,
                    'note' => $attributes['rejection_reason'],
                ]);
            }

            if ($assignedStaff !== null) {
                $application->assignments()->create([
                    'staff_id' => $assignedStaff->id,
                    'department_id' => $service->responsible_department_id,
                    'assigned_by' => $service->responsibleDepartment->leader_id,
                    'assigned_at' => now()->subDays(max(1, $daysAgo - 1)),
                    'ended_at' => null,
                ]);
            }
        };

        $seedApplication('HS-20260820-000001', $citizenOne, $civilStatus, ApplicationStatus::Received, 2);
        $seedApplication('HS-20260820-000002', $citizenOne, $constructionPermit, ApplicationStatus::Assigned, 3, $staffTID->random());
        $seedApplication('HS-20260820-000003', $citizenTwo, $schoolEnrollment, ApplicationStatus::Processing, 3, $staffEHD->random());
        $seedApplication('HS-20260820-000004', $citizenTwo, $healthcareSupport, ApplicationStatus::SupplementRequired, 4, $staffEHD->random());
        $seedApplication('HS-20260820-000005', $citizenThree, $landRecord, ApplicationStatus::PendingApproval, 5, $staffTID->random());
        $seedApplication('HS-20260820-000006', $citizenThree, $birthCertificate, ApplicationStatus::Approved, 6, $staffCSD->random());
        $seedApplication('HS-20260820-000007', $citizenFour, $renovationPermit, ApplicationStatus::Rejected, 5, $staffTID->random());
        $seedApplication('HS-20260820-000008', $citizenFour, $vaccinationCert, ApplicationStatus::Processing, 2, $staffEHD->random());
        $seedApplication('HS-20260820-000009', $citizenOne, $marriageStatus, ApplicationStatus::Received, 1);
        $seedApplication('HS-20260820-000010', $citizenTwo, $fireSafetyCert, ApplicationStatus::Assigned, 2, $staffTID->random());
    }
}

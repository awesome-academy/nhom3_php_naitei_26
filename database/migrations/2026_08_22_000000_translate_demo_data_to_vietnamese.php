<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Translate only untouched demo records, identified by both their stable code/email and old English name.
     */
    public function up(): void
    {
        $this->updateDemoUsers([
            'admin@example.test' => ['Development Super Admin', 'Quản trị viên cấp cao Demo'],
            'manager@example.test' => ['Development Manager', 'Quản lý Demo'],
            'staff1@example.test' => ['Development Staff One', 'Nhân viên Demo 1'],
            'staff2@example.test' => ['Development Staff Two', 'Nhân viên Demo 2'],
            'citizen1@example.test' => ['Development Citizen One', 'Công dân Demo 1'],
            'citizen2@example.test' => ['Development Citizen Two', 'Công dân Demo 2'],
        ]);

        $this->updateDemoDepartments([
            'CSD' => ['Citizen Services Department', 'Phòng Dịch vụ Công dân', 'Trung tâm Phục vụ Hành chính công'],
            'TID' => ['Technical and Infrastructure Department', 'Phòng Kỹ thuật và Hạ tầng', 'Tòa nhà Hành chính Kỹ thuật'],
            'EHD' => ['Education and Health Department', 'Phòng Giáo dục và Y tế', 'Trung tâm Dịch vụ Cộng đồng'],
        ]);

        $this->updateDemoCategories([
            'ADMINISTRATION' => ['Administration', 'Hành chính'],
            'EDUCATION' => ['Education', 'Giáo dục'],
            'HEALTHCARE' => ['Healthcare', 'Y tế'],
            'CONSTRUCTION' => ['Construction', 'Xây dựng'],
            'NATURAL_RESOURCES' => ['Natural Resources', 'Tài nguyên và Môi trường'],
        ]);

        $this->updateDemoServices($this->vietnameseServices());
    }

    public function down(): void
    {
        $this->updateDemoUsers([
            'admin@example.test' => ['Quản trị viên cấp cao Demo', 'Development Super Admin'],
            'manager@example.test' => ['Quản lý Demo', 'Development Manager'],
            'staff1@example.test' => ['Nhân viên Demo 1', 'Development Staff One'],
            'staff2@example.test' => ['Nhân viên Demo 2', 'Development Staff Two'],
            'citizen1@example.test' => ['Công dân Demo 1', 'Development Citizen One'],
            'citizen2@example.test' => ['Công dân Demo 2', 'Development Citizen Two'],
        ]);

        $this->updateDemoDepartments([
            'CSD' => ['Phòng Dịch vụ Công dân', 'Citizen Services Department', 'Public Service Center'],
            'TID' => ['Phòng Kỹ thuật và Hạ tầng', 'Technical and Infrastructure Department', 'Technical Administration Building'],
            'EHD' => ['Phòng Giáo dục và Y tế', 'Education and Health Department', 'Community Services Building'],
        ]);

        $this->updateDemoCategories([
            'ADMINISTRATION' => ['Hành chính', 'Administration'],
            'EDUCATION' => ['Giáo dục', 'Education'],
            'HEALTHCARE' => ['Y tế', 'Healthcare'],
            'CONSTRUCTION' => ['Xây dựng', 'Construction'],
            'NATURAL_RESOURCES' => ['Tài nguyên và Môi trường', 'Natural Resources'],
        ]);

        $englishServices = collect($this->vietnameseServices())->map(function (array $service): array {
            return [
                'old_name' => $service['name'],
                'name' => $service['english_name'],
                'description' => null,
                'requirements' => $service['english_requirements'],
                'form_schema' => $service['english_form_schema'],
                'document_requirements' => $service['english_document_requirements'],
            ];
        })->all();

        $this->updateDemoServices($englishServices);
    }

    /** @param array<string, array{0: string, 1: string}> $users */
    private function updateDemoUsers(array $users): void
    {
        foreach ($users as $email => [$oldName, $newName]) {
            DB::table('users')->where('email', $email)->where('name', $oldName)->update(['name' => $newName]);
        }
    }

    /** @param array<string, array{0: string, 1: string, 2: string}> $departments */
    private function updateDemoDepartments(array $departments): void
    {
        foreach ($departments as $code => [$oldName, $newName, $address]) {
            DB::table('departments')->where('code', $code)->where('name', $oldName)->update([
                'name' => $newName,
                'address' => $address,
            ]);
        }
    }

    /** @param array<string, array{0: string, 1: string}> $categories */
    private function updateDemoCategories(array $categories): void
    {
        foreach ($categories as $code => [$oldName, $newName]) {
            DB::table('service_categories')->where('code', $code)->where('name', $oldName)->update(['name' => $newName]);
        }
    }

    /** @param array<string, array<string, mixed>> $services */
    private function updateDemoServices(array $services): void
    {
        foreach ($services as $code => $service) {
            $oldName = $service['old_name'];
            unset($service['old_name'], $service['english_name'], $service['english_requirements'], $service['english_form_schema'], $service['english_document_requirements']);

            foreach (['form_schema', 'document_requirements'] as $jsonColumn) {
                if (array_key_exists($jsonColumn, $service)) {
                    $service[$jsonColumn] = json_encode($service[$jsonColumn], JSON_UNESCAPED_UNICODE);
                }
            }

            DB::table('service_types')->where('code', $code)->where('name', $oldName)->update($service);
        }
    }

    /** @return array<string, array<string, mixed>> */
    private function vietnameseServices(): array
    {
        return [
            'CIVIL_STATUS_CERTIFICATE' => [
                'old_name' => 'Civil Status Certificate',
                'name' => 'Cấp giấy xác nhận hộ tịch',
                'english_name' => 'Civil Status Certificate',
                'description' => 'Cấp giấy xác nhận thông tin hộ tịch cho công dân theo dữ liệu đã đăng ký.',
                'requirements' => 'Công dân phải có giấy tờ tùy thân còn hiệu lực.',
                'english_requirements' => 'Valid citizen identification is required.',
                'form_schema' => [],
                'english_form_schema' => [],
                'document_requirements' => [['code' => 'citizen_id_copy', 'label' => 'Bản sao căn cước công dân', 'required' => true, 'type' => 'pdf']],
                'english_document_requirements' => [['code' => 'citizen_id_copy', 'label' => 'Citizen ID Copy', 'required' => true]],
            ],
            'CONSTRUCTION_PERMIT' => [
                'old_name' => 'Construction Permit',
                'name' => 'Cấp giấy phép xây dựng',
                'english_name' => 'Construction Permit',
                'description' => 'Tiếp nhận và thẩm định hồ sơ đề nghị cấp giấy phép xây dựng công trình.',
                'requirements' => 'Công trình phải phù hợp quy hoạch và đáp ứng các quy định về xây dựng hiện hành.',
                'english_requirements' => null,
                'form_schema' => [['name' => 'construction_area', 'label' => 'Diện tích xây dựng', 'type' => 'number', 'required' => true]],
                'english_form_schema' => [['name' => 'construction_area', 'label' => 'Construction Area', 'type' => 'number', 'required' => true]],
                'document_requirements' => [['code' => 'design_drawing', 'label' => 'Bản vẽ thiết kế', 'required' => true, 'type' => 'pdf']],
                'english_document_requirements' => [['code' => 'design_drawing', 'label' => 'Design Drawing', 'required' => true]],
            ],
            'PUBLIC_SCHOOL_ENROLLMENT' => [
                'old_name' => 'Public School Enrollment',
                'name' => 'Đăng ký nhập học trường công lập',
                'english_name' => 'Public School Enrollment',
                'description' => 'Đăng ký nhập học trực tuyến cho học sinh tại trường công lập.',
                'requirements' => 'Học sinh thuộc đối tượng và địa bàn tuyển sinh theo quy định.',
                'english_requirements' => null,
                'form_schema' => [],
                'english_form_schema' => [],
                'document_requirements' => [['code' => 'birth_certificate', 'label' => 'Bản sao giấy khai sinh', 'required' => true, 'type' => 'pdf'], ['code' => 'residence_confirmation', 'label' => 'Giấy xác nhận cư trú', 'required' => true, 'type' => 'pdf']],
                'english_document_requirements' => [],
            ],
            'HEALTHCARE_SUPPORT_REGISTRATION' => [
                'old_name' => 'Healthcare Support Registration',
                'name' => 'Đăng ký hỗ trợ chăm sóc sức khỏe',
                'english_name' => 'Healthcare Support Registration',
                'description' => 'Đăng ký hưởng chính sách hỗ trợ chăm sóc sức khỏe dành cho đối tượng đủ điều kiện.',
                'requirements' => 'Người đăng ký thuộc nhóm đối tượng được hưởng hỗ trợ theo quy định.',
                'english_requirements' => null,
                'form_schema' => [],
                'english_form_schema' => [],
                'document_requirements' => [['code' => 'citizen_id_copy', 'label' => 'Bản sao căn cước công dân', 'required' => true, 'type' => 'pdf'], ['code' => 'eligibility_document', 'label' => 'Giấy tờ chứng minh đối tượng', 'required' => true, 'type' => 'pdf']],
                'english_document_requirements' => [],
            ],
            'LAND_RECORD_INFORMATION' => [
                'old_name' => 'Land Record Information Request',
                'name' => 'Yêu cầu cung cấp thông tin hồ sơ đất đai',
                'english_name' => 'Land Record Information Request',
                'description' => 'Cung cấp thông tin lưu trữ về thửa đất và quyền sử dụng đất theo yêu cầu hợp lệ.',
                'requirements' => 'Người yêu cầu phải xác định rõ thửa đất và mục đích khai thác thông tin.',
                'english_requirements' => null,
                'form_schema' => [['name' => 'land_lot_number', 'label' => 'Số thửa đất', 'type' => 'text', 'required' => true]],
                'english_form_schema' => [],
                'document_requirements' => [['code' => 'citizen_id_copy', 'label' => 'Bản sao căn cước công dân', 'required' => true, 'type' => 'pdf']],
                'english_document_requirements' => [],
            ],
        ];
    }
};

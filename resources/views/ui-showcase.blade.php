<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bộ giao diện cơ sở - {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('emblem-vietnam.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Import Google Fonts for demonstration -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-surface min-h-screen p-8 text-gray-800">
    <div class="max-w-4xl mx-auto space-y-8">
        
        <header class="mb-8">
            <div class="mb-6 flex items-center gap-3">
                <img class="h-14 w-14 shrink-0 object-contain" src="{{ asset('emblem-vietnam.svg') }}" alt="Quốc huy Việt Nam">
                <div>
                    <p class="text-lg font-bold leading-tight text-[#073d7d]">Cổng Dịch Vụ Công</p>
                    <p class="mt-0.5 text-[10px] font-semibold tracking-[0.12em] text-gray-400">PHỤC VỤ NGƯỜI DÂN</p>
                </div>
            </div>
            <h1 class="text-3xl font-georgia font-bold text-primary mb-2">Bộ giao diện cơ sở</h1>
            <p class="font-inter text-secondary">Đây là trang tổng hợp các component UI và design tokens dùng chung cho cả Citizen Site và Admin Site.</p>
        </header>

        <!-- Typography Section -->
        <section class="card-container">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">1. Typography (Fonts)</h2>
            <div class="space-y-4">
                <div>
                    <span class="text-sm text-secondary block">Inter (Sans-serif) - Default</span>
                    <p class="font-inter text-lg">The quick brown fox jumps over the lazy dog. (Dùng cho UI chung)</p>
                </div>
                <div>
                    <span class="text-sm text-secondary block">Georgia (Serif)</span>
                    <p class="font-georgia text-lg">The quick brown fox jumps over the lazy dog. (Dùng cho tiêu đề trang trọng, form in ấn)</p>
                </div>
                <div>
                    <span class="text-sm text-secondary block">Consolas (Monospace)</span>
                    <p class="font-consolas text-lg">The quick brown fox jumps over the lazy dog. (Dùng cho mã hồ sơ, code, logs)</p>
                </div>
            </div>
        </section>

        <!-- Colors Section -->
        <section class="card-container">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">2. Design Tokens (Colors)</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-primary shadow-sm flex items-center justify-center text-white font-bold">Primary</div>
                    <p class="text-sm text-center text-secondary">var(--color-primary)</p>
                </div>
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-danger shadow-sm flex items-center justify-center text-white font-bold">Danger</div>
                    <p class="text-sm text-center text-secondary">var(--color-danger)</p>
                </div>
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-secondary shadow-sm flex items-center justify-center text-white font-bold">Secondary</div>
                    <p class="text-sm text-center text-secondary">var(--color-secondary)</p>
                </div>
                <div class="space-y-2">
                    <div class="h-16 rounded-lg bg-surface border shadow-sm flex items-center justify-center text-gray-700 font-bold">Surface</div>
                    <p class="text-sm text-center text-secondary">var(--color-surface)</p>
                </div>
            </div>
        </section>

        <!-- Components Section -->
        <section class="card-container">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">3. Shared Components</h2>
            
            <div class="space-y-8">
                <!-- Buttons -->
                <div>
                    <h3 class="text-sm font-semibold text-secondary mb-4 uppercase tracking-wider">Buttons (Variants)</h3>
                    <div class="flex flex-wrap gap-4 items-center">
                        <button class="btn-primary">Primary Button</button>
                        <button class="btn-secondary">Secondary</button>
                        <button class="btn-success">Success</button>
                        <button class="btn-danger">Danger</button>
                        <button class="btn-outline">Outline Button</button>
                        <button class="btn-ghost">Ghost Button</button>
                        <button class="btn-primary" disabled>Disabled</button>
                    </div>
                </div>

                <!-- Inputs -->
                <div class="pt-6 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-secondary mb-4 uppercase tracking-wider">Inputs & Forms</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="label">Full Name</label>
                            <input type="text" class="input-field" placeholder="Enter your full name">
                        </div>
                        <div>
                            <label class="label">Email Address (Error State)</label>
                            <input type="email" class="input-field input-error" value="invalid-email@">
                            <span class="text-danger text-sm mt-1 block">Please enter a valid email.</span>
                        </div>
                    </div>
                </div>

                <!-- Checkbox -->
                <div class="pt-6 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-secondary mb-4 uppercase tracking-wider">Checkboxes & Controls</h3>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" class="checkbox-field" checked>
                            <span class="text-gray-700 font-inter font-medium">Remember me</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" class="checkbox-field">
                            <span class="text-gray-700 font-inter font-medium">Accept terms</span>
                        </label>
                    </div>
                </div>

                <!-- Capsules / Badges -->
                <div class="pt-6 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-secondary mb-4 uppercase tracking-wider">Status Capsules</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <p class="text-sm text-gray-500 mb-2">1. Large Status (Có viền, dùng cho Trạng thái chính, có thể kèm Icon)</p>
                            <div class="flex flex-wrap gap-3 items-center">
                                <span class="capsule-lg c-success">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Approved
                                </span>
                                <span class="capsule-lg c-danger">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Rejected
                                </span>
                                <span class="capsule-lg c-warning">Pending</span>
                                <span class="capsule-lg c-info">Processing</span>
                                <span class="capsule-lg c-neutral">Draft</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 mb-2">2. Small Tags (Không viền, dùng cho Mức độ ưu tiên/Phân loại)</p>
                            <div class="flex flex-wrap gap-3 items-center">
                                <span class="capsule-sm c-success">Low</span>
                                <span class="capsule-sm c-danger">High</span>
                                <span class="capsule-sm c-warning">Medium</span>
                                <span class="capsule-sm c-info">Normal</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables -->
                <div class="pt-6 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-secondary mb-4 uppercase tracking-wider">Data Tables</h3>
                    <div class="table-container border border-border">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="table-header w-12"><input type="checkbox" class="checkbox-field"></th>
                                    <th class="table-header">Application ID</th>
                                    <th class="table-header">Applicant</th>
                                    <th class="table-header">Status</th>
                                    <th class="table-header text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-row">
                                    <td class="table-cell"><input type="checkbox" class="checkbox-field"></td>
                                    <td class="table-cell font-consolas text-primary font-medium">BP-2024-001</td>
                                    <td class="table-cell">Nguyễn Văn A</td>
                                    <td class="table-cell"><span class="capsule-lg c-success">Approved</span></td>
                                    <td class="table-cell text-right"><button class="btn-ghost !px-4 !py-2 !text-sm">View</button></td>
                                </tr>
                                <tr class="table-row">
                                    <td class="table-cell"><input type="checkbox" class="checkbox-field"></td>
                                    <td class="table-cell font-consolas text-primary font-medium">BP-2024-002</td>
                                    <td class="table-cell">Trần Thị B</td>
                                    <td class="table-cell"><span class="capsule-lg c-warning">Pending</span></td>
                                    <td class="table-cell text-right"><button class="btn-ghost !px-4 !py-2 !text-sm">View</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Interactive example using Alpine -->
        <section class="card-container" x-data="{ show: false }">
            <h2 class="page-header border-b pb-2">4. Admin Alpine.js Example</h2>
            <button @click="show = !show" class="btn-primary">
                Toggle Admin Modal
            </button>

            <div x-show="show" style="display: none;" class="mt-4 p-4 border border-blue-200 bg-blue-50 rounded-lg">
                <p class="font-inter text-primary">Đây là một Component động sử dụng Alpine.js bên trong Blade. Nó dùng chung class <code class="font-consolas bg-white px-1 rounded">btn-primary</code> như React.</p>
            </div>
        </section>
    </div>
</body>
</html>

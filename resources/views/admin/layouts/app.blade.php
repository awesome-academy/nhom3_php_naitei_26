<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/admin/app.js'])
</head>
<body class="min-h-screen bg-admin-page font-inter text-slate-900">
    <header class="bg-primary text-white shadow-sm">
        <div class="mx-auto flex min-h-16 max-w-7xl items-center gap-1 px-2 sm:gap-5 sm:px-6 lg:px-8">
            <a href="{{ route('admin.dashboard') }}" class="shrink-0 text-sm font-bold tracking-tight text-white">
                <span class="sm:hidden">PSMS</span>
                <span class="hidden sm:inline">{{ config('app.name') }}</span>
            </a>

            <nav class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto" aria-label="Điều hướng quản trị">
                <a
                    href="{{ route('admin.dashboard') }}"
                    @class([
                        'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                        'bg-white/20 text-white' => request()->routeIs('admin.dashboard'),
                        'text-white/65 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.dashboard'),
                    ])
                >
                    Tổng quan
                </a>

                @can('viewAny', \App\Models\Application::class)
                    <a
                        href="{{ route('admin.applications.index') }}"
                        @class([
                            'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                            'bg-white/20 text-white' => request()->routeIs('admin.applications.*'),
                            'text-white/65 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.applications.*'),
                        ])
                    >
                        Hồ sơ
                    </a>
                @endcan

                @can('viewAny', \App\Models\Department::class)
                    <a
                        href="{{ route('admin.departments.index') }}"
                        @class([
                            'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                            'bg-white/20 text-white' => request()->routeIs('admin.departments.*'),
                            'text-white/65 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.departments.*'),
                        ])
                    >
                        Phòng ban
                    </a>
                @endcan

                @can('viewAny', \App\Models\ServiceCategory::class)
                    <a
                        href="{{ route('admin.service-categories.index') }}"
                        @class([
                            'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                            'bg-white/20 text-white' => request()->routeIs('admin.service-categories.*'),
                            'text-white/65 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.service-categories.*'),
                        ])
                    >
                        Danh mục
                    </a>
                @endcan

                @can('viewAny', \App\Models\ServiceType::class)
                    <a
                        href="{{ route('admin.service-types.index') }}"
                        @class([
                            'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                            'bg-white/20 text-white' => request()->routeIs('admin.service-types.*'),
                            'text-white/65 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.service-types.*'),
                        ])
                    >
                        Dịch vụ
                    </a>
                @endcan

                @can('viewAny', \App\Models\User::class)
                    <a
                        href="{{ route('admin.users.index') }}"
                        @class([
                            'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                            'bg-white/20 text-white' => request()->routeIs('admin.users.index', 'admin.users.show'),
                            'text-white/65 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.users.index', 'admin.users.show'),
                        ])
                    >
                        Người dùng
                    </a>
                @endcan

                <a
                    href="{{ route('admin.users.import') }}"
                    @class([
                        'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                        'bg-white/20 text-white' => request()->routeIs('admin.users.import*'),
                        'text-white/65 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.users.import*'),
                    ])
                >
                    Nhập dữ liệu CSV
                </a>

                <a
                    href="{{ route('admin.activity-logs.index') }}"
                    @class([
                        'whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition-colors',
                        'bg-white/20 text-white' => request()->routeIs('admin.activity-logs.*'),
                        'text-white/65 hover:bg-white/10 hover:text-white' => ! request()->routeIs('admin.activity-logs.*'),
                    ])
                >
                    Nhật ký
                </a>
            </nav>

            <div class="flex shrink-0 items-center gap-1 sm:gap-3">
                <span class="hidden max-w-40 truncate text-sm font-medium text-white/80 md:inline">
                    {{ auth()->user()?->name }}
                </span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="rounded-lg px-1 py-2 text-sm font-semibold text-white/80 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/70 sm:px-3" type="submit">
                        Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8" x-data>
        @if (session('success'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <p class="font-semibold">Không thể hoàn tất thao tác.</p>
                <p class="mt-1">Vui lòng kiểm tra lại các trường được đánh dấu và thử lại.</p>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('emblem-vietnam.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/admin/app.js'])
</head>
<body class="min-h-screen bg-admin-page font-inter text-slate-900">
    <header class="bg-primary text-white shadow-sm">
        <div class="flex w-full flex-wrap items-center gap-x-3 gap-y-2 px-4 py-2 sm:gap-x-4 xl:flex-nowrap">
            <a href="{{ route('admin.dashboard') }}" class="flex shrink-0 items-center gap-3 text-white">
                <img
                    class="h-11 w-11 shrink-0 object-contain"
                    src="{{ asset('emblem-vietnam.svg') }}"
                    alt="Quốc huy Việt Nam"
                >
                <span class="hidden sm:block">
                    <span class="block text-base font-bold leading-tight tracking-tight text-white">Cổng Dịch Vụ Công</span>
                    <span class="mt-0.5 block text-[9px] font-semibold tracking-[0.12em] text-white/60">PHỤC VỤ NGƯỜI DÂN</span>
                </span>
            </a>

            <nav class="order-3 flex w-full min-w-0 flex-wrap items-center justify-center gap-1 md:order-none md:w-auto md:flex-1 xl:flex-nowrap" aria-label="Điều hướng quản trị">
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
                    Nhập CSV
                </a>

                @if (auth()->user()?->isSuperAdmin())
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
                @endif
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

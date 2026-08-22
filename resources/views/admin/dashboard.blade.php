@extends('admin.layouts.app')

@section('title', 'Tổng quan vận hành')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-gray-950">Tổng quan vận hành</h1>
        <p class="mt-1 max-w-3xl text-sm text-gray-600">
            Số liệu chỉ bao gồm các hồ sơ thuộc phạm vi bạn được phép xem và được cập nhật trực tiếp từ dữ liệu hiện tại.
        </p>
    </div>

    @if (!empty($claimableCount) && $claimableCount > 0)
        <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800" role="status">
            Bạn có <strong>{{ number_format($claimableCount) }}</strong> hồ sơ chưa gán trong phòng ban có thể nhận xử lý.
            <a href="{{ route('admin.applications.index') }}#claimable" class="font-semibold underline hover:text-blue-900">Xem ngay</a>
        </div>
    @endif

    <section
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
        aria-labelledby="application-metrics-title"
    >
        <h2 id="application-metrics-title" class="sr-only">Thống kê hồ sơ</h2>

        @foreach ($metricCards as $key => $card)
            <a
                href="{{ $card['url'] }}"
                class="admin-card group block min-h-44 transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
                aria-label="{{ $card['label'] }}: {{ number_format($metrics[$key]) }} hồ sơ. Xem danh sách"
            >
                <article class="admin-card-body flex h-full flex-col">
                    <p class="text-sm font-semibold text-gray-700">{{ $card['label'] }}</p>
                    <p class="mt-3 text-3xl font-bold tabular-nums {{ $card['accent'] }}">
                        {{ number_format($metrics[$key]) }}
                    </p>
                    <p class="mt-3 flex-1 text-sm leading-6 text-gray-600">{{ $card['description'] }}</p>
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-700 group-hover:text-blue-800">
                        Xem danh sách
                        <span aria-hidden="true">&rarr;</span>
                    </span>
                </article>
            </a>
        @endforeach
    </section>

    <p class="mt-5 text-sm text-gray-500">
        “Đã hoàn thành” gồm hồ sơ đã duyệt và đã từ chối. “Quá hạn” chỉ gồm hồ sơ chưa hoàn thành đã vượt thời gian xử lý quy định của dịch vụ.
    </p>
@endsection

@props([
    'id',
    'title',
    'description' => null,
    'closeLabel' => 'Đóng hộp thoại',
    'size' => 'lg',
])

@php
    $sizeClass = match ($size) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        'full' => 'max-w-[95vw]',
        default => $size,
    };
@endphp
<dialog
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-title"
    @if ($description) aria-describedby="{{ $id }}-description" @endif
    {{ $attributes->class("m-auto w-[calc(100%-2rem)] {$sizeClass} rounded-2xl border border-border bg-white p-0 text-gray-900 shadow-xl backdrop:bg-slate-950/50") }}
>
    <div class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
        <div>
            <h2 id="{{ $id }}-title" class="text-lg font-bold text-gray-950">
                {{ $title }}
            </h2>
            @if ($description)
                <p id="{{ $id }}-description" class="mt-1 text-sm leading-5 text-gray-600">
                    {{ $description }}
                </p>
            @endif
        </div>

        <form method="dialog">
            <button
                type="submit"
                class="inline-flex size-10 items-center justify-center rounded-lg text-2xl leading-none text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"
                aria-label="{{ $closeLabel }}"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </form>
    </div>

    <div class="px-5 py-5">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex flex-wrap justify-end gap-3 border-t border-border bg-gray-50 px-5 py-4">
            {{ $footer }}
        </div>
    @endisset
</dialog>

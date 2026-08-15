@props([
    'file' => '',
    /** @var list<array{old?: int|null, cur?: int|null, type?: string, text: string}> */
    'rows' => [],
])

@php
    $lines = collect($rows ?? [])
        ->filter(fn ($row) => filled(data_get($row, 'text')))
        ->values()
        ->map(fn ($row) => [
            'old' => data_get($row, 'old'),
            'cur' => data_get($row, 'cur') ?? data_get($row, 'new'),
            'type' => data_get($row, 'type', 'ctx'),
            'text' => (string) data_get($row, 'text'),
        ])
        ->all();

    $fileName = (string) $file;
    $added = collect($lines)->where('type', 'add')->count();
    $removed = collect($lines)->where('type', 'del')->count();
@endphp

<div
    {{ $attributes->class(['nk-diff'])->merge([
        'data-slot' => 'file-diff',
    ]) }}
>
    <div class="nk-diff-head">
        <span class="nk-diff-file-wrap">
            <svg class="nk-diff-icon" viewBox="0 0 24 24" width="15" height="15" aria-hidden="true">
                <path
                    d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
            <span class="nk-diff-file">{{ $fileName !== '' ? $fileName : neura_trans('untitledFile') }}</span>
        </span>
        <span class="nk-diff-stat">
            <span class="nk-diff-add">+{{ $added }}</span>
            <span class="nk-diff-del">-{{ $removed }}</span>
        </span>
    </div>

    <div class="nk-diff-body" role="table" aria-label="{{ neura_trans('fileDiffAria', ['file' => $fileName !== '' ? $fileName : 'file']) }}">
        @forelse ($lines as $row)
            @php
                $rowClass = match ($row['type']) {
                    'add' => 'nk-diff-row-add',
                    'del' => 'nk-diff-row-del',
                    default => '',
                };
                $sign = match ($row['type']) {
                    'add' => '+',
                    'del' => '-',
                    default => '',
                };
            @endphp
            <div class="nk-diff-row {{ $rowClass }}">
                <span class="nk-diff-ln-old">{{ $row['old'] ?? '' }}</span>
                <span class="nk-diff-ln-new">{{ $row['cur'] ?? '' }}</span>
                <span class="nk-diff-sign">{{ $sign }}</span>
                <code class="nk-diff-code">{{ $row['text'] }}</code>
            </div>
        @empty
            <div class="nk-diff-row">
                <span class="nk-diff-ln-old"></span>
                <span class="nk-diff-ln-new"></span>
                <span class="nk-diff-sign"></span>
                <code class="nk-diff-code text-fg-muted">{{ neura_trans('fileDiffEmpty') }}</code>
            </div>
        @endforelse
    </div>
</div>

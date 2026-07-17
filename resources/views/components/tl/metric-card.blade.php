@props([
    'icon' => null,
    'label',
    'value',
    'detail' => null,
    'tone' => 'lime',
])

<div {{ $attributes->class(['tl-metric-card', 'tl-tone-'.$tone]) }}>
    <div class="tl-metric-icon">
        @if($icon)
            <i class="{{ $icon }}"></i>
        @else
            <i class="fa-solid fa-chart-simple"></i>
        @endif
    </div>
    <div>
        <span>{{ $label }}</span>
        <strong>{{ $value }}</strong>
        @if($detail)
            <small>{{ $detail }}</small>
        @endif
    </div>
</div>

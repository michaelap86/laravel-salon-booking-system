@props(['name', 'color' => null, 'size' => 'size-10 text-sm'])
@php
    $palette = ['#e11d48', '#7c3aed', '#0ea5e9', '#ec4899', '#f97316', '#10b981', '#f59e0b', '#14b8a6'];
    $bg = $color ?: $palette[crc32($name) % count($palette)];
@endphp
<span {{ $attributes->merge(['class' => "avatar $size"]) }} style="background-image: linear-gradient(135deg, {{ $bg }}, color-mix(in srgb, {{ $bg }} 55%, #3b1d2e));">{{ initials($name) }}</span>

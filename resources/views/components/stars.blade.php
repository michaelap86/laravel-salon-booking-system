@props(['rating' => 5, 'size' => 'size-4'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} aria-label="{{ $rating }} out of 5">
    @for ($i = 1; $i <= 5; $i++)
        <svg class="{{ $size }} {{ $i <= round($rating) ? 'text-gold-500' : 'text-current opacity-20' }}" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    @endfor
</span>

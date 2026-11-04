@props(['status'])
@php $s = $status instanceof \App\Enums\AppointmentStatus ? $status : \App\Enums\AppointmentStatus::from($status); @endphp
<span {{ $attributes->merge(['class' => 'a-badge b-'.$s->color()]) }}>{{ $s->label() }}</span>

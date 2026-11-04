@extends('layouts.admin')
@section('title', 'Messages')
@section('heading', 'Messages')
@section('subheading', 'Enquiries from the contact form')

@section('content')
<div class="a-card divide-y divide-line">
    @forelse ($messages as $m)
        <a href="{{ route('admin.messages.show', $m) }}" class="flex items-start gap-4 px-6 py-4 transition hover:bg-surface-2">
            <x-avatar :name="$m->name" size="size-10 text-sm" />
            <div class="min-w-0 flex-1"><div class="flex items-center gap-2"><b class="truncate {{ $m->read_at ? 'font-medium' : '' }}">{{ $m->subject }}</b>@unless ($m->read_at)<span class="size-2 flex-none rounded-full bg-brand"></span>@endunless</div><p class="truncate text-sm text-muted">{{ $m->name }} · {{ $m->body }}</p></div>
            <span class="whitespace-nowrap text-xs text-muted">{{ $m->created_at->diffForHumans() }}</span>
        </a>
    @empty
        <div class="py-16 text-center text-muted">No messages yet.</div>
    @endforelse
    {{ $messages->links() }}
</div>
@endsection

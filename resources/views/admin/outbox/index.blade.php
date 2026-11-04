@extends('layouts.admin')
@section('title', 'Email outbox')
@section('heading', 'Email outbox')
@section('subheading', 'Every email the system sends: confirmations, reminders and cancellations')

@section('content')
<div class="a-card">
    <div class="flex gap-3 border-b border-line bg-brand-soft px-6 py-3 text-sm text-brand"><x-icon name="info" class="mt-0.5 size-4 flex-none" /> This is a demo, so emails are kept here instead of being delivered. In production they would go out through any SMTP or API provider.</div>
    <div class="divide-y divide-line">
        @forelse ($emails as $e)
            <a href="{{ route('admin.outbox.show', $e) }}" class="flex items-center gap-4 px-6 py-4 transition hover:bg-surface-2">
                <span class="grid size-10 flex-none place-items-center rounded-full bg-brand-soft text-brand"><x-icon name="mail" class="size-[18px]" /></span>
                <div class="min-w-0 flex-1"><b class="block truncate">{{ $e->subject }}</b><span class="block truncate text-sm text-muted">To {{ $e->to }}</span></div>
                <span class="whitespace-nowrap text-xs text-muted">{{ $e->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <div class="py-16 text-center text-muted">No emails yet. Book an appointment to see one appear here.</div>
        @endforelse
    </div>
    {{ $emails->links() }}
</div>
@endsection

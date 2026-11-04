@extends('layouts.admin')
@section('title', $message->subject)
@section('heading', $message->subject)
@section('subheading', 'From '.$message->name.' · '.$message->created_at->format('M j, Y g:i A'))
@section('actions')<a href="{{ route('admin.messages.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="size-4" /> Inbox</a>@endsection

@section('content')
<div class="a-card max-w-3xl p-8">
    <div class="flex items-center gap-4"><x-avatar :name="$message->name" size="size-12" /><div><b>{{ $message->name }}</b><p class="text-sm text-muted">{{ $message->email }}</p></div></div>
    <p class="mt-6 whitespace-pre-line leading-relaxed">{{ $message->body }}</p>
    <div class="mt-8 flex gap-3 border-t border-line pt-6">
        <a href="mailto:{{ $message->email }}?subject=Re: {{ $message->subject }}" class="a-btn a-btn-primary"><x-icon name="mail" class="size-4" /> Reply by email</a>
        <form method="post" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Delete this message?">@csrf @method('DELETE')<button class="a-btn a-btn-ghost text-rose-500"><x-icon name="trash" class="size-4" /> Delete</button></form>
    </div>
</div>
@endsection

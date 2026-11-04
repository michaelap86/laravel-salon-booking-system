@extends('layouts.admin')
@section('title', $email->subject)
@section('heading', $email->subject)
@section('subheading', 'To '.$email->to.' · '.$email->created_at->format('M j, Y g:i A'))
@section('actions')<a href="{{ route('admin.outbox') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="size-4" /> Outbox</a>@endsection

@section('content')
<div class="a-card overflow-hidden"><iframe title="Email preview" srcdoc="{{ $email->html }}" sandbox class="h-[760px] w-full bg-white"></iframe></div>
@endsection

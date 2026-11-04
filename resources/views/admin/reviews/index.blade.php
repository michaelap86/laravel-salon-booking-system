@extends('layouts.admin')
@section('title', 'Reviews')
@section('heading', 'Reviews')
@section('subheading', number_format($summary['total']).' reviews · '.$summary['avg'].' average')

@section('content')
<div class="grid gap-6 lg:grid-cols-[320px_1fr]">
    <div class="a-card h-fit p-6">
        <p class="font-serif text-5xl font-semibold">{{ number_format($summary['avg'], 1) }}</p><x-stars :rating="$summary['avg']" size="size-5" class="mt-1" />
        <ul class="mt-5 space-y-2">
            @foreach ([5, 4, 3, 2, 1] as $n)
                @php $c = $summary['dist'][$n] ?? 0; @endphp
                <li class="flex items-center gap-3 text-sm"><span class="w-3">{{ $n }}</span><span class="h-2 flex-1 overflow-hidden rounded-full bg-surface-2"><span class="block h-full rounded-full bg-gold-500" style="width: {{ $summary['total'] ? $c / $summary['total'] * 100 : 0 }}%"></span></span><span class="w-10 text-right text-xs text-muted">{{ $c }}</span></li>
            @endforeach
        </ul>
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach (['all' => 'All', 'unanswered' => 'Needs reply', 'low' => '1-2 stars', 'hidden' => 'Hidden ('.$summary['hidden'].')'] as $k => $l)<a href="?filter={{ $k }}" class="a-btn a-btn-sm {{ $filter === $k ? 'a-btn-primary' : 'a-btn-ghost' }}">{{ $l }}</a>@endforeach
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($reviews as $r)
            <article class="a-card p-5 {{ $r->published ? '' : 'opacity-70' }}" x-data="{ reply: false }">
                <div class="flex flex-wrap items-start gap-4">
                    <x-avatar :name="$r->client->name" size="size-11 text-sm" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1"><b>{{ $r->client->name }}</b><x-stars :rating="$r->rating" size="size-3.5" /><span class="text-xs text-muted">{{ $r->created_at->format('M j, Y') }}</span>@unless ($r->published)<span class="a-badge b-slate">Hidden</span>@endunless</div>
                        <p class="text-xs text-muted">{{ $r->service->name }} with {{ $r->staff->name }}</p>
                        <p class="mt-2 text-sm leading-relaxed">{{ $r->comment }}</p>
                        @if ($r->reply)<p class="mt-3 rounded-xl bg-brand-soft p-3 text-sm text-brand"><b>Your reply:</b> {{ $r->reply }}</p>@endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="a-btn a-btn-ghost a-btn-sm" @click="reply = !reply">{{ $r->reply ? 'Edit reply' : 'Reply' }}</button>
                        <form method="post" action="{{ route('admin.reviews.publish', $r) }}">@csrf<button class="a-btn a-btn-ghost a-btn-sm" title="{{ $r->published ? 'Hide from website' : 'Show on website' }}"><x-icon :name="$r->published ? 'eye' : 'check'" class="size-3.5" /> {{ $r->published ? 'Hide' : 'Publish' }}</button></form>
                    </div>
                </div>
                <form x-show="reply" x-cloak x-collapse method="post" action="{{ route('admin.reviews.reply', $r) }}" class="mt-4 flex gap-3 border-t border-line pt-4">@csrf<input name="reply" value="{{ $r->reply }}" class="a-input" placeholder="Write a public reply…" required><button class="a-btn a-btn-primary">Send</button></form>
            </article>
        @empty
            <div class="a-card py-16 text-center text-muted">No reviews match.</div>
        @endforelse
        {{ $reviews->links() }}
    </div>
</div>
@endsection

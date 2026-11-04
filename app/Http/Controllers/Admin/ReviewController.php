<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');

        return view('admin.reviews.index', [
            'filter' => $filter,
            'reviews' => Review::query()->with(['client:id,name', 'staff:id,name,color', 'service:id,name'])
                ->when($filter === 'low', fn ($q) => $q->where('rating', '<=', 2))
                ->when($filter === 'hidden', fn ($q) => $q->where('published', false))
                ->when($filter === 'unanswered', fn ($q) => $q->whereNull('reply'))
                ->latest()->paginate(15)->withQueryString(),
            'summary' => [
                'avg' => round((float) Review::query()->avg('rating'), 2),
                'total' => Review::query()->count(),
                'dist' => Review::query()->selectRaw('rating, count(*) c')->groupBy('rating')->pluck('c', 'rating'),
                'hidden' => Review::query()->where('published', false)->count(),
            ],
        ]);
    }

    public function publish(Review $review): RedirectResponse
    {
        $review->update(['published' => ! $review->published]);
        Activity::log('review', $review->client->name, ($review->published ? 'Published' : 'Hid').' a review by '.$review->client->name, route('admin.reviews.index'));

        return back()->with('success', $review->published ? 'Review is now visible on the website.' : 'Review hidden from the website.');
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $review->update($request->validate(['reply' => 'required|string|max:600']));

        return back()->with('success', 'Reply saved.');
    }
}

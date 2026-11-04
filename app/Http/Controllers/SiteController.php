<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Message;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Services\Availability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(Availability $availability): View
    {
        $stats = Cache::remember('home-stats', 300, fn () => [
            'clients' => Client::query()->count(),
            'reviews' => Review::query()->where('published', true)->count(),
            'rating' => round((float) Review::query()->where('published', true)->avg('rating'), 1),
        ]);

        return view('site.home', [
            'stats' => $stats,
            'categories' => ServiceCategory::query()->with(['services' => fn ($q) => $q->active()->orderBy('sort')])->orderBy('sort')->get(),
            'featured' => Service::query()->active()->where('featured', true)->with('category')->orderBy('sort')->get(),
            'staff' => $this->staffWithRatings(),
            'reviews' => Review::query()->with(['client:id,name', 'service:id,name', 'staff:id,name'])
                ->where('published', true)->where('rating', 5)->whereRaw('length(comment) > 70')->latest()->limit(9)->get(),
            'next' => Cache::remember('next-available', 120, fn () => $this->nextAvailable($availability)),
        ]);
    }

    public function services(Request $request): View
    {
        return view('site.services', [
            'categories' => ServiceCategory::query()->with(['services' => fn ($q) => $q->active()->orderBy('sort')])->orderBy('sort')->get(),
            'active' => $request->query('cat', 'all'),
        ]);
    }

    public function service(Service $service): View
    {
        abort_unless($service->active, 404);
        $service->load('category');

        return view('site.service', [
            'service' => $service,
            'staff' => $service->staff()->where('staff.active', true)->orderBy('staff.sort')->get(),
            'reviews' => $service->reviews()->with('client:id,name', 'staff:id,name')->where('published', true)->latest()->limit(6)->get(),
            'rating' => round((float) $service->reviews()->where('published', true)->avg('rating'), 1),
            'reviewCount' => $service->reviews()->where('published', true)->count(),
            'related' => Service::query()->active()->where('service_category_id', $service->service_category_id)->where('id', '!=', $service->id)->orderBy('sort')->limit(3)->get(),
        ]);
    }

    public function team(): View
    {
        return view('site.team', ['staff' => $this->staffWithRatings(true)]);
    }

    public function member(Staff $staff): View
    {
        abort_unless($staff->active, 404);

        return view('site.member', [
            'staff' => $staff,
            'services' => $staff->services()->active()->with('category')->orderBy('sort')->get(),
            'reviews' => $staff->reviews()->with('client:id,name', 'service:id,name')->where('published', true)->latest()->limit(6)->get(),
            'rating' => round((float) $staff->reviews()->where('published', true)->avg('rating'), 1),
            'reviewCount' => $staff->reviews()->where('published', true)->count(),
        ]);
    }

    public function contact(): View
    {
        return view('site.contact');
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'subject' => 'required|string|max:160',
            'body' => 'required|string|min:10|max:3000',
        ]);
        Message::query()->create($data);
        Activity::log('message', $data['subject'], $data['name'].' sent a message', route('admin.messages.index'));

        return redirect()->route('contact')->with('success', 'Thank you! Your message is on its way to the front desk.');
    }

    private function staffWithRatings(bool $withServices = false)
    {
        return Staff::query()->active()
            ->withAvg(['reviews as rating' => fn ($q) => $q->where('published', true)], 'rating')
            ->withCount(['reviews as review_count' => fn ($q) => $q->where('published', true)])
            ->when($withServices, fn ($q) => $q->with(['services.category']))
            ->get();
    }

    /** The earliest open slot for a popular service, shown on the hero card. */
    private function nextAvailable(Availability $availability): ?array
    {
        $service = Service::query()->active()->where('slug', 'gel-manicure')->first() ?? Service::query()->active()->first();
        if (! $service) {
            return null;
        }
        $dates = $availability->openDates($service, null, today(), today()->addDays(14));
        if (! $dates) {
            return null;
        }
        $day = \Illuminate\Support\Carbon::parse(array_key_first($dates));
        $slot = $availability->slots($service, null, $day)[0] ?? null;

        return $slot ? ['service' => $service->name, 'when' => ($day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tomorrow' : $day->format('D, M j'))).' at '.$slot['label']] : null;
    }
}

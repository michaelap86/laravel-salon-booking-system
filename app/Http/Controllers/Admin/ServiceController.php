<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', [
            'categories' => ServiceCategory::query()->with(['services' => fn ($q) => $q->withCount('appointments')->with('staff:id')])->orderBy('sort')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', $this->formData(new Service(['duration' => 60, 'buffer' => 0, 'price' => 5000, 'deposit_required' => true, 'active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $service = Service::query()->create([...$this->validated($request), 'slug' => $this->slug($request->input('name'))]);
        $service->staff()->sync($request->input('staff', []));
        Activity::log('service', $service->name, 'Created service '.$service->name, route('admin.services.edit', $service));

        return redirect()->route('admin.services.index')->with('success', 'Service created.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', $this->formData($service));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $old = $service->price;
        $service->update($this->validated($request));
        $service->staff()->sync($request->input('staff', []));
        Activity::log('service', $service->name, $old !== $service->price ? 'Changed price of '.$service->name.' to '.money($service->price) : 'Updated '.$service->name, route('admin.services.edit', $service));

        return redirect()->route('admin.services.index')->with('success', 'Service saved.');
    }

    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['active' => ! $service->active]);
        Activity::log('service', $service->name, ($service->active ? 'Activated ' : 'Deactivated ').$service->name, route('admin.services.edit', $service));

        return back()->with('success', $service->name.($service->active ? ' is now bookable.' : ' is hidden from the website.'));
    }

    private function formData(Service $service): array
    {
        return [
            'service' => $service,
            'categories' => ServiceCategory::query()->orderBy('sort')->get(),
            'staff' => Staff::query()->active()->get(['id', 'name', 'title', 'color']),
            'assigned' => $service->exists ? $service->staff()->pluck('staff.id')->all() : [],
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:120',
            'description' => 'required|string|max:600',
            'duration' => 'required|integer|min:15|max:480',
            'buffer' => 'required|integer|min:0|max:60',
            'price' => 'required|numeric|min:0|max:5000',
        ]);
        $data['price'] = (int) round($data['price'] * 100);

        return [...$data, 'deposit_required' => $request->boolean('deposit_required'), 'featured' => $request->boolean('featured'), 'active' => $request->boolean('active')];
    }

    private function slug(string $name): string
    {
        $slug = Str::slug($name);
        $i = 1;
        while (Service::query()->where('slug', $slug)->exists()) {
            $slug = Str::slug($name).'-'.(++$i);
        }

        return $slug;
    }
}

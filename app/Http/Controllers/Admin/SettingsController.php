<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\BusinessHour;
use App\Models\Closure;
use App\Models\PromoCode;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(): View
    {
        return view('admin.settings', [
            'settings' => Setting::all_(),
            'hours' => BusinessHour::query()->orderByRaw('CASE weekday WHEN 0 THEN 7 ELSE weekday END')->get(),
            'closures' => Closure::query()->where('date', '>=', today())->orderBy('date')->get(),
            'promos' => PromoCode::query()->orderByDesc('active')->orderBy('code')->get(),
        ]);
    }

    public function booking(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slot_interval' => 'required|integer|in:5,10,15,20,30,60', 'booking_window_days' => 'required|integer|min:7|max:365',
            'min_notice_hours' => 'required|integer|min:0|max:72', 'deposit_percent' => 'required|integer|min:0|max:100',
            'free_cancel_hours' => 'required|integer|min:0|max:168', 'loyalty_dollars_per_point' => 'required|integer|min:1|max:1000',
        ]);
        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }
        Activity::log('settings', 'Booking rules', 'Updated booking rules', route('admin.settings'));

        return back()->with('success', 'Booking rules saved.');
    }

    public function hours(Request $request): RedirectResponse
    {
        $rows = $request->validate(['days' => 'required|array', 'days.*.opens' => 'nullable|date_format:H:i', 'days.*.closes' => 'nullable|date_format:H:i'])['days'];
        foreach (range(0, 6) as $wd) {
            $d = $rows[$wd] ?? [];
            $open = ! empty($d['open']) && ! empty($d['opens']) && ! empty($d['closes']) && $d['closes'] > $d['opens'];
            BusinessHour::query()->updateOrCreate(['weekday' => $wd], ['is_open' => $open, 'opens' => $open ? $d['opens'] : null, 'closes' => $open ? $d['closes'] : null]);
        }
        Cache::forget('footer-hours');
        Activity::log('settings', 'Opening hours', 'Updated opening hours', route('admin.settings'));

        return back()->with('success', 'Opening hours saved.');
    }

    public function addClosure(Request $request): RedirectResponse
    {
        $data = $request->validate(['date' => 'required|date|after_or_equal:today|unique:closures,date', 'reason' => 'required|string|max:120']);
        Closure::query()->create($data);
        Activity::log('settings', $data['date'], 'Added a closure: '.$data['reason'], route('admin.settings'));

        return back()->with('success', 'Closure added. That day is no longer bookable.');
    }

    public function removeClosure(Closure $closure): RedirectResponse
    {
        $closure->delete();

        return back()->with('success', 'Closure removed.');
    }

    public function addPromo(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|max:30|alpha_dash|unique:promo_codes,code', 'percent' => 'required|integer|min:1|max:90', 'description' => 'required|string|max:120']);
        PromoCode::query()->create([...$data, 'code' => strtoupper($data['code'])]);

        return back()->with('success', 'Promo code created.');
    }

    public function togglePromo(PromoCode $promo): RedirectResponse
    {
        $promo->update(['active' => ! $promo->active]);

        return back()->with('success', $promo->code.($promo->active ? ' is active.' : ' is switched off.'));
    }
}

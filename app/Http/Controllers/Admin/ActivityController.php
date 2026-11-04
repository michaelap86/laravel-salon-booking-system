<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $f = $request->only(['action', 'user', 'q']);

        return view('admin.activity', [
            'items' => Activity::query()->with('user:id,name')
                ->when($f['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
                ->when($f['user'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
                ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('details', 'like', "%$s%")->orWhere('subject', 'like', "%$s%")->orWhere('ip', 'like', "%$s%")))
                ->latest('created_at')->latest('id')->paginate(25)->withQueryString(),
            'filters' => $f,
            'actions' => Activity::labels(),
            'people' => User::query()->whereIn('role', ['admin', 'manager', 'stylist'])->orderBy('name')->get(['id', 'name']),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SentEmail;
use Illuminate\View\View;

class OutboxController extends Controller
{
    public function index(): View
    {
        return view('admin.outbox.index', ['emails' => SentEmail::query()->latest('created_at')->latest('id')->paginate(15)]);
    }

    public function show(SentEmail $email): View
    {
        return view('admin.outbox.show', ['email' => $email]);
    }
}

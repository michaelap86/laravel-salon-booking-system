<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        return view('admin.messages.index', ['messages' => Message::query()->latest()->paginate(15)]);
    }

    public function show(Message $message): View
    {
        $message->update(['read_at' => $message->read_at ?? now()]);

        return view('admin.messages.show', ['message' => $message]);
    }

    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'Message deleted.');
    }
}

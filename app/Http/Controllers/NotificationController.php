<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $items = AppNotification::where('user_id', $request->user()->id)->latest()->paginate(30);
        return view('notifications.index', compact('items'));
    }

    public function readAll(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'Semua notifikasi ditandai telah dibaca.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SyncError;
use Illuminate\Http\Request;

class SyncErrorController extends Controller
{
    public function index(Request $request)
    {
        $query = SyncError::with('site')->latest('occurred_at')->limit(100);

        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->query('site_id'));
        }

        if ($request->filled('code')) {
            $query->where('code', $request->query('code'));
        }

        $errors = $query->get();

        $sites = Site::orderBy('name')->get(['id', 'name']);

        return view('sync-errors.index', compact('errors', 'sites'));
    }
}

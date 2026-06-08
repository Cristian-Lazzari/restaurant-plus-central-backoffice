<?php

namespace App\Http\Controllers;

use App\Models\Site;

class SnapshotController extends Controller
{
    public function index(Site $site)
    {
        $snapshots = $site->reportSnapshots()->orderBy('fetched_at', 'desc')->paginate(30);

        return view('snapshots.index', compact('site', 'snapshots'));
    }
}

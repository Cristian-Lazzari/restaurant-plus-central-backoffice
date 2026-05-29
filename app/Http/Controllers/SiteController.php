<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::with(['latestSnapshot', 'latestError'])
            ->orderBy('name')
            ->get();

        return view('sites.index', compact('sites'));
    }

    public function create()
    {
        return view('sites.create', ['site' => new Site()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048', 'starts_with:https://'],
            'token' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ], $this->validationMessages());

        $data['url'] = rtrim($data['url'], '/');
        $data['active'] = $request->boolean('active');
        $data['retention_days'] = $data['retention_days'] ?? 90;

        Site::create($data);

        return redirect()->route('dashboard')->with('success', 'Site created.');
    }

    public function show(Site $site)
    {
        $site->load([
            'latestSnapshot',
            'reportSnapshots' => fn ($query) => $query->latest('fetched_at')->limit(10),
            'syncErrors' => fn ($query) => $query->latest('occurred_at')->limit(10),
        ]);

        return view('sites.show', compact('site'));
    }

    public function edit(Site $site)
    {
        return view('sites.edit', compact('site'));
    }

    public function update(Request $request, Site $site)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048', 'starts_with:https://'],
            'token' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ], $this->validationMessages());

        $site->name = $data['name'];
        $site->url = rtrim($data['url'], '/');
        $site->active = $request->boolean('active');
        $site->notes = $data['notes'] ?? null;
        $site->retention_days = $data['retention_days'] ?? 90;

        if (! empty($data['token'])) {
            $site->token = $data['token'];
        }

        $site->save();

        return redirect()->route('sites.show', $site)->with('success', 'Site updated.');
    }

    public function toggle(Site $site)
    {
        $site->update(['active' => ! $site->active]);

        return back()->with('success', $site->active ? 'Site activated.' : 'Site deactivated.');
    }

    private function validationMessages(): array
    {
        return [
            'url.starts_with' => 'The dashboard URL must start with https://.',
        ];
    }
}

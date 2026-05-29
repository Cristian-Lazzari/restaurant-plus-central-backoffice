@csrf
<div class="field">
    <label for="name">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $site->name) }}" required>
</div>
<div class="field">
    <label for="url">Dashboard URL</label>
    <input id="url" name="url" type="url" value="{{ old('url', $site->url) }}" placeholder="https://dashboard.ristorante.it" required>
</div>
<div class="field">
    <label for="token">Private report token</label>
    <input id="token" name="token" type="password" autocomplete="new-password" placeholder="{{ $site->exists ? 'Leave empty to keep the current token' : '' }}" {{ $site->exists ? '' : 'required' }}>
</div>
<div class="field">
    <label class="inline">
        <input name="active" type="checkbox" value="1" @checked(old('active', $site->exists ? $site->active : true))>
        Active
    </label>
</div>
<div class="field">
    <label for="retention_days">Retention days</label>
    <input id="retention_days" name="retention_days" type="text" value="{{ old('retention_days', $site->retention_days ?? 90) }}">
    <div class="muted">For now this is stored only; automatic cleanup is reserved for V2.</div>
</div>
<div class="field">
    <label for="notes">Private notes</label>
    <textarea id="notes" name="notes" rows="4">{{ old('notes', $site->notes) }}</textarea>
</div>
<div class="actions">
    <button class="btn primary" type="submit">Save</button>
    <a class="btn" href="{{ $site->exists ? route('sites.show', $site) : route('dashboard') }}">Cancel</a>
</div>

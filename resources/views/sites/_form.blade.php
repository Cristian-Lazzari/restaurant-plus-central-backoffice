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
    <div class="actions" style="align-items: flex-start;">
        <div style="flex: 1 1 360px;">
            <input id="token" name="token" type="password" autocomplete="new-password" placeholder="{{ $site->exists ? 'Lascia vuoto per mantenere il token attuale' : '' }}" {{ $site->exists ? '' : 'required' }}>
        </div>
        <button class="btn" id="generate-report-token" type="button">Genera token</button>
        <button class="btn" id="copy-report-token" type="button" disabled>Copia token</button>
    </div>
    @if($site->exists)
        <div class="muted" style="margin-top: 6px;">Se rigeneri il token, ricordati di aggiornarlo anche nel .env della dashboard ristorante.</div>
    @endif
    <div id="report-token-snippet-wrap" style="display: none; margin-top: 10px;">
        <label for="report-token-snippet">Dashboard sorgente .env</label>
        <textarea id="report-token-snippet" rows="3" readonly></textarea>
        <div class="muted" style="margin-top: 6px;">Copia questo token nel file .env della dashboard ristorante, poi esegui php artisan optimize:clear e php artisan config:cache sulla dashboard sorgente.</div>
    </div>
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

<script>
    (function () {
        const tokenInput = document.getElementById('token');
        const generateButton = document.getElementById('generate-report-token');
        const copyButton = document.getElementById('copy-report-token');
        const snippetWrap = document.getElementById('report-token-snippet-wrap');
        const snippet = document.getElementById('report-token-snippet');

        function tokenSnippet(token) {
            return 'PRIVATE_REPORT_TOKEN=' + token + "\n" + 'PRIVATE_REPORT_REVENUE_UNIT=euros';
        }

        function updateSnippet(token) {
            snippet.value = tokenSnippet(token);
            snippetWrap.style.display = 'block';
            copyButton.disabled = false;
            copyButton.textContent = 'Copia token';
        }

        window.generateReportToken = function generateReportToken() {
            if (! window.crypto || ! window.crypto.getRandomValues) {
                alert('Generazione sicura non disponibile in questo browser.');
                return;
            }

            const bytes = new Uint8Array(32);
            window.crypto.getRandomValues(bytes);

            const token = Array.from(bytes)
                .map((byte) => byte.toString(16).padStart(2, '0'))
                .join('');

            tokenInput.value = token;
            updateSnippet(token);
        };

        generateButton.addEventListener('click', window.generateReportToken);

        copyButton.addEventListener('click', async function () {
            if (! snippet.value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(snippet.value);
                copyButton.textContent = 'Copiato';
            } catch (error) {
                snippet.focus();
                snippet.select();
                document.execCommand('copy');
                copyButton.textContent = 'Copiato';
            }

            setTimeout(function () {
                copyButton.textContent = 'Copia token';
            }, 1600);
        });
    })();
</script>

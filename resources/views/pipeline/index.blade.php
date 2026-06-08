@extends('layouts.app')

@section('content')

<style>
/* ─── Tabs ─── */
.pipe-tabs { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 1px solid var(--border-soft); }
.pipe-tab {
    padding: 10px 18px; border: none; background: transparent;
    color: var(--muted); font-size: 13px; font-weight: 600;
    cursor: pointer; border-bottom: 2px solid transparent;
    white-space: nowrap; transition: all .2s; font-family: inherit;
    margin-bottom: -1px;
}
.pipe-tab:hover { color: var(--ink); }
.pipe-tab.active { color: var(--brand); border-bottom-color: var(--brand); }

.pipe-panel { display: none; }
.pipe-panel.active { display: block; }

/* ─── Toolbar ─── */
.pipe-toolbar { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; align-items: center; }
.pipe-search {
    flex: 1; min-width: 180px; padding: 8px 12px;
    background: #fff; border: 1px solid var(--border); border-radius: var(--radius-sm);
    color: var(--ink); font-size: 13px; font-family: inherit; outline: none;
}
.pipe-search:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(14,183,146,.15); }
.pipe-select {
    padding: 8px 10px; background: #fff; border: 1px solid var(--border);
    border-radius: var(--radius-sm); color: var(--ink-2); font-size: 13px;
    font-family: inherit; outline: none; cursor: pointer;
}
.pipe-select:focus { border-color: var(--brand); }

/* ─── Status badges ─── */
.s-badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
.s-nuovo      { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.s-contattato { background: var(--amber-soft); color: var(--amber); border: 1px solid var(--amber-border); }
.s-interessato{ background: var(--green-soft); color: var(--green); border: 1px solid var(--green-border); }
.s-demo       { background: #fdf4ff; color: #7e22ce; border: 1px solid #e9d5ff; }
.s-proposta   { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.s-chiuso     { background: var(--green-soft); color: #15803d; border: 2px solid var(--green-border); }
.s-perso      { background: var(--red-soft); color: var(--red); border: 1px solid var(--red-border); }
.s-followup   { background: #fdf2ff; color: #a21caf; border: 1px solid #f0abfc; }

/* Source badge */
.src-badge { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 999px; white-space: nowrap; }
.src-smm      { background: var(--red-soft); color: var(--red); border: 1px solid var(--red-border); }
.src-ads      { background: var(--amber-soft); color: var(--amber); border: 1px solid var(--amber-border); }
.src-referral { background: var(--green-soft); color: var(--green); border: 1px solid var(--green-border); }
.src-organico { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.src-webinar  { background: #fdf4ff; color: #7e22ce; border: 1px solid #e9d5ff; }
.src-diretto  { background: var(--surface-2); color: var(--muted); border: 1px solid var(--border); }

/* Pacchetto */
.pack-badge { font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 999px; white-space: nowrap; }
.pack-base  { background: var(--surface-2); color: var(--muted); border: 1px solid var(--border); }
.pack-inter { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.pack-top   { background: #fdf4ff; color: #7e22ce; border: 1px solid #e9d5ff; }

/* Priorità dot */
.prio-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.p-alta   { background: var(--red); }
.p-media  { background: var(--amber); }
.p-bassa  { background: var(--green); }

/* SMM status */
.smm-s-nuovo      { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.smm-s-contattato { background: var(--amber-soft); color: var(--amber); border: 1px solid var(--amber-border); }
.smm-s-interessato{ background: var(--green-soft); color: var(--green); border: 1px solid var(--green-border); }
.smm-s-partner    { background: var(--green-soft); color: #15803d; border: 2px solid var(--green-border); }
.smm-s-rifiutato  { background: var(--red-soft); color: var(--red); border: 1px solid var(--red-border); }

/* Funnel */
.funnel-wrap { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 20px; }
.funnel-col { flex-shrink: 0; width: 170px; }
.funnel-header { font-size: 11px; font-weight: 760; text-transform: uppercase; letter-spacing: .07em; padding: 8px 10px; border-radius: var(--radius-sm); margin-bottom: 8px; text-align: center; }
.funnel-count { font-size: 1.4rem; font-weight: 900; line-height: 1.1; }
.funnel-items { display: flex; flex-direction: column; gap: 6px; }
.funnel-item {
    background: var(--surface); border: 1px solid var(--border-soft);
    border-radius: var(--radius-sm); padding: 8px 10px; font-size: 12px;
    color: var(--ink-2); cursor: pointer; transition: all .1s;
}
.funnel-item:hover { background: var(--surface-2); border-color: var(--brand-muted); }
.funnel-item .fi-name { font-weight: 700; color: var(--ink); margin-bottom: 2px; }
.funnel-item .fi-meta { font-size: 11px; color: var(--muted); }
.funnel-add {
    width: 100%; padding: 6px; border: 1px dashed var(--border); background: transparent;
    border-radius: var(--radius-sm); font-size: 12px; color: var(--muted);
    cursor: pointer; font-family: inherit; margin-top: 4px; transition: all .15s;
}
.funnel-add:hover { border-color: var(--brand); color: var(--brand); }

/* KPI */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px,1fr)); gap: 12px; margin-bottom: 20px; }
.kpi-card { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 16px; text-align: center; box-shadow: var(--shadow-sm); }
.kpi-val { font-size: 1.7rem; font-weight: 900; color: var(--brand); line-height: 1; }
.kpi-lbl { font-size: 11px; color: var(--muted); margin-top: 4px; }
.kpi-sub { font-size: 10.5px; color: var(--green); margin-top: 3px; font-weight: 700; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 200; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.hidden { display: none; }
.modal-box { background: #fff; border: 1px solid var(--border-soft); border-radius: 12px; width: 100%; max-width: 660px; max-height: 92vh; overflow-y: auto; box-shadow: var(--shadow); }
.modal-hdr { padding: 18px 22px 14px; border-bottom: 1px solid var(--border-soft); display: flex; align-items: center; justify-content: space-between; }
.modal-ttl { font-size: 15px; font-weight: 780; color: var(--ink); }
.modal-cls { background: none; border: none; color: var(--muted); font-size: 1.1rem; cursor: pointer; padding: 4px 8px; border-radius: var(--radius-sm); }
.modal-cls:hover { background: var(--surface-2); color: var(--ink); }
.modal-bdy { padding: 18px 22px; }
.modal-ftr { padding: 12px 22px; border-top: 1px solid var(--border-soft); display: flex; gap: 8px; justify-content: flex-end; }
.form-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-full2 { grid-column: 1/-1; }
.f-label { display: block; font-weight: 600; font-size: 12px; margin-bottom: 5px; color: var(--ink-2); text-transform: uppercase; letter-spacing: .04em; }

/* Overdue */
.overdue-date { color: var(--red) !important; font-weight: 700; }

/* Empty */
.pipe-empty { text-align: center; padding: 50px 20px; color: var(--muted); }
.pipe-empty-icon { font-size: 2.5rem; margin-bottom: 10px; }

/* Banner retention */
.retention-banner {
    background: var(--red-soft); border: 1px solid var(--red-border);
    border-left: 4px solid var(--red); border-radius: var(--radius);
    padding: 14px 16px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 12px;
}
</style>

<div class="page-header">
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">/</span>
        <span>Pipeline CRM</span>
    </div>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Pipeline CRM</h1>
            <p class="page-subtitle">Gestione lead, SMM partner e conversioni · aggiornato in tempo reale</p>
        </div>
    </div>
    {{-- Header stats (aggiornate via JS) --}}
    <div class="grid-4 mt-3" id="header-stats" style="display:grid">
        <div class="metric-card">
            <span class="metric-label">Lead totali</span>
            <span class="metric-value" id="stat-totali">—</span>
        </div>
        <div class="metric-card">
            <span class="metric-label">Lead attivi</span>
            <span class="metric-value" id="stat-attivi">—</span>
        </div>
        <div class="metric-card">
            <span class="metric-label">Clienti chiusi</span>
            <span class="metric-value" id="stat-chiusi">—</span>
        </div>
        <div class="metric-card">
            <span class="metric-label">ARR aggiunto</span>
            <span class="metric-value" id="stat-arr">—</span>
        </div>
        <div class="metric-card">
            <span class="metric-label">CAC medio</span>
            <span class="metric-value" id="stat-cac">—</span>
        </div>
        <div class="metric-card">
            <span class="metric-label">Conversione</span>
            <span class="metric-value" id="stat-conv">—</span>
        </div>
        <div class="metric-card">
            <span class="metric-label">SMM partner</span>
            <span class="metric-value" id="stat-smm">—</span>
        </div>
    </div>
</div>

<div class="pipe-tabs">
    <button class="pipe-tab active" onclick="showTab('leads',this)">🎯 Lead Pipeline</button>
    <button class="pipe-tab" onclick="showTab('funnel',this)">🔀 Funnel View</button>
    <button class="pipe-tab" onclick="showTab('smm',this)">🤝 SMM Partner</button>
    <button class="pipe-tab" onclick="showTab('kpi',this)">📊 KPI & Analytics</button>
</div>

{{-- ── LEADS PANEL ── --}}
<div class="pipe-panel active" id="panel-leads">

    <div id="retention-banner" class="retention-banner">
        <span style="font-size:1.1rem;flex-shrink:0">⚠️</span>
        <div>
            <strong style="color:var(--red);font-size:13px">PRIORITÀ RETENTION — Clienti a rischio</strong>
            <div style="font-size:12.5px;color:var(--ink-2);margin-top:4px;line-height:1.6">
                5 clienti non sono a conoscenza degli aggiornamenti CRM inclusi nel loro pacchetto.
                <strong style="color:var(--amber)">Azione immediata:</strong> WhatsApp personalizzato → se non rispondono entro 24h → chiamata diretta.<br>
                <strong style="color:var(--green)">Clienti sicuri:</strong> Locanda del Duca · Classico Maglie · Il Tuo Lounge · I Capricci di Leo · Zona Pub
            </div>
        </div>
        <button onclick="document.getElementById('retention-banner').style.display='none'"
            style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:1rem;flex-shrink:0;padding:2px 6px;margin-left:auto">✕</button>
    </div>

    <div class="pipe-toolbar">
        <button class="btn btn-primary" onclick="openLeadModal()">＋ Nuovo Lead</button>
        <input class="pipe-search" id="search-lead" placeholder="🔍 Cerca nome, ristorante, città..." oninput="renderLeads()">
        <select class="pipe-select" id="filter-stato" onchange="renderLeads()">
            <option value="">Tutti gli stati</option>
            <option value="nuovo">Nuovo</option>
            <option value="contattato">Contattato</option>
            <option value="interessato">Interessato</option>
            <option value="demo">Demo fissata</option>
            <option value="proposta">Proposta inviata</option>
            <option value="followup">Follow-up</option>
            <option value="chiuso">Chiuso ✓</option>
            <option value="perso">Perso</option>
        </select>
        <select class="pipe-select" id="filter-fonte" onchange="renderLeads()">
            <option value="">Tutte le fonti</option>
            <option value="smm">SMM Partner</option>
            <option value="ads">Meta Ads</option>
            <option value="referral">Referral cliente</option>
            <option value="organico">Organico</option>
            <option value="webinar">Webinar</option>
            <option value="diretto">Contatto diretto</option>
        </select>
        <select class="pipe-select" id="filter-prio" onchange="renderLeads()">
            <option value="">Tutte le priorità</option>
            <option value="alta">Alta</option>
            <option value="media">Media</option>
            <option value="bassa">Bassa</option>
        </select>
        <button class="btn" onclick="exportCsv()">⬇ Export CSV</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>P</th>
                    <th onclick="sortLeads('nome')" style="cursor:pointer">Nome / Ristorante ↕</th>
                    <th>Città</th>
                    <th onclick="sortLeads('stato')" style="cursor:pointer">Stato ↕</th>
                    <th>Fonte</th>
                    <th>Pacchetto</th>
                    <th onclick="sortLeads('valore')" style="cursor:pointer">Valore ↕</th>
                    <th>Prossimo step</th>
                    <th onclick="sortLeads('data_contatto')" style="cursor:pointer">Contatto ↕</th>
                    <th>Follow-up</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="leads-tbody"></tbody>
        </table>
    </div>
    <div id="leads-empty" class="pipe-empty" style="display:none">
        <div class="pipe-empty-icon">📭</div>
        <p>Nessun lead trovato.<br>Aggiungi il primo con il pulsante <strong>＋ Nuovo Lead</strong>.</p>
    </div>
</div>

{{-- ── FUNNEL PANEL ── --}}
<div class="pipe-panel" id="panel-funnel">
    <div id="funnel-content"></div>
</div>

{{-- ── SMM PANEL ── --}}
<div class="pipe-panel" id="panel-smm">
    <div class="pipe-toolbar">
        <button class="btn btn-primary" onclick="openSmmModal()">＋ Nuovo SMM Partner</button>
        <input class="pipe-search" id="search-smm" placeholder="🔍 Cerca nome, città..." oninput="renderSmm()">
        <select class="pipe-select" id="filter-smm-stato" onchange="renderSmm()">
            <option value="">Tutti gli stati</option>
            <option value="nuovo">Da contattare</option>
            <option value="contattato">Contattato</option>
            <option value="interessato">Interessato</option>
            <option value="partner">Partner attivo ✓</option>
            <option value="rifiutato">Rifiutato</option>
        </select>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nome SMM</th>
                    <th>Piattaforma</th>
                    <th>Profilo</th>
                    <th>Ristoranti</th>
                    <th>Stato</th>
                    <th>Referral fee</th>
                    <th>Clienti portati</th>
                    <th>Guadagno SMM</th>
                    <th>Contattato il</th>
                    <th>Note</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="smm-tbody"></tbody>
        </table>
    </div>
    <div id="smm-empty" class="pipe-empty" style="display:none">
        <div class="pipe-empty-icon">🤝</div>
        <p>Nessun SMM in lista.<br>Aggiungi il primo SMM partner.</p>
    </div>
</div>

{{-- ── KPI PANEL ── --}}
<div class="pipe-panel" id="panel-kpi">
    <div id="kpi-content"></div>
</div>

{{-- ── LEAD MODAL ── --}}
<div class="modal-overlay hidden" id="lead-modal">
    <div class="modal-box">
        <div class="modal-hdr">
            <div class="modal-ttl" id="lead-modal-ttl">Nuovo Lead</div>
            <button class="modal-cls" onclick="closeLeadModal()">✕</button>
        </div>
        <div class="modal-bdy">
            <div class="form-grid2">
                <div class="field"><label class="f-label">Nome contatto *</label><input type="text" id="f-nome" placeholder="Es. Mario Rossi"></div>
                <div class="field"><label class="f-label">Nome ristorante *</label><input type="text" id="f-ristorante" placeholder="Es. Trattoria da Mario"></div>
                <div class="field"><label class="f-label">Città</label><input type="text" id="f-citta" placeholder="Es. Milano"></div>
                <div class="field"><label class="f-label">Telefono</label><input type="text" id="f-telefono" placeholder="+39 333 123 4567"></div>
                <div class="field"><label class="f-label">Email</label><input type="email" id="f-email" placeholder="mario@ristorante.it"></div>
                <div class="field"><label class="f-label">Fonte *</label>
                    <select id="f-fonte">
                        <option value="smm">SMM Partner</option>
                        <option value="ads">Meta Ads</option>
                        <option value="referral">Referral cliente</option>
                        <option value="organico">Organico</option>
                        <option value="webinar">Webinar</option>
                        <option value="diretto" selected>Contatto diretto</option>
                    </select>
                </div>
                <div class="field"><label class="f-label">SMM di riferimento</label><input type="text" id="f-smm-ref" placeholder="Nome SMM (se fonte = SMM)"></div>
                <div class="field"><label class="f-label">Stato *</label>
                    <select id="f-stato">
                        <option value="nuovo">Nuovo</option>
                        <option value="contattato">Contattato</option>
                        <option value="interessato">Interessato</option>
                        <option value="demo">Demo fissata</option>
                        <option value="proposta">Proposta inviata</option>
                        <option value="followup">Follow-up</option>
                        <option value="chiuso">Chiuso ✓</option>
                        <option value="perso">Perso</option>
                    </select>
                </div>
                <div class="field"><label class="f-label">Priorità</label>
                    <select id="f-priorita">
                        <option value="alta">🔴 Alta</option>
                        <option value="media">🟡 Media</option>
                        <option value="bassa" selected>🟢 Bassa</option>
                    </select>
                </div>
                <div class="field"><label class="f-label">Pacchetto</label>
                    <select id="f-pacchetto">
                        <option value="">Non definito</option>
                        <option value="base">Base — €399/anno</option>
                        <option value="inter">Intermedio — €999/anno</option>
                        <option value="top">Top CRM — €1.200/anno</option>
                    </select>
                </div>
                <div class="field"><label class="f-label">Valore stimato (€/anno)</label><input type="number" id="f-valore" placeholder="399" min="0"></div>
                <div class="field"><label class="f-label">Data primo contatto</label><input type="date" id="f-data-contatto"></div>
                <div class="field"><label class="f-label">Data follow-up</label><input type="date" id="f-followup"></div>
                <div class="field form-full2"><label class="f-label">Prossimo step</label><input type="text" id="f-nextstep" placeholder="Es. Chiamare giovedì per chiudere..."></div>
                <div class="field form-full2"><label class="f-label">Note / Obiezioni</label><textarea id="f-note" rows="3" placeholder="Es. Usa già TheFork, vuole pensarci..."></textarea></div>
            </div>
        </div>
        <div class="modal-ftr">
            <button class="btn btn-danger" id="lead-btn-delete" onclick="deleteLead()" style="display:none;margin-right:auto">🗑 Elimina</button>
            <button class="btn" onclick="closeLeadModal()">Annulla</button>
            <button class="btn btn-primary" onclick="saveLead()">Salva Lead</button>
        </div>
    </div>
</div>

{{-- ── SMM MODAL ── --}}
<div class="modal-overlay hidden" id="smm-modal">
    <div class="modal-box">
        <div class="modal-hdr">
            <div class="modal-ttl" id="smm-modal-ttl">Nuovo SMM Partner</div>
            <button class="modal-cls" onclick="closeSmmModal()">✕</button>
        </div>
        <div class="modal-bdy">
            <div class="form-grid2">
                <div class="field"><label class="f-label">Nome SMM *</label><input type="text" id="sf-nome" placeholder="Es. Laura Bianchi"></div>
                <div class="field"><label class="f-label">Città / Zona</label><input type="text" id="sf-citta" placeholder="Es. Roma, Sud Italia..."></div>
                <div class="field"><label class="f-label">Piattaforma principale</label>
                    <select id="sf-piattaforma">
                        <option value="Instagram">Instagram</option>
                        <option value="LinkedIn">LinkedIn</option>
                        <option value="TikTok">TikTok</option>
                        <option value="Multi">Multi-piattaforma</option>
                    </select>
                </div>
                <div class="field"><label class="f-label">Profilo / URL</label><input type="text" id="sf-profilo" placeholder="@nomeprofilo o link"></div>
                <div class="field"><label class="f-label">Ristoranti gestiti (stima)</label><input type="number" id="sf-ristoranti" placeholder="Es. 8" min="0"></div>
                <div class="field"><label class="f-label">Stato *</label>
                    <select id="sf-stato">
                        <option value="nuovo">Da contattare</option>
                        <option value="contattato">Contattato</option>
                        <option value="interessato">Interessato</option>
                        <option value="partner">Partner attivo ✓</option>
                        <option value="rifiutato">Rifiutato</option>
                    </select>
                </div>
                <div class="field"><label class="f-label">Referral fee accordata (€)</label><input type="number" id="sf-fee" placeholder="60" min="0"></div>
                <div class="field"><label class="f-label">Clienti portati</label><input type="number" id="sf-clienti" placeholder="0" value="0" min="0"></div>
                <div class="field"><label class="f-label">Data primo contatto</label><input type="date" id="sf-data"></div>
                <div class="field"><label class="f-label">Canale contatto usato</label>
                    <select id="sf-canale">
                        <option value="Instagram DM">Instagram DM</option>
                        <option value="LinkedIn">LinkedIn</option>
                        <option value="Email">Email</option>
                        <option value="Telefono">Telefono</option>
                        <option value="WhatsApp">WhatsApp</option>
                    </select>
                </div>
                <div class="field form-full2"><label class="f-label">Note</label><textarea id="sf-note" rows="3" placeholder="Es. Gestisce principalmente ristoranti pugliesi..."></textarea></div>
            </div>
        </div>
        <div class="modal-ftr">
            <button class="btn btn-danger" id="smm-btn-delete" onclick="deleteSmm()" style="display:none;margin-right:auto">🗑 Elimina</button>
            <button class="btn" onclick="closeSmmModal()">Annulla</button>
            <button class="btn btn-primary" onclick="saveSmm()">Salva SMM</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const API = {
    leads:    "{{ route('pipeline.leads') }}",
    storeLead:"{{ route('pipeline.leads.store') }}",
    smm:      "{{ route('pipeline.smm') }}",
    storeSmm: "{{ route('pipeline.smm.store') }}",
    stats:    "{{ route('pipeline.stats') }}",
    seed:     "{{ route('pipeline.seed') }}",
    exportCsv:"{{ route('pipeline.export') }}",
};
const CSRF = "{{ csrf_token() }}";

// ─── Helpers ──────────────────────────────────────────────────────────────────
function fmtDate(d) {
    if (!d) return '—';
    const p = d.split('-');
    return `${p[2]}/${p[1]}/${p[0].slice(2)}`;
}
function fmtEur(v) { return v ? `€${Number(v).toLocaleString('it')}` : '—'; }

const statusLabel = { nuovo:'Nuovo', contattato:'Contattato', interessato:'Interessato', demo:'Demo fissata', proposta:'Proposta inviata', followup:'Follow-up', chiuso:'Chiuso ✓', perso:'Perso' };
const statusClass = { nuovo:'s-nuovo', contattato:'s-contattato', interessato:'s-interessato', demo:'s-demo', proposta:'s-proposta', followup:'s-followup', chiuso:'s-chiuso', perso:'s-perso' };
const sourceLabel = { smm:'SMM', ads:'Meta Ads', referral:'Referral', organico:'Organico', webinar:'Webinar', diretto:'Diretto' };
const sourceClass = { smm:'src-smm', ads:'src-ads', referral:'src-referral', organico:'src-organico', webinar:'src-webinar', diretto:'src-diretto' };
const packLabel   = { base:'Base €399', inter:'Interm. €999', top:'Top €1.200' };
const packClass   = { base:'pack-base', inter:'pack-inter', top:'pack-top' };
const prioClass   = { alta:'p-alta', media:'p-media', bassa:'p-bassa' };
const smmStatusLabel = { nuovo:'Da contattare', contattato:'Contattato', interessato:'Interessato', partner:'Partner ✓', rifiutato:'Rifiutato' };
const smmStatusClass = { nuovo:'smm-s-nuovo', contattato:'smm-s-contattato', interessato:'smm-s-interessato', partner:'smm-s-partner', rifiutato:'smm-s-rifiutato' };

// Cache locale
let leadsCache = [];
let smmCache   = [];
let sortCol    = 'created_at';
let sortDir    = 'desc';

// ─── TABS ─────────────────────────────────────────────────────────────────────
function showTab(id, btn) {
    document.querySelectorAll('.pipe-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.pipe-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('panel-' + id).classList.add('active');
    btn.classList.add('active');
    if (id === 'funnel') renderFunnel();
    if (id === 'kpi')    renderKPI();
    if (id === 'smm')    loadSmm();
}

// ─── LEADS ────────────────────────────────────────────────────────────────────
async function loadLeads() {
    const params = new URLSearchParams({
        stato:    document.getElementById('filter-stato').value,
        fonte:    document.getElementById('filter-fonte').value,
        priorita: document.getElementById('filter-prio').value,
        q:        document.getElementById('search-lead').value,
        sort:     sortCol,
        dir:      sortDir,
    });
    const res = await fetch(API.leads + '?' + params, {
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    leadsCache = await res.json();
    renderLeads();
}

function renderLeads() {
    loadLeads();
}

async function renderLeadsLocal() {
    const q     = document.getElementById('search-lead').value.toLowerCase();
    const stato = document.getElementById('filter-stato').value;
    const fonte = document.getElementById('filter-fonte').value;
    const prio  = document.getElementById('filter-prio').value;

    let leads = leadsCache.filter(l => {
        if (stato && l.stato !== stato) return false;
        if (fonte && l.fonte !== fonte) return false;
        if (prio  && l.priorita !== prio) return false;
        if (q && !(`${l.nome}${l.ristorante}${l.citta}`).toLowerCase().includes(q)) return false;
        return true;
    });

    const tbody = document.getElementById('leads-tbody');
    const empty = document.getElementById('leads-empty');

    if (!leads.length) { tbody.innerHTML = ''; empty.style.display = 'block'; return; }
    empty.style.display = 'none';

    tbody.innerHTML = leads.map(l => `
        <tr style="cursor:pointer" onclick="editLead(${l.id})">
            <td><div class="prio-dot ${prioClass[l.priorita] || 'p-bassa'}"></div></td>
            <td>
                <strong style="font-size:13px">${l.nome || '—'}</strong>
                <div style="font-size:11px;color:var(--muted)">${l.ristorante || ''}</div>
            </td>
            <td>${l.citta || '—'}</td>
            <td><span class="s-badge ${statusClass[l.stato] || ''}">${statusLabel[l.stato] || l.stato}</span></td>
            <td><span class="src-badge ${sourceClass[l.fonte] || ''}">${sourceLabel[l.fonte] || l.fonte || '—'}</span></td>
            <td>${l.pacchetto ? `<span class="pack-badge ${packClass[l.pacchetto]}">${packLabel[l.pacchetto]}</span>` : '—'}</td>
            <td style="font-weight:700;color:var(--brand)">${fmtEur(l.valore)}</td>
            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:var(--muted)" title="${l.nextstep || ''}">${l.nextstep || '—'}</td>
            <td style="font-size:12px;color:var(--muted)">${fmtDate(l.data_contatto)}</td>
            <td class="${l.overdue ? 'overdue-date' : ''}" style="font-size:12px">${l.followup_date ? fmtDate(l.followup_date) + (l.overdue ? ' ⚠' : '') : '—'}</td>
            <td><button class="btn" style="padding:3px 9px;font-size:11px" onclick="event.stopPropagation();editLead(${l.id})">✏</button></td>
        </tr>`).join('');
}

function sortLeads(col) {
    if (sortCol === col) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    else { sortCol = col; sortDir = 'asc'; }
    loadLeads();
}

// ─── LEAD MODAL ───────────────────────────────────────────────────────────────
let editingLeadId = null;

function openLeadModal(prefillStato) {
    editingLeadId = null;
    document.getElementById('lead-modal-ttl').textContent = 'Nuovo Lead';
    document.getElementById('lead-btn-delete').style.display = 'none';
    clearLeadForm();
    if (prefillStato) document.getElementById('f-stato').value = prefillStato;
    document.getElementById('f-data-contatto').value = new Date().toISOString().slice(0, 10);
    document.getElementById('lead-modal').classList.remove('hidden');
}

function editLead(id) {
    const l = leadsCache.find(x => x.id === id);
    if (!l) return;
    editingLeadId = id;
    document.getElementById('lead-modal-ttl').textContent = 'Modifica Lead';
    document.getElementById('lead-btn-delete').style.display = 'block';
    document.getElementById('f-nome').value          = l.nome || '';
    document.getElementById('f-ristorante').value    = l.ristorante || '';
    document.getElementById('f-citta').value         = l.citta || '';
    document.getElementById('f-telefono').value      = l.telefono || '';
    document.getElementById('f-email').value         = l.email || '';
    document.getElementById('f-fonte').value         = l.fonte || 'diretto';
    document.getElementById('f-smm-ref').value       = l.smm_ref || '';
    document.getElementById('f-stato').value         = l.stato || 'nuovo';
    document.getElementById('f-priorita').value      = l.priorita || 'bassa';
    document.getElementById('f-pacchetto').value     = l.pacchetto || '';
    document.getElementById('f-valore').value        = l.valore || '';
    document.getElementById('f-data-contatto').value = l.data_contatto || '';
    document.getElementById('f-followup').value      = l.followup_date || '';
    document.getElementById('f-nextstep').value      = l.nextstep || '';
    document.getElementById('f-note').value          = l.note || '';
    document.getElementById('lead-modal').classList.remove('hidden');
}

function closeLeadModal() { document.getElementById('lead-modal').classList.add('hidden'); }

function clearLeadForm() {
    ['f-nome','f-ristorante','f-citta','f-telefono','f-email','f-smm-ref','f-valore','f-data-contatto','f-followup','f-nextstep','f-note'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('f-fonte').value    = 'diretto';
    document.getElementById('f-stato').value    = 'nuovo';
    document.getElementById('f-priorita').value = 'bassa';
    document.getElementById('f-pacchetto').value = '';
}

async function saveLead() {
    const nome = document.getElementById('f-nome').value.trim();
    if (!nome) { alert('Inserisci il nome del contatto'); return; }

    const pack = document.getElementById('f-pacchetto').value;
    const packValues = { base: 399, inter: 999, top: 1200 };
    const valore = document.getElementById('f-valore').value || (pack ? packValues[pack] : '');

    const payload = {
        nome,
        ristorante:    document.getElementById('f-ristorante').value.trim(),
        citta:         document.getElementById('f-citta').value.trim(),
        telefono:      document.getElementById('f-telefono').value.trim(),
        email:         document.getElementById('f-email').value.trim(),
        fonte:         document.getElementById('f-fonte').value,
        smm_ref:       document.getElementById('f-smm-ref').value.trim(),
        stato:         document.getElementById('f-stato').value,
        priorita:      document.getElementById('f-priorita').value,
        pacchetto:     pack,
        valore:        valore || null,
        data_contatto: document.getElementById('f-data-contatto').value || null,
        followup_date: document.getElementById('f-followup').value || null,
        nextstep:      document.getElementById('f-nextstep').value.trim(),
        note:          document.getElementById('f-note').value.trim(),
    };

    const url    = editingLeadId ? `${API.storeLead}/${editingLeadId}` : API.storeLead;
    const method = editingLeadId ? 'PUT' : 'POST';

    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(payload),
    });

    if (!res.ok) { const err = await res.json(); alert(Object.values(err.errors || {err:'Errore'}).flat().join('\n')); return; }

    closeLeadModal();
    loadLeads();
    loadStats();
}

async function deleteLead() {
    if (!editingLeadId || !confirm('Eliminare questo lead?')) return;
    await fetch(`${API.storeLead}/${editingLeadId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    closeLeadModal();
    loadLeads();
    loadStats();
}

// ─── SMM ──────────────────────────────────────────────────────────────────────
async function loadSmm() {
    const params = new URLSearchParams({
        stato: document.getElementById('filter-smm-stato').value,
        q:     document.getElementById('search-smm').value,
    });
    const res = await fetch(API.smm + '?' + params, {
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    smmCache = await res.json();
    renderSmmTable();
}

function renderSmm() { loadSmm(); }

function renderSmmTable() {
    const tbody = document.getElementById('smm-tbody');
    const empty = document.getElementById('smm-empty');
    if (!smmCache.length) { tbody.innerHTML = ''; empty.style.display = 'block'; return; }
    empty.style.display = 'none';
    tbody.innerHTML = smmCache.map(s => `
        <tr style="cursor:pointer" onclick="editSmm(${s.id})">
            <td><strong>${s.nome}</strong></td>
            <td>${s.piattaforma || '—'}</td>
            <td style="font-size:12px">${s.profilo ? `<a href="${s.profilo.startsWith('http') ? s.profilo : 'https://' + s.profilo}" target="_blank" onclick="event.stopPropagation()" style="color:var(--brand)">${s.profilo}</a>` : '—'}</td>
            <td style="text-align:center">${s.ristoranti || '—'}</td>
            <td><span class="s-badge ${smmStatusClass[s.stato] || ''}">${smmStatusLabel[s.stato] || s.stato}</span></td>
            <td style="color:var(--green);font-weight:700">${s.fee ? `€${s.fee}` : s.stato === 'partner' ? '€60' : '—'}</td>
            <td style="text-align:center;font-weight:700;color:var(--brand)">${s.clienti || 0}</td>
            <td style="color:var(--green);font-weight:700">${s.guadagno ? `€${Number(s.guadagno).toLocaleString('it')}` : '€0'}</td>
            <td style="font-size:12px;color:var(--muted)">${fmtDate(s.data_contatto)}</td>
            <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:var(--muted)" title="${s.note || ''}">${s.note || '—'}</td>
            <td><button class="btn" style="padding:3px 9px;font-size:11px" onclick="event.stopPropagation();editSmm(${s.id})">✏</button></td>
        </tr>`).join('');
}

let editingSmmId = null;

function openSmmModal() {
    editingSmmId = null;
    document.getElementById('smm-modal-ttl').textContent = 'Nuovo SMM Partner';
    document.getElementById('smm-btn-delete').style.display = 'none';
    ['sf-nome','sf-citta','sf-profilo','sf-ristoranti','sf-fee','sf-note'].forEach(id => { document.getElementById(id).value = ''; });
    document.getElementById('sf-clienti').value = '0';
    document.getElementById('sf-piattaforma').value = 'Instagram';
    document.getElementById('sf-stato').value = 'nuovo';
    document.getElementById('sf-canale').value = 'Instagram DM';
    document.getElementById('sf-data').value = new Date().toISOString().slice(0, 10);
    document.getElementById('smm-modal').classList.remove('hidden');
}

function editSmm(id) {
    const s = smmCache.find(x => x.id === id);
    if (!s) return;
    editingSmmId = id;
    document.getElementById('smm-modal-ttl').textContent = 'Modifica SMM Partner';
    document.getElementById('smm-btn-delete').style.display = 'block';
    document.getElementById('sf-nome').value       = s.nome || '';
    document.getElementById('sf-citta').value      = s.citta || '';
    document.getElementById('sf-piattaforma').value= s.piattaforma || 'Instagram';
    document.getElementById('sf-profilo').value    = s.profilo || '';
    document.getElementById('sf-ristoranti').value = s.ristoranti || '';
    document.getElementById('sf-stato').value      = s.stato || 'nuovo';
    document.getElementById('sf-fee').value        = s.fee || '';
    document.getElementById('sf-clienti').value    = s.clienti || '0';
    document.getElementById('sf-data').value       = s.data_contatto || '';
    document.getElementById('sf-canale').value     = s.canale || 'Instagram DM';
    document.getElementById('sf-note').value       = s.note || '';
    document.getElementById('smm-modal').classList.remove('hidden');
}

function closeSmmModal() { document.getElementById('smm-modal').classList.add('hidden'); }

async function saveSmm() {
    const nome = document.getElementById('sf-nome').value.trim();
    if (!nome) { alert('Inserisci il nome dell\'SMM'); return; }

    const payload = {
        nome,
        citta:         document.getElementById('sf-citta').value.trim(),
        piattaforma:   document.getElementById('sf-piattaforma').value,
        profilo:       document.getElementById('sf-profilo').value.trim(),
        ristoranti:    document.getElementById('sf-ristoranti').value || null,
        stato:         document.getElementById('sf-stato').value,
        fee:           document.getElementById('sf-fee').value || null,
        clienti:       document.getElementById('sf-clienti').value || 0,
        data_contatto: document.getElementById('sf-data').value || null,
        canale:        document.getElementById('sf-canale').value,
        note:          document.getElementById('sf-note').value.trim(),
    };

    const url    = editingSmmId ? `${API.storeSmm}/${editingSmmId}` : API.storeSmm;
    const method = editingSmmId ? 'PUT' : 'POST';

    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(payload),
    });

    if (!res.ok) { const err = await res.json(); alert(Object.values(err.errors || {err:'Errore'}).flat().join('\n')); return; }

    closeSmmModal();
    loadSmm();
    loadStats();
}

async function deleteSmm() {
    if (!editingSmmId || !confirm('Eliminare questo SMM?')) return;
    await fetch(`${API.storeSmm}/${editingSmmId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    closeSmmModal();
    loadSmm();
    loadStats();
}

// ─── STATS ────────────────────────────────────────────────────────────────────
async function loadStats() {
    const res = await fetch(API.stats, {
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    const d = await res.json();

    document.getElementById('stat-totali').textContent = d.totali;
    document.getElementById('stat-attivi').textContent = d.attivi;
    document.getElementById('stat-chiusi').textContent = d.chiusiCount;
    document.getElementById('stat-arr').textContent    = d.arr ? `€${Number(d.arr).toLocaleString('it')}` : '€0';
    document.getElementById('stat-cac').textContent    = d.cac ? `€${d.cac}` : '—';
    document.getElementById('stat-conv').textContent   = d.totali ? `${d.conv}%` : '—%';
    document.getElementById('stat-smm').textContent    = d.smmPartner;
}

// ─── FUNNEL ───────────────────────────────────────────────────────────────────
const FUNNEL_STAGES = [
    {key:'nuovo',       label:'Nuovi',      color:'#1d4ed8'},
    {key:'contattato',  label:'Contattati', color:'#b54708'},
    {key:'interessato', label:'Interessati',color:'#027a48'},
    {key:'demo',        label:'Demo',       color:'#7e22ce'},
    {key:'proposta',    label:'Proposta',   color:'#c2410c'},
    {key:'followup',    label:'Follow-up',  color:'#a21caf'},
    {key:'chiuso',      label:'Chiusi ✓',   color:'#15803d'},
    {key:'perso',       label:'Persi',      color:'#b42318'},
];

function renderFunnel() {
    const leads = leadsCache;
    let html = '<div class="funnel-wrap">';
    FUNNEL_STAGES.forEach(stage => {
        const items = leads.filter(l => l.stato === stage.key);
        const val   = items.reduce((s, l) => s + (Number(l.valore) || 0), 0);
        html += `<div class="funnel-col">
            <div class="funnel-header" style="background:${stage.color}18;color:${stage.color};border:1px solid ${stage.color}33">
                <div class="funnel-count">${items.length}</div>
                <div style="font-size:10px;margin-top:2px">${stage.label}</div>
                ${val ? `<div style="font-size:10px;margin-top:2px">€${val.toLocaleString('it')}</div>` : ''}
            </div>
            <div class="funnel-items">
                ${items.length ? items.map(l => `
                    <div class="funnel-item" onclick="editLead(${l.id})">
                        <div class="fi-name">${l.nome || '—'}</div>
                        <div class="fi-meta">${l.ristorante || ''} ${l.citta ? '· ' + l.citta : ''}</div>
                        ${l.valore ? `<div style="font-size:11px;color:var(--brand);margin-top:3px;font-weight:700">€${Number(l.valore).toLocaleString('it')}</div>` : ''}
                    </div>`).join('') : '<div style="font-size:11px;color:var(--muted);padding:10px;text-align:center">vuoto</div>'}
                <button class="funnel-add" onclick="openLeadModal('${stage.key}')">＋ Aggiungi</button>
            </div>
        </div>`;
    });
    html += '</div>';
    document.getElementById('funnel-content').innerHTML = html;
}

// ─── KPI ──────────────────────────────────────────────────────────────────────
async function renderKPI() {
    const res = await fetch(API.stats, {
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    const d = await res.json();

    const byFonteHtml = Object.entries(d.byFonte || {}).map(([k, v]) => `
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <span class="src-badge ${sourceClass[k] || ''}" style="width:80px;text-align:center">${sourceLabel[k] || k}</span>
            <div style="flex:1;height:8px;background:var(--bg);border-radius:4px;overflow:hidden;border:1px solid var(--border-soft)">
                <div style="width:${d.totali ? Math.round(v.total/d.totali*100) : 0}%;height:100%;background:var(--brand);border-radius:4px"></div>
            </div>
            <span style="font-size:12px;color:var(--brand);font-weight:700;width:20px">${v.total}</span>
            <span style="font-size:11px;color:var(--green)">${v.chiusi} ✓</span>
        </div>`).join('') || '<p style="color:var(--muted);font-size:13px">Nessun dato ancora</p>';

    const pipelineHtml = ['interessato','demo','proposta'].map(stato => {
        const info = d.pipelineValore[stato] || {count:0, valore:0};
        const label = {interessato:'Interessati', demo:'Demo', proposta:'Proposta'}[stato];
        return `<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border-soft)">
            <span style="font-size:13px;color:var(--ink-2)">${label} (${info.count})</span>
            <span style="font-size:13px;color:var(--brand);font-weight:700">${info.valore ? '€' + Number(info.valore).toLocaleString('it') : '€0'}</span>
        </div>`;
    }).join('');

    document.getElementById('kpi-content').innerHTML = `
        <div class="kpi-grid">
            <div class="kpi-card"><div class="kpi-val">${d.totali}</div><div class="kpi-lbl">Lead totali</div></div>
            <div class="kpi-card"><div class="kpi-val">${d.chiusiCount}</div><div class="kpi-lbl">Clienti chiusi</div><div class="kpi-sub">target: 30</div></div>
            <div class="kpi-card"><div class="kpi-val">${d.conv}%</div><div class="kpi-lbl">Conversione</div><div class="kpi-sub">target: ≥15%</div></div>
            <div class="kpi-card"><div class="kpi-val">${d.arr ? '€'+Number(d.arr).toLocaleString('it') : '€0'}</div><div class="kpi-lbl">ARR aggiunto</div><div class="kpi-sub">target: €15k+</div></div>
            <div class="kpi-card"><div class="kpi-val">${d.persi}</div><div class="kpi-lbl">Lead persi</div></div>
            <div class="kpi-card"><div class="kpi-val">${d.smmPartner}</div><div class="kpi-lbl">SMM partner</div><div class="kpi-sub">target: 5+</div></div>
            <div class="kpi-card"><div class="kpi-val">${d.smmClienti}</div><div class="kpi-lbl">Clienti via SMM</div></div>
            <div class="kpi-card"><div class="kpi-val">${d.smmFee ? '€'+Number(d.smmFee).toLocaleString('it') : '€0'}</div><div class="kpi-lbl">Fee pagate SMM</div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="panel">
                <div style="font-size:12px;font-weight:760;color:var(--brand);margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em">Lead per fonte</div>
                ${byFonteHtml}
            </div>
            <div class="panel">
                <div style="font-size:12px;font-weight:760;color:var(--brand);margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em">Valore pipeline attiva</div>
                ${pipelineHtml}
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0 0">
                    <span style="font-size:13px;color:var(--muted);font-weight:700">Totale pipeline</span>
                    <span style="font-size:15px;color:var(--green);font-weight:900">€${Number(d.pipelineTotale || 0).toLocaleString('it')}</span>
                </div>
            </div>
        </div>`;
}

// ─── EXPORT ───────────────────────────────────────────────────────────────────
function exportCsv() { window.location.href = API.exportCsv; }

// ─── CLOSE MODAL ON OVERLAY CLICK ─────────────────────────────────────────────
document.getElementById('lead-modal').addEventListener('click', e => { if (e.target.id === 'lead-modal') closeLeadModal(); });
document.getElementById('smm-modal').addEventListener('click',  e => { if (e.target.id === 'smm-modal')  closeSmmModal(); });

// ─── INIT ─────────────────────────────────────────────────────────────────────
(async function () {
    await fetch(API.seed, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
    await loadLeads();
    await loadStats();
})();
</script>
@endpush

@endsection

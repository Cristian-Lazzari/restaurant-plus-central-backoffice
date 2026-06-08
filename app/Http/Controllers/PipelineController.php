<?php

namespace App\Http\Controllers;

use App\Models\PipelineSmm;
use App\Models\Site;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    // ─── Vista principale ─────────────────────────────────────────────────────

    public function index()
    {
        return view('pipeline.index');
    }

    // ─── LEADS ───────────────────────────────────────────────────────────────

    public function leads(Request $request)
    {
        // La pipeline mostra tutti i Site, sia prospect che clienti connessi.
        // Il campo has_dashboard nel payload indica se il sito ha già una dashboard.
        $q = Site::query();

        if ($s = $request->input('stato')) {
            $q->where('stato', $s);
        }
        if ($f = $request->input('fonte')) {
            $q->where('fonte', $f);
        }
        if ($p = $request->input('priorita')) {
            $q->where('priorita', $p);
        }
        if ($search = $request->input('q')) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('citta', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'created_at');
        $dir  = $request->input('dir', 'desc');

        // Mappa i nomi frontend → colonne reali in sites
        $sortMap = [
            'nome'          => 'name',
            'stato'         => 'stato',
            'valore'        => 'valore',
            'data_contatto' => 'data_contatto',
            'created_at'    => 'created_at',
        ];

        if (isset($sortMap[$sort])) {
            $q->orderBy($sortMap[$sort], $dir === 'asc' ? 'asc' : 'desc');
        }

        $leads = $q->get()->map(fn ($s) => $this->formatSite($s));

        return response()->json($leads);
    }

    public function storeLead(Request $request)
    {
        $data = $this->validateLead($request);

        $site = Site::create(array_merge(
            $this->pipelineToSiteData($data),
            ['is_prospect' => true, 'url' => '', 'token' => '']
        ));

        return response()->json($this->formatSite($site), 201);
    }

    public function updateLead(Request $request, Site $site)
    {
        $data = $this->validateLead($request);

        // Aggiorna solo i campi CRM; non toccare url/token/active/consecutive_failures
        $site->update($this->pipelineToSiteData($data));

        return response()->json($this->formatSite($site->fresh()));
    }

    public function destroyLead(Site $site)
    {
        // I clienti connessi non si eliminano dalla pipeline: rischio perdita dati sync
        if (! $site->is_prospect) {
            return response()->json([
                'error' => 'Non puoi eliminare un cliente connesso dalla pipeline',
            ], 403);
        }

        $site->delete();

        return response()->json(['ok' => true]);
    }

    // ─── SMM ─────────────────────────────────────────────────────────────────

    public function smmList(Request $request)
    {
        $q = PipelineSmm::query();

        if ($s = $request->input('stato')) {
            $q->where('stato', $s);
        }
        if ($search = $request->input('q')) {
            $q->where(function ($sub) use ($search) {
                $sub->where('nome', 'like', "%{$search}%")
                    ->orWhere('citta', 'like', "%{$search}%");
            });
        }

        $smm = $q->orderBy('created_at', 'desc')->get()->map(fn ($s) => $this->formatSmm($s));

        return response()->json($smm);
    }

    public function storeSmm(Request $request)
    {
        $data = $this->validateSmm($request);
        $smm  = PipelineSmm::create($data);

        return response()->json($this->formatSmm($smm), 201);
    }

    public function updateSmm(Request $request, PipelineSmm $smm)
    {
        $data = $this->validateSmm($request);
        $smm->update($data);

        return response()->json($this->formatSmm($smm->fresh()));
    }

    public function destroySmm(PipelineSmm $smm)
    {
        $smm->delete();

        return response()->json(['ok' => true]);
    }

    // ─── STATISTICHE ─────────────────────────────────────────────────────────

    public function stats()
    {
        // Usa Site::all() come unica sorgente di verità per i lead della pipeline
        $leads = Site::all();
        $smm   = PipelineSmm::all();

        $totali  = $leads->count();
        $attivi  = $leads->whereNotIn('stato', ['chiuso', 'perso'])->count();
        $chiusi  = $leads->where('stato', 'chiuso');
        $chiusiCount = $chiusi->count();
        $arr     = $chiusi->sum('valore');
        $persi   = $leads->where('stato', 'perso')->count();
        $conv    = $totali ? round($chiusiCount / $totali * 100) : 0;

        $adsCost     = $leads->where('fonte', 'ads')->count() * 22;
        $cac         = $chiusiCount ? (int) round($adsCost / $chiusiCount) : 0;
        $smmPartner  = $smm->where('stato', 'partner')->count();
        $smmClienti  = $smm->sum('clienti');
        $smmFee      = $smm->reduce(fn ($carry, $s) => $carry + ($s->clienti * ($s->fee ?? 60)), 0);

        // Lead per fonte
        $byFonte = [];
        foreach ($leads as $l) {
            $f = $l->fonte ?: 'diretto';
            if (! isset($byFonte[$f])) {
                $byFonte[$f] = ['total' => 0, 'chiusi' => 0];
            }
            $byFonte[$f]['total']++;
            if ($l->stato === 'chiuso') {
                $byFonte[$f]['chiusi']++;
            }
        }

        // Valore pipeline attiva per fase
        $pipelineValore = [];
        foreach (['interessato', 'demo', 'proposta'] as $stato) {
            $items = $leads->where('stato', $stato);
            $pipelineValore[$stato] = [
                'count'  => $items->count(),
                'valore' => $items->sum('valore'),
            ];
        }
        $pipelineTotale = $leads->whereNotIn('stato', ['chiuso', 'perso'])->sum('valore');

        return response()->json(compact(
            'totali', 'attivi', 'chiusiCount', 'arr', 'persi',
            'conv', 'cac', 'smmPartner', 'smmClienti', 'smmFee',
            'byFonte', 'pipelineValore', 'pipelineTotale'
        ));
    }

    // ─── SEED dati iniziali ───────────────────────────────────────────────────

    public function seed()
    {
        // Se esistono già Site con stato pipeline valorizzato, il seed è già stato fatto
        if (Site::whereNotNull('stato')->exists()) {
            return response()->json(['skipped' => true]);
        }

        $packMap = [
            1 => ['pacchetto' => 'base',  'valore' => 399],
            2 => ['pacchetto' => 'inter', 'valore' => 999],
            3 => ['pacchetto' => 'top',   'valore' => 1199],
            5 => ['pacchetto' => 'top',   'valore' => 1199],
        ];

        // I siti connessi (is_prospect = false) con pack valorizzato sono già nella pipeline
        // come "chiuso". Li aggiorniamo aggiungendo i dati CRM senza creare duplicati.
        $sites = Site::connected()
            ->whereNotNull('pack')
            ->whereIn('pack', array_keys($packMap))
            ->orderBy('sort_order')
            ->get();

        foreach ($sites as $site) {
            $map = $packMap[$site->pack];
            $site->update([
                'stato'         => 'chiuso',
                'priorita'      => 'bassa',
                'valore'        => $map['valore'],
                'data_contatto' => $site->created_at?->toDateString() ?? now()->toDateString(),
                'fonte'         => 'diretto',
                'tag'           => 'sicuro',
            ]);
        }

        return response()->json(['seeded' => $sites->count()]);
    }

    // ─── EXPORT CSV ──────────────────────────────────────────────────────────

    public function exportCsv()
    {
        $leads = Site::orderBy('created_at', 'desc')->get();

        static $packStr = [1 => 'base', 2 => 'inter', 3 => 'top', 5 => 'top'];

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="pipeline_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($leads, $packStr) {
            $handle = fopen('php://output', 'w');
            // BOM per Excel italiano
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Nome', 'Ristorante', 'Città', 'Telefono', 'Email', 'Fonte', 'Stato', 'Priorità', 'Pacchetto', 'Valore €', 'Data contatto', 'Follow-up', 'Prossimo step', 'Note', 'Connesso'], ';');
            foreach ($leads as $l) {
                fputcsv($handle, [
                    $l->name, $l->name, $l->citta, $l->telefono, $l->email,
                    $l->fonte, $l->stato, $l->priorita,
                    $l->pack ? ($packStr[$l->pack] ?? '') : '',
                    $l->valore,
                    $l->data_contatto?->format('d/m/Y'), $l->followup_date?->format('d/m/Y'),
                    $l->nextstep, $l->notes,
                    $l->is_prospect ? 'No' : 'Sì',
                ], ';');
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── Helpers privati ─────────────────────────────────────────────────────

    private function validateLead(Request $request): array
    {
        return $request->validate([
            'nome'          => 'required|string|max:200',
            'ristorante'    => 'nullable|string|max:200',
            'citta'         => 'nullable|string|max:100',
            'telefono'      => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:150',
            'fonte'         => 'nullable|in:smm,ads,referral,organico,webinar,diretto',
            'smm_ref'       => 'nullable|string|max:200',
            'stato'         => 'nullable|in:nuovo,contattato,interessato,demo,proposta,followup,chiuso,perso',
            'priorita'      => 'nullable|in:alta,media,bassa',
            'pacchetto'     => 'nullable|in:base,inter,top',
            'valore'        => 'nullable|integer|min:0|max:9999',
            'data_contatto' => 'nullable|date',
            'followup_date' => 'nullable|date',
            'nextstep'      => 'nullable|string|max:500',
            'note'          => 'nullable|string|max:2000',
            'tag'           => 'nullable|string|max:20',
        ]);
    }

    private function validateSmm(Request $request): array
    {
        return $request->validate([
            'nome'          => 'required|string|max:200',
            'citta'         => 'nullable|string|max:100',
            'piattaforma'   => 'nullable|in:Instagram,LinkedIn,TikTok,Multi',
            'profilo'       => 'nullable|string|max:300',
            'ristoranti'    => 'nullable|integer|min:0',
            'stato'         => 'nullable|in:nuovo,contattato,interessato,partner,rifiutato',
            'fee'           => 'nullable|integer|min:0',
            'clienti'       => 'nullable|integer|min:0',
            'data_contatto' => 'nullable|date',
            'canale'        => 'nullable|string|max:30',
            'note'          => 'nullable|string|max:2000',
        ]);
    }

    /**
     * Formatta un Site come risposta JSON per il frontend pipeline.
     * Espone il campo has_dashboard per distinguere clienti connessi da prospect.
     */
    private function formatSite(Site $s): array
    {
        static $packStr = [1 => 'base', 2 => 'inter', 3 => 'top', 5 => 'top'];
        static $packVal = [1 => 399, 2 => 999, 3 => 1199, 5 => 1199];

        return [
            'id'            => $s->id,
            'nome'          => $s->name,
            'ristorante'    => $s->name,
            'citta'         => $s->citta,
            'telefono'      => $s->telefono,
            'email'         => $s->email,
            'fonte'         => $s->fonte,
            'smm_ref'       => $s->smm_ref,
            'stato'         => $s->stato,
            'priorita'      => $s->priorita,
            'pacchetto'     => $s->pack ? ($packStr[$s->pack] ?? null) : null,
            'valore'        => $s->valore ?? ($s->pack ? ($packVal[$s->pack] ?? null) : null),
            'data_contatto' => $s->data_contatto?->format('Y-m-d'),
            'followup_date' => $s->followup_date?->format('Y-m-d'),
            'nextstep'      => $s->nextstep,
            'note'          => $s->notes,
            'tag'           => $s->tag,
            'has_dashboard' => ! $s->is_prospect,
            'overdue'       => $s->isOverdue(),
        ];
    }

    private function formatSmm(PipelineSmm $s): array
    {
        return [
            'id'            => $s->id,
            'nome'          => $s->nome,
            'citta'         => $s->citta,
            'piattaforma'   => $s->piattaforma,
            'profilo'       => $s->profilo,
            'ristoranti'    => $s->ristoranti,
            'stato'         => $s->stato,
            'fee'           => $s->fee,
            'clienti'       => $s->clienti,
            'data_contatto' => $s->data_contatto?->format('Y-m-d'),
            'canale'        => $s->canale,
            'note'          => $s->note,
            'guadagno'      => $s->guadagnoTotale(),
        ];
    }

    /**
     * Converte i dati del form pipeline (chiavi frontend) nelle colonne di sites.
     */
    private function pipelineToSiteData(array $data): array
    {
        return [
            'name'          => $data['nome'],
            'citta'         => $data['citta'] ?? null,
            'telefono'      => $data['telefono'] ?? null,
            'email'         => $data['email'] ?? null,
            'fonte'         => $data['fonte'] ?? null,
            'smm_ref'       => $data['smm_ref'] ?? null,
            'stato'         => $data['stato'] ?? null,
            'priorita'      => $data['priorita'] ?? null,
            'pack'          => $this->pacchettoPack($data['pacchetto'] ?? null),
            'valore'        => $data['valore'] ?? null,
            'data_contatto' => $data['data_contatto'] ?? null,
            'followup_date' => $data['followup_date'] ?? null,
            'nextstep'      => $data['nextstep'] ?? null,
            'notes'         => $data['note'] ?? null,
            'tag'           => $data['tag'] ?? null,
        ];
    }

    /**
     * Converte la stringa pacchetto (base/inter/top) nell'integer pack usato in sites.
     */
    private function pacchettoPack(?string $pacchetto): ?int
    {
        return ['base' => 1, 'inter' => 2, 'top' => 3][$pacchetto] ?? null;
    }
}

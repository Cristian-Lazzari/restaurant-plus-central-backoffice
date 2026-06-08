<?php

namespace App\Http\Controllers;

use App\Models\PipelineLead;
use App\Models\PipelineSmm;
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
        $q = PipelineLead::query();

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
                $sub->where('nome', 'like', "%{$search}%")
                    ->orWhere('ristorante', 'like', "%{$search}%")
                    ->orWhere('citta', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'created_at');
        $dir  = $request->input('dir', 'desc');
        $allowedSorts = ['nome', 'stato', 'valore', 'data_contatto', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $q->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $leads = $q->get()->map(fn ($l) => $this->formatLead($l));

        return response()->json($leads);
    }

    public function storeLead(Request $request)
    {
        $data = $this->validateLead($request);
        $lead = PipelineLead::create($data);

        return response()->json($this->formatLead($lead), 201);
    }

    public function updateLead(Request $request, PipelineLead $lead)
    {
        $data = $this->validateLead($request);
        $lead->update($data);

        return response()->json($this->formatLead($lead->fresh()));
    }

    public function destroyLead(PipelineLead $lead)
    {
        $lead->delete();

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
        $leads = PipelineLead::all();
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
                'count' => $items->count(),
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
        if (PipelineLead::count() > 0) {
            return response()->json(['skipped' => true]);
        }

        $today = now()->toDateString();
        $fu1   = now()->addDays(1)->toDateString();
        $fu2   = now()->addDays(2)->toDateString();
        $fu3   = now()->addDays(3)->toDateString();

        $clienti = [
            ['nome' => 'Locanda del Duca',        'ristorante' => 'Locanda del Duca',        'stato' => 'chiuso', 'priorita' => 'bassa', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'nextstep' => 'Cliente sicuro — chiedi testimonianza video', 'note' => 'Cliente storico. 118 coperti/mese. Candidato per caso studio.', 'tag' => 'sicuro'],
            ['nome' => 'Classico Maglie',          'ristorante' => 'Classico Maglie',          'stato' => 'chiuso', 'priorita' => 'bassa', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'nextstep' => 'Cliente sicuro — chiedi video-testimonianza 60s', 'note' => 'Cliente sicuro. 154 coperti/mese. Candidato ideale per video-testimonianza.', 'tag' => 'sicuro'],
            ['nome' => 'Il Tuo Lounge Restaurant', 'ristorante' => 'Il Tuo Lounge Restaurant', 'stato' => 'chiuso', 'priorita' => 'bassa', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'nextstep' => 'Cliente sicuro — aggiorna su nuove funzioni CRM', 'note' => 'Cliente confermato.', 'tag' => 'sicuro'],
            ['nome' => 'I Capricci di Leo',        'ristorante' => 'I Capricci di Leo',        'stato' => 'chiuso', 'priorita' => 'bassa', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'nextstep' => 'Cliente sicuro — aggiorna su nuove funzioni CRM', 'note' => 'Cliente confermato.', 'tag' => 'sicuro'],
            ['nome' => 'Zona Pub',                 'ristorante' => 'Zona Pub',                 'stato' => 'chiuso', 'priorita' => 'bassa', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'nextstep' => 'Cliente assicurato — rafforza rapporto, chiedi referral', 'note' => 'Cliente assicurato. Ottimo candidato per referral program.', 'tag' => 'sicuro'],
            ['nome' => 'Cliente a rischio 1', 'ristorante' => '(aggiorna nome)', 'stato' => 'followup', 'priorita' => 'alta', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'followup_date' => $fu1, 'nextstep' => 'WhatsApp oggi — aggiornamento CRM incluso nel piano. Se non risponde: chiama domani.', 'note' => '⚠ A RISCHIO. Non sa delle ultime modifiche CRM. Contattare con urgenza.', 'tag' => 'rischio'],
            ['nome' => 'Cliente a rischio 2', 'ristorante' => '(aggiorna nome)', 'stato' => 'followup', 'priorita' => 'alta', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'followup_date' => $fu1, 'nextstep' => 'WhatsApp oggi — aggiornamento CRM incluso nel piano. Se non risponde: chiama domani.', 'note' => '⚠ A RISCHIO. Non sa delle ultime modifiche CRM. Contattare con urgenza.', 'tag' => 'rischio'],
            ['nome' => 'Cliente a rischio 3', 'ristorante' => '(aggiorna nome)', 'stato' => 'followup', 'priorita' => 'alta', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'followup_date' => $fu2, 'nextstep' => 'WhatsApp oggi — aggiornamento CRM incluso nel piano. Se non risponde: chiama domani.', 'note' => '⚠ A RISCHIO. Non sa delle ultime modifiche CRM.', 'tag' => 'rischio'],
            ['nome' => 'Cliente a rischio 4', 'ristorante' => '(aggiorna nome)', 'stato' => 'followup', 'priorita' => 'alta', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'followup_date' => $fu2, 'nextstep' => 'WhatsApp oggi — aggiornamento CRM incluso nel piano. Se non risponde: chiama domani.', 'note' => '⚠ A RISCHIO. Non sa delle ultime modifiche CRM.', 'tag' => 'rischio'],
            ['nome' => 'Cliente a rischio 5', 'ristorante' => '(aggiorna nome)', 'stato' => 'followup', 'priorita' => 'alta', 'pacchetto' => 'base', 'valore' => 399, 'data_contatto' => '2025-01-01', 'followup_date' => $fu3, 'nextstep' => 'WhatsApp oggi — aggiornamento CRM incluso nel piano. Se non risponde: chiama domani.', 'note' => '⚠ A RISCHIO. Non sa delle ultime modifiche CRM.', 'tag' => 'rischio'],
        ];

        foreach ($clienti as $c) {
            $c['fonte'] = 'diretto';
            PipelineLead::create($c);
        }

        return response()->json(['seeded' => true]);
    }

    // ─── EXPORT CSV ──────────────────────────────────────────────────────────

    public function exportCsv()
    {
        $leads = PipelineLead::orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="pipeline_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($leads) {
            $handle = fopen('php://output', 'w');
            // BOM per Excel italiano
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Nome', 'Ristorante', 'Città', 'Telefono', 'Email', 'Fonte', 'Stato', 'Priorità', 'Pacchetto', 'Valore €', 'Data contatto', 'Follow-up', 'Prossimo step', 'Note'], ';');
            foreach ($leads as $l) {
                fputcsv($handle, [
                    $l->nome, $l->ristorante, $l->citta, $l->telefono, $l->email,
                    $l->fonte, $l->stato, $l->priorita, $l->pacchetto, $l->valore,
                    $l->data_contatto?->format('d/m/Y'), $l->followup_date?->format('d/m/Y'),
                    $l->nextstep, $l->note,
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

    private function formatLead(PipelineLead $l): array
    {
        return [
            'id'            => $l->id,
            'nome'          => $l->nome,
            'ristorante'    => $l->ristorante,
            'citta'         => $l->citta,
            'telefono'      => $l->telefono,
            'email'         => $l->email,
            'fonte'         => $l->fonte,
            'smm_ref'       => $l->smm_ref,
            'stato'         => $l->stato,
            'priorita'      => $l->priorita,
            'pacchetto'     => $l->pacchetto,
            'valore'        => $l->valore,
            'data_contatto' => $l->data_contatto?->format('Y-m-d'),
            'followup_date' => $l->followup_date?->format('Y-m-d'),
            'nextstep'      => $l->nextstep,
            'note'          => $l->note,
            'tag'           => $l->tag,
            'overdue'       => $l->isOverdue(),
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
}

<?php

namespace App\Services;

use App\Models\MarketingItem;
use App\Models\MarketingPlan;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Importa una strategia social (JSON prodotto dalla skill "strategia social")
 * creando piano marketing + contenuti + posizionamento calendario.
 */
class MarketingPlanImportService
{
    /**
     * Mappa: chiave lista JSON → [tipo item, mappatura campi].
     */
    private const LISTS = [
        'posts' => 'post',
        'stories' => 'storia',
        'videos' => 'video',
        'promos' => 'promo',
        'campagne' => 'campagna',
        'automazioni' => 'automazione',
        'modelli' => 'modello',
    ];

    public function import(Site $site, string $json): MarketingPlan
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new InvalidArgumentException('JSON non valido: ' . json_last_error_msg());
        }

        $hasContent = false;
        foreach (array_keys(self::LISTS) as $key) {
            if (! empty($data[$key]) && is_array($data[$key])) {
                $hasContent = true;
                break;
            }
        }

        if (! $hasContent) {
            throw new InvalidArgumentException('Il JSON non contiene nessuna lista di contenuti (posts, stories, videos, promos, campagne, automazioni, modelli).');
        }

        return DB::transaction(function () use ($site, $data) {
            // Re-import: sostituisce il piano esistente (e i suoi item in cascata).
            $site->marketingPlan()?->delete();

            $grid = is_array($data['grid'] ?? null) ? $data['grid'] : [];
            $weeks = count($grid) > 0 ? count($grid) : 4;

            $plan = MarketingPlan::create([
                'site_id' => $site->id,
                'objective' => $this->str($data['obiettivo'] ?? null),
                'timeline_label' => $this->str($data['tempistiche'] ?? null) ?? "$weeks settimane",
                'weeks' => $weeks,
                'start_date' => null,
                'social_status' => is_array($data['stato_social'] ?? null) ? $data['stato_social'] : null,
                'photos_needed' => is_numeric($data['foto_necessarie'] ?? null) ? (int) $data['foto_necessarie'] : null,
                'reels_needed' => is_numeric($data['reel_necessari'] ?? null) ? (int) $data['reel_necessari'] : null,
                'kpis' => [
                    'clienti_online' => 0,
                    'consenso' => 0,
                    'tot_ordini' => 0,
                    'tot_prenotazioni' => 0,
                ],
            ]);

            $placement = $this->parseGrid($grid);

            foreach (self::LISTS as $key => $type) {
                foreach (($data[$key] ?? []) as $raw) {
                    if (! is_array($raw) || empty($raw['id'])) {
                        continue;
                    }

                    $code = (string) $raw['id'];
                    $pos = $placement[$code] ?? [];

                    MarketingItem::create(array_merge(
                        $this->mapItem($type, $raw),
                        [
                            'marketing_plan_id' => $plan->id,
                            'type' => $type,
                            'code' => $code,
                            'week' => $pos['week'] ?? null,
                            'day_index' => $pos['day_index'] ?? null,
                            'slot' => $pos['slot'] ?? null,
                        ]
                    ));
                }
            }

            return $plan;
        });
    }

    /**
     * Estrae da grid[] la posizione calendario di ogni codice contenuto.
     *
     * @return array<string, array{week:int, day_index:int, slot:string}>
     */
    private function parseGrid(array $grid): array
    {
        $placement = [];

        foreach ($grid as $weekBlock) {
            $week = (int) ($weekBlock['week'] ?? 0);
            $days = is_array($weekBlock['days'] ?? null) ? $weekBlock['days'] : [];

            if ($week < 1) {
                continue;
            }

            foreach (array_values($days) as $dayIndex => $day) {
                $slots = is_array($day['slots'] ?? null) ? $day['slots'] : [];

                foreach (MarketingItem::SLOTS as $slot) {
                    $cell = is_array($slots[$slot] ?? null) ? $slots[$slot] : [];

                    foreach ($cell as $code) {
                        if (is_string($code) && $code !== '') {
                            $placement[$code] = [
                                'week' => $week,
                                'day_index' => $dayIndex,
                                'slot' => $slot,
                            ];
                        }
                    }
                }
            }
        }

        return $placement;
    }

    /**
     * Mappa i campi JSON di un contenuto su title/description/payload.
     */
    private function mapItem(string $type, array $raw): array
    {
        $payload = $raw;
        unset($payload['id']);

        switch ($type) {
            case 'post':
            case 'storia':
                $description = $this->str($raw['descrizione'] ?? null);
                unset($payload['descrizione']);

                return ['title' => null, 'description' => $description, 'payload' => $payload];

            case 'video':
                $title = $this->str($raw['titolo'] ?? null);
                $description = $this->str($raw['script'] ?? null);
                unset($payload['titolo'], $payload['script']);

                return ['title' => $title, 'description' => $description, 'payload' => $payload];

            case 'promo':
                $title = $this->str($raw['desc'] ?? null);
                unset($payload['desc']);

                return ['title' => $title, 'description' => null, 'payload' => $payload];

            case 'modello':
                $title = $this->str($raw['titolo'] ?? null);
                $description = $this->str($raw['corpo'] ?? null);
                unset($payload['titolo'], $payload['corpo']);

                return ['title' => $title, 'description' => $description, 'payload' => $payload];

            default: // campagna, automazione
                return ['title' => null, 'description' => null, 'payload' => $payload];
        }
    }

    private function str(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}

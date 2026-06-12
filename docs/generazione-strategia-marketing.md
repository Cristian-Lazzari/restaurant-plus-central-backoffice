# Generazione strategia social — formato JSON per l'import

Istruzioni complete per generare una strategia social compatibile con l'import della Pipeline Marketing del backoffice (`Marketing → Importa strategia`).

Il blocco qui sotto è un **prompt pronto all'uso**: copialo, compila la sezione "Dati del ristorante" e dallo a Claude (o a qualsiasi AI). L'output sarà il JSON da incollare nel form di import.

---

## Prompt da copiare

```
Sei un social media manager esperto di ristorazione. Genera una strategia social
completa per il ristorante descritto sotto.

## Dati del ristorante
- Nome: [NOME RISTORANTE]
- Località: [CITTÀ/ZONA]
- Tipo di cucina / prodotti di punta: [ES. PIZZA, KRESCIA, BURGER...]
- Punti di forza / contesto: [ES. VICINO AL CAMPO PADEL, DEHOR ESTIVO, ZONA TURISTICA...]
- Servizi attivi: [ORDINI ONLINE / ASPORTO / DOMICILIO / PRENOTAZIONE TAVOLI]
- Stato canali social: Instagram: [...], Facebook: [...], TikTok: [...], SMM dedicato: [SÌ/NO]
- Obiettivo principale: [ES. AUMENTARE ORDINI ONLINE E PRENOTAZIONI]
- Durata piano: [N] settimane

## Cosa devi produrre
Una strategia con questi volumi (adattali alla durata del piano):
- 12-16 post organici Instagram/Facebook
- 14-18 storie
- 4-8 video brevi (max 2 minuti) con script scena per scena
- 4-6 promozioni collegate a trigger concreti
- 2-4 campagne (organiche o WhatsApp) che usano modelli e promo
- 4-6 automazioni email/WhatsApp basate su trigger comportamentali
- 3-5 modelli messaggio riutilizzabili da campagne e automazioni
- Un calendario editoriale che distribuisce post, storie, video, promo e
  campagne sulle settimane del piano

Testi pronti da pubblicare (con emoji e hashtag), in italiano, nel tono del
locale. Ogni post/storia deve indicare anche la foto o il video da produrre,
descritto in modo che chiunque possa scattarlo.

## Formato di output — OBBLIGATORIO
Rispondi SOLO con un blocco JSON valido (nessun testo prima o dopo, nessun
commento dentro il JSON), con esattamente questa struttura:

{
  "ristorante": "Nome del ristorante",
  "obiettivo": "Obiettivo della strategia in una frase",
  "tempistiche": "4 settimane",
  "stato_social": {
    "instagram": "es. Poco attivo",
    "facebook": "es. Assente / da creare",
    "tiktok": "es. Assente / da creare",
    "smm": "es. No (gestito da staff interno)"
  },
  "foto_necessarie": 18,
  "reel_necessari": 8,
  "posts": [
    {
      "id": "P-1",
      "descrizione": "Testo completo del post pronto da pubblicare, con emoji e hashtag",
      "foto": "Descrizione della foto/visual da produrre (inquadratura, luce, soggetto)"
    }
  ],
  "stories": [
    {
      "id": "S-1",
      "descrizione": "Testo/idea della storia",
      "foto": "Descrizione del visual (video breve, grafica poll, countdown...)"
    }
  ],
  "videos": [
    {
      "id": "V-1",
      "titolo": "Titolo del video",
      "durata": 30,
      "ambientazione": "Dove si gira (cucina a vista, sala, dehor...)",
      "script": "Scena 1 (0-5s): ... Scena 2 (5-12s): ... con testi overlay e indicazioni di ripresa",
      "tono": "es. Autentico, energico",
      "cta": "es. Ordina ora, link in bio",
      "promo": "PR-1"
    }
  ],
  "promos": [
    {
      "id": "PR-1",
      "desc": "es. 10% di sconto sul primo ordine online",
      "trigger": "es. Ordini / Prenotazioni / Iscrizione",
      "minimo": "es. 15€",
      "applicabile": "es. Carrello / Tavolo",
      "tipo_sconto": "Percentuale | Fisso",
      "sconto": "es. 10% oppure 5€",
      "cta": "es. Ordina ora",
      "riusabile": "Sì | No"
    }
  ],
  "campagne": [
    {
      "id": "C-1",
      "modello": "M-1",
      "promo": "PR-1",
      "tipo": "Organica | WhatsApp",
      "segmento": "A chi è rivolta (es. nuovi follower, clienti inattivi 30gg)",
      "canale": "Instagram | Facebook | WhatsApp | Email"
    }
  ],
  "automazioni": [
    {
      "id": "A-1",
      "modello": "M-1",
      "promo": "PR-1",
      "trigger": "es. Dopo il primo ordine / 30 giorni di inattività",
      "canale": "Email | WhatsApp"
    }
  ],
  "modelli": [
    {
      "id": "M-1",
      "titolo": "Oggetto/titolo del messaggio",
      "corpo": "Testo del messaggio, può usare {{nome_cliente}}",
      "conclusione": "Frase di chiusura con invito all'azione",
      "foto": "Visual consigliato per il messaggio"
    }
  ],
  "grid": [
    {
      "week": 1,
      "days": [
        { "day": "Lun", "slots": { "mattina": { "post": null, "storia": "S-1", "promo": null, "campagna": null, "video": null }, "pomeriggio": { "post": "P-1", "storia": null, "promo": null, "campagna": null, "video": null } } },
        { "day": "Mar", "slots": { "mattina": { "post": null, "storia": "S-2", "promo": null, "campagna": null, "video": null }, "pomeriggio": { "post": "P-2", "storia": null, "promo": null, "campagna": null, "video": null } } },
        { "day": "Mer", "slots": { "mattina": { "post": null, "storia": null, "promo": null, "campagna": null, "video": null }, "pomeriggio": { "post": null, "storia": null, "promo": null, "campagna": null, "video": "V-1" } } },
        { "day": "Gio", "slots": { "mattina": { "post": null, "storia": null, "promo": "PR-1", "campagna": null, "video": null }, "pomeriggio": { "post": null, "storia": null, "promo": null, "campagna": null, "video": null } } },
        { "day": "Ven", "slots": { "mattina": { "post": null, "storia": null, "promo": null, "campagna": "C-1", "video": null }, "pomeriggio": { "post": "P-3", "storia": null, "promo": null, "campagna": null, "video": null } } },
        { "day": "Sab", "slots": { "mattina": { "post": null, "storia": "S-3", "promo": null, "campagna": null, "video": null }, "pomeriggio": { "post": null, "storia": null, "promo": null, "campagna": null, "video": null } } },
        { "day": "Dom", "slots": { "mattina": { "post": null, "storia": null, "promo": null, "campagna": null, "video": null }, "pomeriggio": { "post": null, "storia": null, "promo": null, "campagna": null, "video": null } } }
      ]
    }
  ]
}

## Regole vincolanti
1. ID con questi prefissi e numerazione progressiva da 1, senza buchi:
   post P-1, P-2...; storie S-n; video V-n; promo PR-n; campagne C-n;
   automazioni A-n; modelli M-n. Ogni ID è unico in tutto il documento.
2. "grid" deve avere UN oggetto per ogni settimana del piano ("week": 1, 2, ...)
   e OGNI settimana deve avere ESATTAMENTE 7 giorni nell'ordine
   Lun, Mar, Mer, Gio, Ven, Sab, Dom.
3. Ogni giorno ha solo due fasce: "mattina" e "pomeriggio". In ogni fascia le
   chiavi sono esattamente: post, storia, promo, campagna, video — con valore
   l'ID del contenuto oppure null. Massimo un contenuto per chiave per fascia.
4. Nel calendario vanno SOLO post, storie, video, promo e campagne.
   Automazioni e modelli NON vanno nel calendario (lavorano in automatico).
5. Ogni ID di post, storia e video deve comparire nel calendario ESATTAMENTE
   una volta. Promo e campagne compaiono nel giorno di lancio.
6. I riferimenti incrociati devono esistere: se una campagna cita
   "modello": "M-2" e "promo": "PR-3", quegli ID devono essere definiti
   nelle rispettive liste. Idem per "promo" nei video e nelle automazioni.
   Se un video o un'automazione non ha promo collegata, usa null.
7. "durata" dei video è un numero (secondi), massimo 120.
8. "foto_necessarie" e "reel_necessari" sono numeri interi: conta le foto e i
   video/reel effettivamente richiesti dai contenuti.
9. JSON valido al 100%: virgolette doppie, niente virgole finali, niente
   commenti, emoji ammesse nei testi.
```

---

## Note tecniche sull'import (per chi sviluppa)

Cosa fa il parser (`app/Services/MarketingPlanImportService.php`):

- **Chiavi lette a livello piano**: `obiettivo`, `tempistiche`, `stato_social`, `foto_necessarie`, `reel_necessari`. `ristorante` è ignorata (il piano è già legato al sito). Il numero di settimane = numero di elementi in `grid` (default 4 se assente).
- **Liste contenuti**: `posts`, `stories`, `videos`, `promos`, `campagne`, `automazioni`, `modelli`. Serve almeno una lista non vuota, altrimenti l'import viene rifiutato. Ogni elemento senza `id` viene scartato.
- **Mappatura campi**: post/storie → `descrizione` diventa il testo principale; video → `titolo` + `script`; promo → `desc` diventa il titolo; modelli → `titolo` + `corpo`. Tutti gli altri campi finiscono nel payload e vengono mostrati nella scheda con etichetta automatica — quindi campi extra non previsti dallo schema non rompono nulla, vengono semplicemente mostrati.
- **Calendario**: la posizione (settimana, giorno, fascia) viene letta da `grid`; il `day_index` deriva dalla **posizione** nell'array `days` (0 = Lun), non dall'etichetta `day`. Fasce valide: solo `mattina` e `pomeriggio`. ID nel grid che non esistono nelle liste vengono ignorati senza errore; contenuti non presenti nel grid restano fuori calendario (normale per automazioni e modelli).
- **Vincolo DB**: `code` unico per piano — due contenuti con lo stesso ID fanno fallire l'import.
- **Re-import**: sostituisce integralmente il piano esistente del ristorante; avanzamento, date e note vengono azzerati.
- La **data di inizio** non fa parte del JSON: si imposta dopo l'import nella tab Panoramica del piano.

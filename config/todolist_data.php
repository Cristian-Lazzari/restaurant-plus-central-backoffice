<?php

/**
 * Dati strutturali del Piano 90 Giorni Future Plus.
 * La struttura (settimane, giorni, task) è statica.
 * Solo lo stato di completamento (checkbox) viene persistito in DB.
 *
 * Chiave task: "{week_id}_{day_index}_{block_index}_{task_index}"
 */

return [
    // ═══ MESE 1 ═══
    [
        'id' => 'w1', 'month' => 1, 'label' => 'Settimana 1', 'dates' => 'Giu 9–13',
        'color' => '#f87171',
        'subtitle' => 'Prima i clienti attuali — retention, poi acquisizione',
        'focus' => '🛡 Retention Clienti + Fondamenta',
        'goals' => ['Clienti a rischio contattati e aggiornati sul CRM', 'Zona Pub e clienti sicuri consolidati', 'Landing aggiornata con dati reali'],
        'days' => [
            [
                'name' => 'Mar 9', 'theme' => 'PRIORITÀ: Retention — clienti a rischio', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–10:00', 'label' => 'Mattina — Mappa situazione clienti', 'tasks' => [
                        ['text' => 'Fai un elenco completo dei 10 clienti attuali con stato: SICURI (Locanda del Duca, Classico Maglie, Il Tuo Lounge Restaurant, I Capricci di Leo, Zona Pub) e A RISCHIO (tutti gli altri). Segna nella pipeline chi manca.', 'tag' => 'ops'],
                        ['text' => 'Per ogni cliente a rischio: scrivi in 1 riga perché potrebbe andarsene e cosa puoi offrirgli oggi (aggiornamento CRM incluso nel pacchetto, nuove funzioni, supporto dedicato).', 'tag' => 'ops'],
                    ]],
                    ['time' => '10:00–12:00', 'label' => 'Tarda mattina — WhatsApp clienti a rischio (primo giro)', 'tasks' => [
                        ['text' => 'Manda WhatsApp personalizzato a ogni cliente a rischio. Tono caldo, non commerciale: "Ciao [Nome], volevo aggiornarti personalmente su alcune novità importanti che abbiamo aggiunto alla piattaforma — e che sono già incluse nel tuo pacchetto senza costi aggiuntivi. Hai 10 minuti questa settimana per una chiamata veloce?"', 'tag' => 'sales'],
                        ['text' => 'Manda il messaggio uno per uno, personalizzato con il nome del ristorante. Non mandare lo stesso testo copia-incolla — si sente.', 'tag' => 'sales'],
                        ['text' => 'Segna nella pipeline: data WhatsApp inviato, testo usato, risposta attesa entro 24h.', 'tag' => 'ops'],
                    ]],
                    ['time' => '13:00–15:00', 'label' => 'Pomeriggio — Prepara il materiale di aggiornamento CRM', 'tasks' => [
                        ['text' => "Crea un documento semplice (PDF o messaggio lungo) \"Cosa c'è di nuovo nel tuo pacchetto Future Plus\": elenca le funzionalità CRM aggiornate in modo comprensibile per un ristoratore, non tecnico.", 'tag' => 'content'],
                        ['text' => 'Prepara 3 esempi concreti di cosa possono fare ORA con il CRM aggiornato che prima non potevano. Questi esempi sono la leva per convincerli a restare.', 'tag' => 'content'],
                    ]],
                    ['time' => '15:00–17:00', 'label' => 'Pomeriggio — WhatsApp clienti sicuri (Zona Pub + altri)', 'tasks' => [
                        ['text' => 'Manda WhatsApp anche a Zona Pub e agli altri clienti sicuri: non per retention ma per rafforzare il rapporto. "Ciao [Nome], stiamo aggiornando la piattaforma con nuove funzioni CRM incluse nel tuo piano — ti faccio sapere appena pronte. Se hai feedback o cose che vorresti migliorare, diccelo!"', 'tag' => 'sales'],
                        ['text' => 'Questo serve a farli sentire parte del progetto, non solo clienti paganti. È la base per ottenere testimonianze e referral.', 'tag' => 'sales'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Fine giornata', 'tasks' => [
                        ['text' => 'Aggiorna pipeline con tutti gli stati: chi ha già risposto al WhatsApp? Chi non ha ancora risposto? Imposta reminder per follow-up chiamata domani mattina per chi non risponde.', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 10', 'theme' => 'Chiamate clienti a rischio + landing page', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Chiamate clienti a rischio', 'tasks' => [
                        ['text' => 'Chiama uno per uno i clienti a rischio che non hanno risposto al WhatsApp di ieri. Obiettivo della chiamata: non vendere, ma aggiornare.', 'tag' => 'sales'],
                        ['text' => 'Durante la chiamata: ascolta se c\'è insoddisfazione latente. Se emergono problemi (non usano la piattaforma, hanno difficoltà tecniche, non vedono valore) — prendine nota e proponi una sessione di supporto gratuita.', 'tag' => 'sales'],
                        ['text' => 'Dopo ogni chiamata: aggiorna pipeline con esito (soddisfatto / a rischio confermato / ha chiesto supporto / ha disdetto). Questa è informazione oro.', 'tag' => 'ops'],
                    ]],
                    ['time' => '11:00–13:00', 'label' => 'Tarda mattina — Sessioni supporto clienti', 'tasks' => [
                        ['text' => 'Per i clienti che hanno mostrato interesse all\'aggiornamento CRM: fissa una call di 20 minuti per mostrargli le nuove funzioni in diretta. Questo aumenta drasticamente il loro engagement e la probabilità di rinnovo.', 'tag' => 'sales'],
                        ['text' => 'Prepara uno script per la call di aggiornamento CRM: 5min "cosa puoi fare ora che prima non potevi" → 10min demo live delle funzioni nuove → 5min "hai domande o cose che vorresti migliorare?"', 'tag' => 'content'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Landing page + dati reali', 'tasks' => [
                        ['text' => 'Raccogli tutti i dati reali dal back office: €22.497 risparmio, 5.500 coperti, 1.677 prenotazioni. Aggiorna la landing con questi numeri in modo visivo e immediato.', 'tag' => 'ops'],
                        ['text' => 'Aggiorna la landing page online: headline, social proof con numeri reali, sezione "I nostri clienti risparmiano in media €2.250/anno" con calcolo visivo.', 'tag' => 'content'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Fine giornata', 'tasks' => [
                        ['text' => 'Aggiorna pipeline retention: quanti clienti a rischio contattati? Quanti hanno risposto positivamente? Quanti richiedono follow-up? Quanti sono ancora irraggiungibili?', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 11', 'theme' => 'Demo CRM ai clienti a rischio + testimonianza video', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–10:30', 'label' => 'Mattina — Demo CRM live per clienti a rischio', 'tasks' => [
                        ['text' => 'Conduci le call di aggiornamento CRM con i clienti a rischio che le hanno accettate ieri. Mostra in diretta: gestione clienti abituali, invio promozioni mirate, statistiche prenotazioni.', 'tag' => 'sales'],
                        ['text' => 'Al termine di ogni call: chiedi esplicitamente "Sei soddisfatto? C\'è qualcosa che non funziona come vorresti?" — le risposte ti danno il polso reale della situazione.', 'tag' => 'sales'],
                        ['text' => 'Chiama Classico Maglie per fissare la video-testimonianza: 60 secondi, risparmio e coperti gestiti. Offri un mese gratis come ringraziamento.', 'tag' => 'sales'],
                    ]],
                    ['time' => '10:30–13:00', 'label' => 'Tarda mattina — Offerta ingresso', 'tasks' => [
                        ['text' => 'Decidi l\'offerta di ingresso definitiva. Opzione consigliata: "Setup gratuito (valore €150) + primo mese incluso" — abbassa la barriera senza svalutare il prodotto.', 'tag' => 'ops'],
                        ['text' => 'Scrivi la pagina/sezione offerta sul sito con scadenza (es. "Offerta valida fino al 30 giugno") — la scarsità aumenta le conversioni.', 'tag' => 'content'],
                        ['text' => 'Prepara la sequenza post-lead: cosa succede quando qualcuno lascia il contatto? Email 1 (immediata), Email 2 (24h), Email 3 (72h). Scrivi i testi.', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio — Creative ads (bozze)', 'tasks' => [
                        ['text' => 'Scrivi il copy per la Creative A (video): hook "Stai pagando il 13% a JustEat su ogni prenotazione. I tuoi concorrenti no." + soluzione + CTA.', 'tag' => 'ads'],
                        ['text' => 'Scrivi il copy per la Creative B (immagine statica): numero grande "€22.497 risparmiati dai nostri clienti" + logo FP + CTA.', 'tag' => 'ads'],
                        ['text' => 'Registra o fai registrare il video 20 secondi per la creative A (anche con telefono, montaggio semplice, sottotitoli in italiano).', 'tag' => 'ads'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 12', 'theme' => 'Follow-up retention + primo outreach SMM warm', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–10:00', 'label' => 'Mattina — Follow-up retention', 'tasks' => [
                        ['text' => 'Chiama o manda WhatsApp di follow-up ai clienti a rischio ancora silenti. Ultimo tentativo caldo prima di trattarli come urgenza critica.', 'tag' => 'sales'],
                        ['text' => 'Per chi ha già fatto la call di aggiornamento CRM: manda un messaggio di ringraziamento con 1-2 tip su come sfruttare subito le nuove funzioni. Mantieni il contatto caldo.', 'tag' => 'sales'],
                    ]],
                    ['time' => '10:00–12:00', 'label' => 'Tarda mattina — Primo outreach SMM warm', 'tasks' => [
                        ['text' => 'Invia i primi 5 DM Instagram agli SMM identificati dai profili dei tuoi clienti attuali (quelli "warm"). Usa lo script warm preparato.', 'tag' => 'smm'],
                        ['text' => 'Invia connessione + messaggio LinkedIn agli stessi 5 (doppio canale = più probabilità di risposta).', 'tag' => 'smm'],
                        ['text' => 'Per ogni SMM contattato: segna data di contatto e canale usato nella pipeline.', 'tag' => 'ops'],
                    ]],
                    ['time' => '12:00–13:00', 'label' => 'Mezzogiorno — Setup Meta Ads', 'tasks' => [
                        ['text' => 'Apri Meta Ads Manager. Crea il Business Account se non esiste già. Collega il pixel alla landing page.', 'tag' => 'ads'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Altro outreach + ricerca', 'tasks' => [
                        ['text' => 'Continua ricerca SMM: altri 10 profili da LinkedIn/Instagram. Obiettivo 30 nella lista entro venerdì.', 'tag' => 'smm'],
                        ['text' => 'Invia altri 5 DM agli SMM "cold" trovati online. Personalizza ogni messaggio con un dettaglio del loro profilo.', 'tag' => 'smm'],
                        ['text' => 'Ricerca competitor: guarda come TheFork, JustEat, e i competitor SaaS italiani si posizionano. Prendi nota delle loro leve di vendita per usarle in contrasto.', 'tag' => 'ops'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Fine giornata', 'tasks' => [
                        ['text' => 'Controlla risposte ai DM inviati. Rispondi entro 2 ore sempre — la velocità di risposta è cruciale nelle prime conversazioni.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 13', 'theme' => 'Follow-up + setup pipeline vendite', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–10:00', 'label' => 'Mattina — Revisione risposte', 'tasks' => [
                        ['text' => 'Controlla tutti i canali (Instagram DM, LinkedIn, email) e rispondi a chi ha risposto. Se qualcuno vuole saperne di più: proponi una call di 20 minuti.', 'tag' => 'sales'],
                    ]],
                    ['time' => '10:00–13:00', 'label' => 'Tarda mattina — Pipeline vendite', 'tasks' => [
                        ['text' => 'Crea il foglio "Sales Pipeline": colonne Lead | Fonte | Data primo contatto | Stato | Note | Prossimo step.', 'tag' => 'ops'],
                        ['text' => 'Imposta un reminder giornaliero fisso alle 09:00 per controllare la pipeline e aggiornare gli stati.', 'tag' => 'ops'],
                        ['text' => 'Scrivi il deck demo: 8 slide per presentare FP in 20 minuti. Struttura: problema → soluzione → demo live → numeri reali → offerta → prossimo step.', 'tag' => 'content'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Creative ads (finalizzazione)', 'tasks' => [
                        ['text' => 'Finalizza il video 20s per Meta Ads: sottotitoli, logo, CTA finale "Scopri di più" o "Prenota una demo".', 'tag' => 'ads'],
                        ['text' => 'Crea l\'immagine statica per la Creative B su Canva: sfondo scuro, numero grande, logo FP, CTA.', 'tag' => 'ads'],
                        ['text' => 'Configura in Meta Ads Manager la campagna di test: obiettivo Lead Generation, audience ristoratori 30-55 Italia, budget €200 totale su 10 giorni.', 'tag' => 'ads'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Review settimana 1', 'tasks' => [
                        ['text' => 'Review settimanale: quanti SMM contattati? Quante risposte? Landing aggiornata? Creative pronte? Scrivi 3 cose che hai imparato questa settimana.', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 14 (riposo)', 'theme' => 'Lancio ads + outreach intensivo SMM', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Lancio Meta Ads', 'tasks' => [
                        ['text' => 'Carica le creative in Meta Ads Manager. Lancia la campagna di test €200 (Creative A vs Creative B, 2 audience diverse).', 'tag' => 'ads'],
                        ['text' => 'Imposta il form di lead generation nativo Meta: nome, email, telefono, "Sei il proprietario del ristorante?" (sì/no).', 'tag' => 'ads'],
                    ]],
                    ['time' => '11:00–13:00', 'label' => 'Tarda mattina — Outreach SMM', 'tasks' => [
                        ['text' => 'Invia altri 10 DM/messaggi agli SMM in lista. Obiettivo: 30 SMM contattati entro fine settimana.', 'tag' => 'smm'],
                        ['text' => 'Per gli SMM che non hanno risposto nei giorni scorsi: invia follow-up leggero "Ciao, hai avuto modo di vedere il mio messaggio?"', 'tag' => 'smm'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Contenuto organico', 'tasks' => [
                        ['text' => 'Scrivi e pubblica un post LinkedIn dal profilo di Cristian: storia di Locanda del Duca (118 coperti/mese senza advertising). Usa i numeri reali.', 'tag' => 'content'],
                        ['text' => 'Crea 3 post Instagram per il profilo FP: 1) numero (€22k risparmiati), 2) come funziona WA per il ristoratore, 3) confronto FP vs commissioni.', 'tag' => 'content'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Fine settimana 1', 'tasks' => [
                        ['text' => 'Aggiorna pipeline. Conta: SMM contattati, risposte ricevute, call fissate. Prepara mentalmente le priorità per lunedì.', 'tag' => 'ops'],
                    ]],
                ],
            ],
        ],
    ],

    // ═══ SETTIMANA 2 ═══
    [
        'id' => 'w2', 'month' => 1, 'label' => 'Settimana 2', 'dates' => 'Giu 16–20',
        'color' => '#f87171',
        'subtitle' => 'Prime call, prime demo, ottimizzazione outreach',
        'focus' => '📞 Prime Demo & Conversazioni',
        'goals' => ['5+ call/demo con SMM o lead diretti', 'Testimonianza video registrata', 'Ads: primi dati di costo per lead'],
        'days' => [
            [
                'name' => 'Mar 16', 'theme' => 'Prime call SMM + analisi ads', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–09:30', 'label' => 'Apertura giornata', 'tasks' => [
                        ['text' => 'Controlla pipeline: chi ha risposto nel weekend? Rispondi a tutti entro le 09:30. Aggiorna stati nella pipeline.', 'tag' => 'ops'],
                        ['text' => 'Controlla Meta Ads: quante impression, click, lead nelle prime 48h? Annota i dati base.', 'tag' => 'ads'],
                    ]],
                    ['time' => '09:30–12:00', 'label' => 'Mattina — Outreach', 'tasks' => [
                        ['text' => 'Invia altri 10 DM a nuovi SMM. Questa settimana punta a contattare anche SMM che gestiscono ristoranti fuori dalla tua area geografica.', 'tag' => 'smm'],
                        ['text' => 'Per chi ha risposto positivamente: proponi call di 20 minuti. Usa Calendly o simile per rendere facile la prenotazione.', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Prima call/demo', 'tasks' => [
                        ['text' => 'Conduci le call fissate (se ci sono). Struttura: 5min ascolto loro situazione → 10min demo live → 5min offerta → prossimo step.', 'tag' => 'sales'],
                        ['text' => 'Dopo ogni call: scrivi note nella pipeline, identifica l\'obiezione principale, imposta reminder per follow-up a 48h.', 'tag' => 'ops'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Fine giornata', 'tasks' => [
                        ['text' => 'Registra testimonianza video se il cliente è disponibile oggi. Altrimenti riconferma data con Classico Maglie.', 'tag' => 'content'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 17', 'theme' => 'Outreach cold + ottimizzazione script', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–09:30', 'label' => 'Pipeline review', 'tasks' => [
                        ['text' => 'Aggiorna pipeline. Controlla Meta Ads: CTR delle due creative, CPL stimato. Quale sta performando meglio?', 'tag' => 'ops'],
                    ]],
                    ['time' => '09:30–13:00', 'label' => 'Mattina — Outreach massiccio', 'tasks' => [
                        ['text' => 'Ricerca nuovi 15 SMM: concentrati su Instagram cercando ristoranti con buona presenza social e guarda chi li gestisce.', 'tag' => 'smm'],
                        ['text' => 'Invia 15 messaggi di outreach personalizzati. Personalizzazione minima: nomina un loro cliente ristorante nel messaggio.', 'tag' => 'smm'],
                        ['text' => 'Prova anche approccio diverso: commenta con valore un loro post recente PRIMA di mandare il DM. Aumenta il tasso di risposta.', 'tag' => 'smm'],
                    ]],
                    ['time' => '14:00–16:00', 'label' => 'Pomeriggio — Contenuto', 'tasks' => [
                        ['text' => 'Scrivi caso studio scritto di Classico Maglie (1 pagina): situazione prima FP, numeri dopo, quote del titolare. Da usare nel kit SMM e sul sito.', 'tag' => 'content'],
                        ['text' => 'Trasforma il caso studio in 3 formati: post LinkedIn, post Instagram, sezione del PDF kit SMM.', 'tag' => 'content'],
                    ]],
                    ['time' => '16:00–18:00', 'label' => 'Pomeriggio — Call/Demo', 'tasks' => [
                        ['text' => 'Conduci call fissate. Dopo ogni call persa/rifiuto: analizza l\'obiezione e migliora lo script per il prossimo.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 18', 'theme' => 'Testimonianza video + ottimizzazione landing', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Testimonianza', 'tasks' => [
                        ['text' => 'Registra la testimonianza video (20–60 secondi) con Classico Maglie o Locanda del Duca. Se in presenza non è possibile: guida il titolare via WhatsApp su come registrarla da solo.', 'tag' => 'content'],
                        ['text' => 'Monta il video: taglia, aggiungi sottotitoli, logo FP in basso, musica di sottofondo leggera. Strumento: CapCut (gratis) o DaVinci Resolve.', 'tag' => 'content'],
                    ]],
                    ['time' => '13:00–15:00', 'label' => 'Pomeriggio — Landing', 'tasks' => [
                        ['text' => 'Aggiungi il video testimonianza alla landing page. Posizionalo subito dopo la sezione social proof (numeri).', 'tag' => 'ops'],
                        ['text' => 'Aggiungi una sezione FAQ alla landing: 5 domande frequenti dei ristoratori. Rispondi alle obiezioni prima che le facciano.', 'tag' => 'content'],
                    ]],
                    ['time' => '15:00–18:00', 'label' => 'Pomeriggio — Outreach + call', 'tasks' => [
                        ['text' => 'Invia 10 nuovi messaggi SMM. Conduci le call fissate. Aggiorna pipeline.', 'tag' => 'smm'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 19', 'theme' => 'Analisi ads + call/demo intensive', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–10:30', 'label' => 'Mattina — Analisi Meta Ads', 'tasks' => [
                        ['text' => 'Analisi completa campagna test dopo 7 giorni: CPL, CTR, frequenza, quali audience performano. Prendi decisione: quale creative scalare?', 'tag' => 'ads'],
                        ['text' => 'Se CPL > €40: cambia audience o creative. Se CPL €15–30: ottimo, scala. Se hai già 10+ lead: analizza la qualità.', 'tag' => 'ads'],
                    ]],
                    ['time' => '10:30–13:00', 'label' => 'Tarda mattina — Call intensive', 'tasks' => [
                        ['text' => 'Blocca 2.5 ore solo per call e demo. Obiettivo: 3 call oggi. Usa un timer: 20 minuti per call, non di più.', 'tag' => 'sales'],
                        ['text' => 'Dopo ogni call: 5 minuti di note nella pipeline. Non rimandare.', 'tag' => 'ops'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio — Partnership formale', 'tasks' => [
                        ['text' => 'Per gli SMM che hanno mostrato interesse: manda proposta formale via email. Includi: referral fee (€60 per cliente attivato), kit materiali, accordo semplice.', 'tag' => 'smm'],
                        ['text' => 'Scrivi email di follow-up per chi non ha risposto ai DM da 5+ giorni: tono diverso, nuovo angolo.', 'tag' => 'smm'],
                        ['text' => 'Continua outreach: altri 10 SMM nuovi nella lista.', 'tag' => 'smm'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 20', 'theme' => 'Chiusura prima partnership SMM', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Chiusura accordi', 'tasks' => [
                        ['text' => 'Follow-up telefonico agli SMM più caldi. Obiettivo: chiudere il primo accordo formale questa settimana.', 'tag' => 'smm'],
                        ['text' => 'Per chi è pronto: manda l\'accordo via email (testo semplice). "Ecco come funziona la partnership: tu segnali, io vendo, ti pago €60 entro 7 giorni dall\'attivazione."', 'tag' => 'smm'],
                    ]],
                    ['time' => '13:00–15:00', 'label' => 'Pomeriggio — Contenuto organico', 'tasks' => [
                        ['text' => 'Pubblica il caso studio Classico Maglie su LinkedIn (formato articolo lungo). È il contenuto più potente che hai.', 'tag' => 'content'],
                        ['text' => 'Pubblica il video testimonianza su Instagram e LinkedIn. Aggiungi caption con i numeri chiave.', 'tag' => 'content'],
                    ]],
                    ['time' => '15:00–17:30', 'label' => 'Pomeriggio — Outreach', 'tasks' => [
                        ['text' => 'Altri 10 SMM contattati. Questa settimana dovresti aver raggiunto 50+ SMM totali nella pipeline.', 'tag' => 'smm'],
                        ['text' => 'Fai pulizia pipeline: chi è "morto" (nessuna risposta dopo 2 follow-up)? Segna come "perso" e non perdere altro tempo.', 'tag' => 'ops'],
                    ]],
                    ['time' => '17:30–18:00', 'label' => 'Review settimana 2', 'tasks' => [
                        ['text' => 'KPI settimana 2: SMM totali contattati, risposte ricevute, call fatte, demo completate, accordi firmati, lead da ads, CPL. Scrivi il numero accanto a ogni voce.', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 21 (riposo)', 'theme' => 'Scale ads + outreach + contenuto', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Scale ads', 'tasks' => [
                        ['text' => 'Scala la creative vincente: porta il budget da €200 test a €550 campagna principale. Mantieni la stessa audience che ha performato meglio.', 'tag' => 'ads'],
                        ['text' => 'Lancia la campagna retargeting €150 per chi ha visitato la landing ma non ha lasciato il contatto.', 'tag' => 'ads'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Produzione contenuto', 'tasks' => [
                        ['text' => 'Crea 5 post pianificati per la prossima settimana (Instagram FP + LinkedIn personale). Usa Buffer o Later per programmarli.', 'tag' => 'content'],
                        ['text' => 'Scrivi email newsletter per i tuoi 10 clienti attuali: aggiornamento prodotto + chiedi referral.', 'tag' => 'sales'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Fine settimana 2', 'tasks' => [
                        ['text' => 'Aggiorna tutta la pipeline. Conta: partnership SMM firmate (target: almeno 1). Lead da ads totali. Clienti chiusi (target: 1 primo nuovo cliente).', 'tag' => 'ops'],
                    ]],
                ],
            ],
        ],
    ],

    // ═══ SETTIMANA 3 ═══
    [
        'id' => 'w3', 'month' => 1, 'label' => 'Settimana 3', 'dates' => 'Giu 23–27',
        'color' => '#f87171',
        'subtitle' => 'Primo nuovo cliente + sistema di vendita che gira',
        'focus' => '🎯 Primo Cliente Nuovo',
        'goals' => ['Almeno 1 nuovo cliente pagante', '2+ partnership SMM attive', 'Pipeline con 20+ lead attivi'],
        'days' => [
            [
                'name' => 'Mar 23', 'theme' => 'Follow-up aggressivo + call block', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–09:30', 'label' => 'Apertura', 'tasks' => [
                        ['text' => 'Pipeline review: chi è pronto per chiudere? Identifica i 3 lead più caldi e mettili in cima alla lista del giorno.', 'tag' => 'ops'],
                    ]],
                    ['time' => '09:30–13:00', 'label' => 'Mattina — Sales block', 'tasks' => [
                        ['text' => 'Dedica 3.5 ore solo a chiamate e follow-up. Chiama (non solo scrivi) i lead più caldi. La voce converte meglio del testo.', 'tag' => 'sales'],
                        ['text' => 'Per chi ha fatto la demo ma non ha ancora deciso: offri una call di "supporto decisionale" — aiutali a capire il ROI specifico per il loro ristorante.', 'tag' => 'sales'],
                        ['text' => 'Calcola live con loro: "Quante prenotazioni ricevete al mese? Quante via TheFork? A €4/prenotazione quanto spendete?" Poi mostra il confronto con FP.', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio — Outreach + contenuto', 'tasks' => [
                        ['text' => 'Invia 15 messaggi SMM. Questa settimana prova anche email diretta se trovi indirizzi pubblici dei loro siti.', 'tag' => 'smm'],
                        ['text' => 'Pubblica contenuto: post con il confronto commissioni (grafico visivo: TheFork €X/anno vs FP €399/anno).', 'tag' => 'content'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 24', 'theme' => 'Outreach SMM massiccio + analisi risposta', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–09:30', 'label' => 'Apertura', 'tasks' => [
                        ['text' => 'Pipeline review: chi ha risposto nelle ultime 24h? Aggiorna stati. Controlla Meta Ads: come sta andando la campagna dopo i primi giorni di settimana 3?', 'tag' => 'ops'],
                    ]],
                    ['time' => '09:30–13:00', 'label' => 'Mattina — Outreach SMM', 'tasks' => [
                        ['text' => 'Invia 15 messaggi SMM nuovi. Questa settimana prova a personalizzare ancora di più: cita un post recente del loro ristorante cliente nel messaggio.', 'tag' => 'smm'],
                        ['text' => 'Analizza le risposte ricevute finora dagli SMM: qual è la percentuale di risposta? Quale tipo di messaggio funziona meglio? Aggiorna il template con quello che funziona.', 'tag' => 'smm'],
                        ['text' => 'Per ogni SMM che ha risposto positivamente: proponi call entro 48 ore. Usa Calendly per rendere facile la prenotazione.', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Call block', 'tasks' => [
                        ['text' => 'Conduci le call fissate. Struttura: 5 min ascolto → 10 min demo → 5 min offerta. Non andare oltre i 20 minuti.', 'tag' => 'sales'],
                        ['text' => 'Dopo ogni call: 5 minuti di note nella pipeline. Obiezione principale? Interesse reale? Prossimo step?', 'tag' => 'ops'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Fine giornata', 'tasks' => [
                        ['text' => 'Aggiorna pipeline. Segna: quanti SMM contattati in totale, quante risposte, quante call fissate. Sei in linea con l\'obiettivo 20+ lead attivi?', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 25', 'theme' => 'Call block + ottimizzazione creative ads', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Call block dedicato', 'tasks' => [
                        ['text' => 'Mattina interamente dedicata a call. Obiettivo: 3 call oggi. Chiama i lead caldi che non hanno ancora risposto al messaggio — la voce converte molto meglio del testo.', 'tag' => 'sales'],
                        ['text' => 'Per i lead che hanno già fatto la demo ma non hanno deciso: proponi una "call di supporto decisionale" — calcolatrice ROI personalizzata per il loro ristorante.', 'tag' => 'sales'],
                    ]],
                    ['time' => '13:00–15:00', 'label' => 'Pomeriggio — Ottimizzazione ads', 'tasks' => [
                        ['text' => 'Analizza i dati Meta Ads dopo 10+ giorni: quale creative ha il CTR più alto? Quale audience porta i lead più qualificati (chi risponde "sì" alla domanda "Sei il proprietario")?', 'tag' => 'ads'],
                        ['text' => 'Crea la Creative C: nuova variante basata sull\'angolo che ha funzionato meglio. Testa un headline diverso o un\'immagine diversa mantenendo il copy vincente.', 'tag' => 'ads'],
                    ]],
                    ['time' => '15:00–18:00', 'label' => 'Pomeriggio — SMM + contenuto', 'tasks' => [
                        ['text' => 'Invia altri 10 messaggi SMM. Conduci eventuali call rimaste.', 'tag' => 'smm'],
                        ['text' => 'Pubblica un post Instagram: "3 ristoranti su 10 non sanno quanto stanno pagando di commissioni ogni mese" — usa i dati reali dei tuoi clienti per rendere il post credibile.', 'tag' => 'content'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 26', 'theme' => 'Sales intensivo — chiudere il primo cliente', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–13:00', 'label' => 'Mattina — Sales block dedicato', 'tasks' => [
                        ['text' => 'Giornata quasi interamente dedicata alla vendita. Chiama tutti i lead caldi rimasti in pipeline da più di 3 giorni senza risposta.', 'tag' => 'sales'],
                        ['text' => 'Usa urgency reale: "L\'offerta con setup gratuito è valida fino al 30 giugno — dopo torno al prezzo standard". Non inventare scarsità finta.', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–16:00', 'label' => 'Pomeriggio — Primo cliente (target)', 'tasks' => [
                        ['text' => 'Obiettivo di questa sessione: attivare almeno 1 nuovo cliente pagante. Se hai un lead pronto, questa è la call di chiusura.', 'tag' => 'sales'],
                        ['text' => 'Dopo la chiusura: invia email di benvenuto personale (non automatica), offri onboarding call entro 24h, chiedi subito un referral.', 'tag' => 'sales'],
                    ]],
                    ['time' => '16:00–18:00', 'label' => 'Fine giornata', 'tasks' => [
                        ['text' => 'Outreach: 10 nuovi SMM. Aggiorna pipeline completa.', 'tag' => 'smm'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 27', 'theme' => 'Referral dai clienti attuali', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Attivazione referral', 'tasks' => [
                        ['text' => 'Chiama (non email) tutti e 10 i tuoi clienti attuali. Chiedi: "Sei soddisfatto del servizio? Conosci altri ristoratori che potrebbero beneficiarne?" Sii diretto.', 'tag' => 'sales'],
                        ['text' => 'Offri incentivo referral al cliente: "Se mi presenti un tuo collega che attiva FP, ti regalo 1 mese gratis." Semplice, chiaro, conveniente.', 'tag' => 'sales'],
                    ]],
                    ['time' => '15:00–18:00', 'label' => 'Pomeriggio — Outreach + review', 'tasks' => [
                        ['text' => 'Invia altri 10 messaggi SMM. Conduci eventuali call rimaste.', 'tag' => 'smm'],
                        ['text' => 'Review settimana 3: 1° cliente chiuso? Partnership SMM attive? Lead pipeline totale? Ads CPL? Scrivi i numeri.', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 28 (riposo)', 'theme' => 'Scale ads + outreach + preparazione settimana 4', 'hours' => '4h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Scale ads e contenuto', 'tasks' => [
                        ['text' => 'Se la Creative C lanciata giovedì mostra già dati positivi: aumenta il budget del 20%. Se no: pausa la creative peggiore e ridistribuisci il budget.', 'tag' => 'ads'],
                        ['text' => 'Programma i post della settimana 4 su Instagram e LinkedIn. Obiettivo: 1 post/giorno Instagram, 3 post/settimana LinkedIn personale.', 'tag' => 'content'],
                    ]],
                    ['time' => '11:00–13:00', 'label' => 'Mattina — Prep settimana 4', 'tasks' => [
                        ['text' => 'Identifica i 5 lead più caldi nella pipeline: quelli che lunedì o martedì prossimo chiamerai per primi. Prepara il "motivo di chiamata" per ognuno.', 'tag' => 'ops'],
                        ['text' => 'Aggiorna pipeline completa: sposta in "perso" chi non ha risposto dopo 2 follow-up. Tieni solo i contatti realmente attivi.', 'tag' => 'ops'],
                    ]],
                ],
            ],
        ],
    ],

    // ═══ MESE 2 ═══
    [
        'id' => 'w4', 'month' => 2, 'label' => 'Settimana 4', 'dates' => 'Giu 30–Lug 4',
        'color' => '#fbbf24',
        'subtitle' => 'Accelerazione — sistema che gira, scala ciò che funziona',
        'focus' => '⚡ Scalare Ciò che Funziona',
        'goals' => ['3+ nuovi clienti questa settimana', '3+ SMM partner attivi', 'Sistema outreach semi-automatizzato'],
        'days' => [
            [
                'name' => 'Mar 30', 'theme' => 'Lancio mese 2 — scala il canale vincente', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–10:00', 'label' => 'Apertura mese 2', 'tasks' => [
                        ['text' => 'Identifica il canale che ha portato più clienti nel mese 1 (SMM? Ads? Referral?) — oggi inizi a scalarlo sistematicamente.', 'tag' => 'ops'],
                        ['text' => 'Imposta obiettivi settimana: 3 nuovi clienti, 1 nuovo SMM partner, 20 nuovi lead in pipeline.', 'tag' => 'ops'],
                    ]],
                    ['time' => '10:00–13:00', 'label' => 'Mattina — Outreach scalato', 'tasks' => [
                        ['text' => 'Invia 20 messaggi SMM oggi (doppio rispetto alla settimana 1). Usa template personalizzato ma vai più veloce: hai già il sistema.', 'tag' => 'smm'],
                        ['text' => 'Contatta le associazioni di categoria: Fipe, Confcommercio locale, associazioni ristoratori regionali. Proponi una partnership istituzionale.', 'tag' => 'smm'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio — Sales e contenuto', 'tasks' => [
                        ['text' => 'Blocco call: 4 ore dedicate a demo e follow-up. Obiettivo 4 call oggi.', 'tag' => 'sales'],
                        ['text' => 'Tra una call e l\'altra: pubblica contenuto programmato, rispondi ai commenti.', 'tag' => 'content'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 1', 'theme' => 'Outreach massiccio + call block', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–09:30', 'label' => 'Apertura', 'tasks' => [
                        ['text' => 'Pipeline review: chi ha risposto nel weekend? Aggiorna stati. Controlla Meta Ads: nuovi lead ricevuti?', 'tag' => 'ops'],
                    ]],
                    ['time' => '09:30–13:00', 'label' => 'Mattina — Outreach SMM x20', 'tasks' => [
                        ['text' => 'Invia 20 messaggi SMM oggi — doppio rispetto alla settimana 1. Hai già il sistema rodato: vai più veloce senza sacrificare la personalizzazione minima (nomina un loro cliente ristorante).', 'tag' => 'smm'],
                        ['text' => 'Contatta anche 3-5 SMM che operano in regioni diverse dalla tua: allargare la geografia è il modo più veloce per scalare il canale SMM.', 'tag' => 'smm'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio — Call block 4 call', 'tasks' => [
                        ['text' => 'Blocca 4 ore per call. Obiettivo: 4 call oggi. Timer 20 minuti per call, 5 minuti di note subito dopo.', 'tag' => 'sales'],
                        ['text' => 'Priorizza i lead che vengono da referral o da SMM partner rispetto ai lead da ads: il tasso di chiusura è più alto.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 2', 'theme' => 'Demo intensive + contenuto', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–13:00', 'label' => 'Mattina — Demo block', 'tasks' => [
                        ['text' => 'Conduci le demo fissate. Mattina è il momento migliore per le demo — i ristoratori sono più disponibili prima del servizio pranzo.', 'tag' => 'sales'],
                        ['text' => 'Per ogni demo: usa la calcolatrice ROI live. "Quante prenotazioni a settimana? Quante via TheFork/JustEat? Moltiplica per €4/prenotazione = quanto spendete ora. FP costa €X/anno."', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–16:00', 'label' => 'Pomeriggio — Contenuto', 'tasks' => [
                        ['text' => 'Scrivi un caso studio breve (1 pagina) del tuo primo nuovo cliente del mese 2. Situazione prima, numeri dopo, quote diretta. Da pubblicare su LinkedIn e nel kit SMM.', 'tag' => 'content'],
                        ['text' => 'Crea 3 post Instagram per la settimana. Temi: 1) calcolatrice commissioni interattiva, 2) screenshot della piattaforma FP in uso, 3) numero di coperti gestiti in totale.', 'tag' => 'content'],
                    ]],
                    ['time' => '16:00–18:00', 'label' => 'Pomeriggio — Ads', 'tasks' => [
                        ['text' => 'Analisi ads settimana: quale audience porta il CPL più basso? Ottimizza: aumenta budget del 30% sull\'audience migliore, riduci o pausa quella peggiore.', 'tag' => 'ads'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 3', 'theme' => 'Chiusura settimanale + pipeline review', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–13:00', 'label' => 'Mattina — Sales chiusura', 'tasks' => [
                        ['text' => 'Chiama tutti i lead caldi rimasti aperti da lunedì. Fine settimana = ultima chance prima del weekend di fargli prendere una decisione.', 'tag' => 'sales'],
                        ['text' => 'Per chi è indeciso: offri un trial visivo di 30 minuti — condividi lo schermo e mostra la piattaforma come se fosse già il loro account.', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Follow-up + SMM', 'tasks' => [
                        ['text' => 'Invia follow-up email a tutti quelli con cui hai parlato questa settimana. Massimo 3 righe: riassunto call, prossimo step, link prenotazione.', 'tag' => 'sales'],
                        ['text' => 'Invia altri 10 messaggi SMM. Controlla anche i DM Instagram e LinkedIn — rispondi a tutto entro 2 ore.', 'tag' => 'smm'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Review settimanale', 'tasks' => [
                        ['text' => 'KPI settimana 4: nuovi clienti (target 3), SMM partner (target 3+), lead attivi in pipeline, CPL, CAC. Scrivi i numeri. Sei in linea con i target?', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 4', 'theme' => 'Batch lavoro + sistema', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Sistema', 'tasks' => [
                        ['text' => 'Documentazione: scrivi un "playbook di vendita" per te stesso. Cosa dici nelle prime 5 domande? Come gestisci le 3 obiezioni più comuni?', 'tag' => 'ops'],
                        ['text' => 'Crea template email per ogni fase del funnel: primo contatto, follow-up 24h, follow-up 72h, proposta formale, benvenuto post-chiusura.', 'tag' => 'ops'],
                    ]],
                    ['time' => '13:00–18:00', 'label' => 'Pomeriggio — Contenuto + outreach', 'tasks' => [
                        ['text' => 'Programma 10 post per le prossime 2 settimane. Mantieni il ritmo: 1 post/giorno su Instagram FP, 3/settimana su LinkedIn personale.', 'tag' => 'content'],
                        ['text' => 'Invia 15 messaggi SMM. Aggiorna pipeline totale.', 'tag' => 'smm'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 5 (riposo)', 'theme' => 'Batch contenuto + outreach weekend', 'hours' => '4h',
                'blocks' => [
                    ['time' => '10:00–12:00', 'label' => 'Mattina — Contenuto batch', 'tasks' => [
                        ['text' => 'Programma altri 5 post per la settimana 5 su Instagram e LinkedIn. Temi: testimonianze clienti, numeri aggiornati, confronto commissioni, come funziona il sistema WA.', 'tag' => 'content'],
                    ]],
                    ['time' => '14:00–16:00', 'label' => 'Pomeriggio — Outreach leggero', 'tasks' => [
                        ['text' => 'Rispondi a tutti i DM e LinkedIn rimasti in attesa. 10 nuovi messaggi SMM a chi non hai ancora contattato.', 'tag' => 'smm'],
                        ['text' => 'Aggiorna pipeline: sposta i lead "morti" (2+ follow-up senza risposta) in archivio. Tieni pulita la pipeline attiva.', 'tag' => 'ops'],
                    ]],
                ],
            ],
        ],
    ],

    // ═══ MESE 2 — SETTIMANA 5 ═══
    [
        'id' => 'w5', 'month' => 2, 'label' => 'Settimana 5', 'dates' => 'Lug 7–11',
        'color' => '#fbbf24',
        'subtitle' => 'Sistematizzare l\'acquisizione — meno sforzo, più risultati',
        'focus' => '🔧 Sistemi & Automazione',
        'goals' => ['15+ clienti totali', '5+ SMM partner attivi', 'Funnel email automatizzato attivo'],
        'days' => [
            [
                'name' => 'Mar 7', 'theme' => 'Full sales day', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–13:00', 'label' => 'Mattina — Sales block', 'tasks' => [
                        ['text' => 'Intera mattina dedicata a call e follow-up. Obiettivo: 5 call oggi. Usa timer 20 minuti per call, poi 5 min note, poi prossima.', 'tag' => 'sales'],
                        ['text' => 'Per ogni lead in pipeline da 5+ giorni senza risposta: prova un canale diverso (se hai scritto email, chiama. Se hai scritto DM, manda email).', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio', 'tasks' => [
                        ['text' => 'SMM outreach: 20 messaggi. Questa settimana punta su SMM con portfolio visibile di 5+ ristoranti — valgono di più come partner.', 'tag' => 'smm'],
                        ['text' => 'Pubblica contenuto: post educativo su come funziona il sistema WhatsApp di FP per il ristoratore (differenziatore chiave vs competitor).', 'tag' => 'content'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 8', 'theme' => 'Automazione funnel email', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–13:00', 'label' => 'Mattina — Setup automazione', 'tasks' => [
                        ['text' => 'Configura Mailchimp o Brevo (gratis fino a 2.000 contatti): crea la lista lead, importa i contatti ricevuti dagli ads.', 'tag' => 'ops'],
                        ['text' => 'Configura la sequenza email automatica in 3 step: benvenuto immediato, follow-up 24h con caso studio, follow-up 72h con offerta.', 'tag' => 'ops'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio', 'tasks' => [
                        ['text' => 'Call block: 4 ore. Conduci le demo fissate.', 'tag' => 'sales'],
                        ['text' => 'Ricerca e outreach 10 nuovi SMM.', 'tag' => 'smm'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 9', 'theme' => 'Call block + attivazione SMM partner', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–13:00', 'label' => 'Mattina — Call block 5 call', 'tasks' => [
                        ['text' => 'Obiettivo: 5 call oggi. Usa timer 20 minuti per call. Priorizza i lead che hanno già visto la landing o partecipato a una demo in settimana.', 'tag' => 'sales'],
                        ['text' => 'Per ogni lead che obietta sul prezzo: fai la calcolatrice live con loro. Il prezzo di FP si giustifica da solo se il ristorante ha 30+ prenotazioni/mese via portali.', 'tag' => 'sales'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio — Attivazione SMM partner', 'tasks' => [
                        ['text' => 'Contatta ogni SMM partner che hai già firmato: chiedi un aggiornamento. Quanti ristoranti stanno promuovendo FP? Hanno bisogno di materiali aggiuntivi?', 'tag' => 'smm'],
                        ['text' => 'Prepara un "kit aggiornato" per i partner SMM: nuovi dati, nuovo caso studio, nuove creative da condividere con i loro clienti ristoratori.', 'tag' => 'smm'],
                        ['text' => 'Invia 10 nuovi messaggi a SMM non ancora contattati.', 'tag' => 'smm'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 10', 'theme' => 'Analisi funnel email + chiusura aggressiva', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Analisi funnel', 'tasks' => [
                        ['text' => 'Analisi sequenza email automatica: open rate, click rate, conversione a call. Quale email funziona meglio? Quale viene ignorata? Ottimizza il soggetto e il CTA delle email con performance bassa.', 'tag' => 'ops'],
                        ['text' => 'Controlla anche i lead che hanno aperto le email ma non hanno ancora prenotato una call: mandagli un DM personalizzato su LinkedIn o Instagram.', 'tag' => 'sales'],
                    ]],
                    ['time' => '11:00–18:00', 'label' => 'Resto giornata — Sales', 'tasks' => [
                        ['text' => 'Chiusura aggressiva: chiama tutti i lead caldi rimasti aperti. Fine settimana = urgenza reale. "Ho ancora 2 slot nell\'offerta con setup gratuito — lo tengo per te fino a stasera."', 'tag' => 'sales'],
                        ['text' => 'Invia follow-up a tutti quelli con cui hai parlato questa settimana entro fine giornata.', 'tag' => 'sales'],
                        ['text' => 'Aggiorna pipeline completa prima del weekend.', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 11', 'theme' => 'Review mese 2 intermedia', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Review numeri', 'tasks' => [
                        ['text' => 'Conta: clienti totali, nuovi questa settimana, SMM partner attivi, lead totali in pipeline, CPL ads, CAC reale, ARR totale. Sei su track per 25–30 clienti a fine mese 2?', 'tag' => 'ops'],
                        ['text' => 'Identifica il tuo canale #1 e il canale #2 per acquisizione. Da oggi, il 70% del tempo va sul canale #1.', 'tag' => 'ops'],
                    ]],
                    ['time' => '14:00–18:00', 'label' => 'Pomeriggio', 'tasks' => [
                        ['text' => 'Call block + outreach SMM (15 nuovi messaggi).', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 12 (riposo)', 'theme' => 'Ottimizzazione ads + contenuto settimana 6', 'hours' => '4h',
                'blocks' => [
                    ['time' => '10:00–12:00', 'label' => 'Mattina — Ads', 'tasks' => [
                        ['text' => 'Ottimizza campagne Meta Ads sulla base dei dati della settimana: aumenta budget audience vincente del 20–30%, pausa l\'audience peggiore, valuta se testare una nuova fascia d\'età o geo.', 'tag' => 'ads'],
                    ]],
                    ['time' => '14:00–16:00', 'label' => 'Pomeriggio — Prep contenuto', 'tasks' => [
                        ['text' => 'Programma i contenuti della settimana 6: 5 post Instagram, 3 LinkedIn. Usa il caso studio nuovo e i numeri aggiornati dei clienti.', 'tag' => 'content'],
                    ]],
                ],
            ],
        ],
    ],

    // ═══ MESE 2 — SETTIMANA 6 ═══
    [
        'id' => 'w6', 'month' => 2, 'label' => 'Settimana 6', 'dates' => 'Lug 14–18',
        'color' => '#fbbf24',
        'subtitle' => 'Velocità e volume — chiudi il mese 2 forte',
        'focus' => '🚀 Volume & Velocità',
        'goals' => ['20+ clienti totali', 'Almeno 5 clienti nuovi questa settimana', '1 partnership istituzionale confermata'],
        'days' => [
            [
                'name' => 'Mar 14', 'theme' => 'Sales day totale', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–18:00', 'label' => 'Giornata intera — Solo vendita', 'tasks' => [
                        ['text' => 'Oggi niente outreach, niente contenuto, niente ops. Solo call e demo. Obiettivo: 6 call oggi.', 'tag' => 'sales'],
                        ['text' => 'Mattina: 3 call con lead caldi (quelli che hanno già fatto la demo e non hanno ancora deciso).', 'tag' => 'sales'],
                        ['text' => 'Pomeriggio: 3 call con lead nuovi (primo contatto o prima demo).', 'tag' => 'sales'],
                        ['text' => 'Fine giornata: invia follow-up email a tutti quelli con cui hai parlato oggi. Stessa sera, non il giorno dopo.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 15', 'theme' => 'Outreach + call + contenuto', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–09:30', 'label' => 'Apertura', 'tasks' => [
                        ['text' => 'Pipeline review. Controlla Meta Ads: nuovi lead dal weekend? Rispondi a tutti i messaggi ricevuti.', 'tag' => 'ops'],
                    ]],
                    ['time' => '09:30–13:00', 'label' => 'Mattina — Outreach SMM x20', 'tasks' => [
                        ['text' => 'Invia 20 messaggi a nuovi SMM. Questa settimana prova anche a contattare SMM tramite email diretta — spesso convertono meglio del DM freddo.', 'tag' => 'smm'],
                        ['text' => 'Follow-up agli SMM che non hanno risposto da 7+ giorni: usa un angolo completamente diverso, un nuovo hook.', 'tag' => 'smm'],
                    ]],
                    ['time' => '14:00–17:00', 'label' => 'Pomeriggio — Call + contenuto', 'tasks' => [
                        ['text' => 'Blocco call: 3 demo. Priorizza i lead che vengono da SMM partner — sono già "pre-riscaldati".', 'tag' => 'sales'],
                        ['text' => 'Pubblica contenuto pianificato. Rispondi ai commenti: ogni commento è un potenziale lead o una referral.', 'tag' => 'content'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Fine giornata', 'tasks' => [
                        ['text' => 'Aggiorna pipeline. Sei su track per 20+ clienti totali entro fine settimana?', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 16', 'theme' => 'Analisi ads + sales intensive', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Analisi campagne', 'tasks' => [
                        ['text' => 'Analisi completa delle campagne Meta Ads del mese 2: CPL per audience, CPL per creative, CPL per placement (feed vs stories vs reels). Scrivi il numero accanto a ogni variante.', 'tag' => 'ads'],
                        ['text' => 'Decisione: quali varianti scalare per l\'ultima settimana del mese? Porta budget totale a €1.000 se i dati lo giustificano (CPL < €30).', 'tag' => 'ads'],
                    ]],
                    ['time' => '11:00–18:00', 'label' => 'Resto giornata — Sales', 'tasks' => [
                        ['text' => 'Sales day intensivo: 5 call oggi. Niente outreach, niente contenuto — solo vendita.', 'tag' => 'sales'],
                        ['text' => 'Tra una call e l\'altra: manda follow-up email ai lead di ieri. Non lasciare passare più di 24 ore tra la demo e il follow-up.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 17', 'theme' => 'Chiusura aggressiva fine mese 2', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–18:00', 'label' => 'Giornata — Sales & Urgency', 'tasks' => [
                        ['text' => 'Fine mese si avvicina: usa scarsità reale. "Sto chiudendo il mese e voglio portare ancora X clienti entro il 31 luglio — per chi attiva entro questa settimana, aggiungo 2 mesi gratis."', 'tag' => 'sales'],
                        ['text' => 'Chiama tutti i lead che non hanno ancora deciso. Sii diretto: "Sei ancora interessato? Posso aiutarti a prendere una decisione oggi?"', 'tag' => 'sales'],
                        ['text' => 'SMM partner: chiedi loro di fare un push questa settimana sui loro clienti ristoratori. Dai loro un incentivo extra: "Se chiudi 2 ristoranti questo mese, raddoppio la referral fee."', 'tag' => 'smm'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 18', 'theme' => 'Review + preparazione mese 3', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Review mese 2', 'tasks' => [
                        ['text' => 'Conta tutto: clienti totali, nuovi nel mese 2, SMM partner attivi, lead totali generati, CPL, CAC, ARR totale. Sei a 20+?', 'tag' => 'ops'],
                        ['text' => 'Identifica le 3 azioni che hanno portato più risultati e le 3 che hanno portato meno. Il mese 3 raddoppia sulle prime, elimina le seconde.', 'tag' => 'ops'],
                    ]],
                    ['time' => '11:00–18:00', 'label' => 'Pomeriggio — Outreach + call', 'tasks' => [
                        ['text' => 'Continua con il ritmo. Non rallentare a fine mese.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 19 (riposo)', 'theme' => 'Review mese 2 + batch contenuto mese 3', 'hours' => '4h',
                'blocks' => [
                    ['time' => '10:00–12:00', 'label' => 'Mattina — Review mese 2', 'tasks' => [
                        ['text' => 'Conta tutto prima di entrare nel mese 3: clienti totali, nuovi nel mese 2, SMM partner attivi, ARR aggiunto, CPL medio, CAC medio. Sei a 20+?', 'tag' => 'ops'],
                        ['text' => 'Identifica le 3 azioni che hanno portato più risultati nel mese 2 e le 3 che hanno portato meno. Nel mese 3 raddoppia sulle prime, elimina le seconde.', 'tag' => 'ops'],
                    ]],
                    ['time' => '14:00–16:00', 'label' => 'Pomeriggio — Contenuto mese 3', 'tasks' => [
                        ['text' => 'Programma i primi 5 post del mese 3. Temi: webinar annuncio, numeri aggiornati clienti, recruiting commerciale, caso studio mese 2.', 'tag' => 'content'],
                    ]],
                ],
            ],
        ],
    ],

    // ═══ MESE 3 ═══
    [
        'id' => 'w7', 'month' => 3, 'label' => 'Settimana 7', 'dates' => 'Lug 21–25',
        'color' => '#34d399',
        'subtitle' => 'Mese 3 — sistema maturo, nuovi canali, primissimo team',
        'focus' => '🌱 Nuovi Canali & Team',
        'goals' => ['25+ clienti totali', 'Primo collaboratore identificato', 'Nuovo canale testato (webinar/evento)'],
        'days' => [
            [
                'name' => 'Mar 21', 'theme' => 'Lancio mese 3 — nuovo canale', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Nuovo canale: Webinar', 'tasks' => [
                        ['text' => 'Pianifica il primo webinar gratuito per ristoratori: "Come eliminare le commissioni di JustEat e TheFork — quello che nessuno ti dice." Data: fine mese 3.', 'tag' => 'content'],
                        ['text' => 'Crea la pagina di registrazione (Eventbrite gratuito o Google Form). Target: 50 iscritti ristoratori.', 'tag' => 'ops'],
                        ['text' => 'Promuovi il webinar a: lista email leads, tutti i clienti attuali, tutti gli SMM partner (loro lo promuovono ai loro clienti), post sui social.', 'tag' => 'content'],
                    ]],
                    ['time' => '11:00–18:00', 'label' => 'Resto giornata — Routine', 'tasks' => [
                        ['text' => 'Outreach SMM: 20 messaggi. Call block pomeriggio: 4 call.', 'tag' => 'smm'],
                        ['text' => 'Segui il ritmo stabilito nei mesi 1 e 2. Non reinventare, esegui.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 22', 'theme' => 'Ricerca primo collaboratore', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Collaboratore commerciale', 'tasks' => [
                        ['text' => 'Scrivi la job description per il primo collaboratore: "Commerciale a percentuale per startup SaaS ristorazione. No fisso, 15–20% su ogni cliente chiuso. Lavoro da remoto."', 'tag' => 'ops'],
                        ['text' => 'Posta su LinkedIn, Indeed, e nei gruppi Facebook di venditori/commerciali italiani.', 'tag' => 'ops'],
                        ['text' => 'Definisci la commission structure: es. €60–80 per ogni cliente Base attivato, €150 per Intermedio, €200 per Top.', 'tag' => 'ops'],
                    ]],
                    ['time' => '13:00–18:00', 'label' => 'Pomeriggio — Routine', 'tasks' => [
                        ['text' => 'Call block: 5 call. SMM outreach: 15 messaggi.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 23', 'theme' => 'Colloqui collaboratore + routine vendita', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Colloqui commerciali', 'tasks' => [
                        ['text' => 'Conduci i colloqui telefonici con i candidati commerciali che hanno risposto all\'annuncio. Domande chiave: hanno esperienza in vendita? Capiscono il SaaS? Sono motivati dalla percentuale?', 'tag' => 'ops'],
                        ['text' => 'Valuta ogni candidato su 3 criteri: capacità di ascolto, conoscenza del settore ristorativo, autonomia. Non cercare il perfetto — cerca chi può iniziare subito.', 'tag' => 'ops'],
                    ]],
                    ['time' => '13:00–18:00', 'label' => 'Pomeriggio — Routine', 'tasks' => [
                        ['text' => 'Call block: 4 call. SMM outreach: 15 messaggi. Promozione webinar su tutti i canali.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 24', 'theme' => 'Scelta collaboratore + onboarding iniziale', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Decisione e accordo', 'tasks' => [
                        ['text' => 'Scegli il candidato migliore e contattalo: "Sei selezionato. Vuoi iniziare la prossima settimana?" Sii diretto — i buoni candidati apprezzano la velocità.', 'tag' => 'ops'],
                        ['text' => 'Definisci l\'accordo scritto: struttura commissioni, materiali che fornisci, KPI di valutazione dopo 30 giorni (es. 3+ clienti chiusi).', 'tag' => 'ops'],
                        ['text' => 'Prepara il "kit di onboarding": playbook di vendita, deck demo, script delle obiezioni, accesso ai materiali.', 'tag' => 'ops'],
                    ]],
                    ['time' => '13:00–18:00', 'label' => 'Pomeriggio — Sales block', 'tasks' => [
                        ['text' => 'Call block: 4 call. Tu sei ancora il principale venditore — il collaboratore sarà operativo dalla prossima settimana.', 'tag' => 'sales'],
                        ['text' => 'Promuovi webinar: manda email alla lista lead + post sui social. Obiettivo: 50+ iscritti prima del webinar.', 'tag' => 'content'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 25', 'theme' => 'Formazione collaboratore + review settimana 7', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Sessione formazione', 'tasks' => [
                        ['text' => 'Sessione di formazione con il nuovo collaboratore (video call o in presenza): mostragli la demo completa, il playbook, come usare la pipeline, come gestire le obiezioni più comuni.', 'tag' => 'ops'],
                        ['text' => 'Fai fare al collaboratore una demo simulata con te come "cliente difficile". Dagli feedback specifici. Non mandarlo sulle call finché non è pronto.', 'tag' => 'ops'],
                    ]],
                    ['time' => '13:00–15:00', 'label' => 'Pomeriggio — Review settimana 7', 'tasks' => [
                        ['text' => 'KPI settimana 7: clienti totali (target 25+), SMM partner, ARR, iscritti webinar (target 50+), collaboratore onboarded. Scrivi i numeri.', 'tag' => 'ops'],
                    ]],
                    ['time' => '15:00–18:00', 'label' => 'Pomeriggio — Outreach + call', 'tasks' => [
                        ['text' => 'Call block: 3 call. SMM outreach: 10 messaggi. Promozione webinar finale.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 26 (riposo)', 'theme' => 'Promozione webinar + outreach partner', 'hours' => '4h',
                'blocks' => [
                    ['time' => '10:00–12:00', 'label' => 'Mattina — Push webinar', 'tasks' => [
                        ['text' => 'Lancia campagna ads specifica per il webinar: obiettivo iscrizioni, audience ristoratori. Budget €50–100 dedicato. Anche un post organico spinto sui social.', 'tag' => 'ads'],
                        ['text' => 'Chiedi a ogni SMM partner di promuovere il webinar ai loro clienti ristoratori: "Manda questo link ai tuoi clienti — è gratis per loro e potrebbe portarti una referral fee."', 'tag' => 'smm'],
                    ]],
                    ['time' => '14:00–16:00', 'label' => 'Pomeriggio — Outreach + pipeline', 'tasks' => [
                        ['text' => 'Aggiorna pipeline. 10 nuovi messaggi SMM. Prep mentale per la settimana 8 — sarà la settimana del webinar.', 'tag' => 'ops'],
                    ]],
                ],
            ],
        ],
    ],

    // ═══ MESE 3 — SETTIMANA 8 ═══
    [
        'id' => 'w8', 'month' => 3, 'label' => 'Settimana 8', 'dates' => 'Lug 28–Ago 1',
        'color' => '#34d399',
        'subtitle' => 'Webinar + primo collaboratore attivo',
        'focus' => '🎤 Webinar & Primo Team',
        'goals' => ['30+ clienti totali', 'Primo collaboratore avviato', 'Webinar: 50+ iscritti'],
        'days' => [
            [
                'name' => 'Mar 28', 'theme' => 'Promozione webinar intensa + routine vendita', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Push promozione webinar', 'tasks' => [
                        ['text' => 'Invia email alla lista lead completa: "Ti aspettiamo al webinar gratuito giovedì — imparerai come eliminare le commissioni di JustEat e TheFork." Includi data, ora, link di iscrizione.', 'tag' => 'content'],
                        ['text' => 'Pubblica post di promozione webinar su Instagram FP e LinkedIn personale. Conta gli iscritti: sei a 50+? Se no, fai push più aggressivo.', 'tag' => 'content'],
                    ]],
                    ['time' => '11:00–18:00', 'label' => 'Resto giornata — Routine vendita', 'tasks' => [
                        ['text' => 'Call block: 4 call. SMM outreach: 15 messaggi. Il collaboratore gestisce autonomamente il suo blocco di call — tu supervisioni.', 'tag' => 'sales'],
                        ['text' => 'Check-in con il collaboratore: come sta andando? Quante call ha fatto? Quali obiezioni ha incontrato? Dagli feedback specifico.', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 29', 'theme' => 'Preparazione finale webinar + call block', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Prep webinar', 'tasks' => [
                        ['text' => 'Ripassa le slide del webinar. Test tecnologico completo: Zoom/Meet, audio, video, screen sharing, chat, sondaggi. Non lasciare niente al caso — i problemi tecnici distruggono la credibilità.', 'tag' => 'ops'],
                        ['text' => 'Prepara l\'offerta esclusiva del webinar: es. "Setup gratuito + 2 mesi inclusi" valida solo per 48h dopo il webinar. Decidila ora, non durante.', 'tag' => 'ops'],
                        ['text' => 'Invia email di promemoria agli iscritti: "Ti aspettiamo domani alle [ora]. Ecco cosa imparerai..." — aumenta il tasso di partecipazione del 20–30%.', 'tag' => 'content'],
                    ]],
                    ['time' => '13:00–18:00', 'label' => 'Pomeriggio — Call block', 'tasks' => [
                        ['text' => 'Ultimi call prima del webinar: 3 call. Invita anche i lead più caldi al webinar come warm-up prima della proposta.', 'tag' => 'sales'],
                        ['text' => 'Aggiorna pipeline. Conta gli iscritti al webinar: target minimo 30 per avere una sessione produttiva.', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 30', 'theme' => 'Webinar day', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–17:00', 'label' => 'Preparazione e webinar', 'tasks' => [
                        ['text' => 'Mattina: ripassa le slide, prepara le risposte alle domande più probabili, testa nuovamente la tecnologia.', 'tag' => 'ops'],
                        ['text' => 'Pomeriggio: CONDUCI IL WEBINAR. Registra sempre — il recording è contenuto riutilizzabile per mesi.', 'tag' => 'content'],
                        ['text' => 'Durante il webinar: offerta esclusiva per chi si iscrive entro 48 ore (es. setup gratuito + 1 mese incluso).', 'tag' => 'sales'],
                    ]],
                    ['time' => '17:00–18:00', 'label' => 'Post-webinar', 'tasks' => [
                        ['text' => 'Invia email follow-up entro 2 ore dalla fine: ringraziamento + link recording + offerta con scadenza 48h + link per prenotare call con te.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 31', 'theme' => 'Post-webinar: chiudi i lead caldi', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–18:00', 'label' => 'Giornata intera — Post-webinar sales', 'tasks' => [
                        ['text' => 'Chiama (non email!) ogni persona che ha partecipato al webinar e non si è ancora iscritta. Sono i lead più caldi che hai mai avuto.', 'tag' => 'sales'],
                        ['text' => 'Offerta scadenza: "L\'offerta speciale del webinar scade oggi alle 18:00." Mantieni la scadenza — la credibilità è fondamentale.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 1', 'theme' => 'Onboarding nuovi clienti webinar + pipeline', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Onboarding', 'tasks' => [
                        ['text' => 'Onboarding call con i nuovi clienti acquisiti dal webinar: presentati, mostra la piattaforma, imposta i primi automessaggi WA. Falli sentire seguiti — il churn avviene nei primi 30 giorni.', 'tag' => 'ops'],
                        ['text' => 'Per chi non ha ancora deciso dopo il webinar: manda l\'ultima email con recording + offerta che scade stasera a mezzanotte.', 'tag' => 'sales'],
                    ]],
                    ['time' => '13:00–16:00', 'label' => 'Pomeriggio — Outreach + collaboratore', 'tasks' => [
                        ['text' => 'SMM outreach: 10 messaggi. Check-in con il collaboratore sui risultati della settimana.', 'tag' => 'smm'],
                        ['text' => 'Pubblica il recording del webinar su YouTube (unlisted) e invialo via email a chi si era iscritto ma non ha partecipato — sono lead semi-caldi.', 'tag' => 'content'],
                    ]],
                    ['time' => '16:00–18:00', 'label' => 'Review settimana 8', 'tasks' => [
                        ['text' => 'KPI: clienti totali (target 30+), iscritti webinar, conversioni webinar, collaboratore attivo, ARR totale. Sei in linea?', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 2 (riposo)', 'theme' => 'Scala ads post-webinar + contenuto', 'hours' => '4h',
                'blocks' => [
                    ['time' => '10:00–12:00', 'label' => 'Mattina — Ads webinar recording', 'tasks' => [
                        ['text' => 'Lancia una nuova campagna ads con il recording del webinar come "lead magnet": chi guarda il video gratuito → segue la sequenza email → call. Questo è il funnel più potente che hai ora.', 'tag' => 'ads'],
                        ['text' => 'Scala le campagne esistenti che stanno ancora performando bene. Budget totale mese 3 può salire a €1.500 se il CAC è positivo.', 'tag' => 'ads'],
                    ]],
                    ['time' => '14:00–16:00', 'label' => 'Pomeriggio — Pipeline e contenuto', 'tasks' => [
                        ['text' => 'Aggiorna pipeline completa. Prepara i contenuti dei prossimi 3 giorni. Settimana 9 è la chiusura del trimestre — massima concentrazione.', 'tag' => 'ops'],
                    ]],
                ],
            ],
        ],
    ],

    // ═══ MESE 3 — SETTIMANA 9 ═══
    [
        'id' => 'w9', 'month' => 3, 'label' => 'Settimana 9', 'dates' => 'Ago 4–8',
        'color' => '#34d399',
        'subtitle' => 'Consolidamento finale — chiudi il trimestre forte',
        'focus' => '🏁 Chiusura Trimestre',
        'goals' => ['35+ clienti totali', 'Collaboratore autonomo su call', 'ARR: €17k+'],
        'days' => [
            [
                'name' => 'Mar 4', 'theme' => 'Collaboratore autonomo', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Delega', 'tasks' => [
                        ['text' => 'Da oggi il collaboratore gestisce autonomamente il suo blocco di call (con supervisione). Tu focalizzi il 70% del tuo tempo sulla chiusura dei lead più grandi e complessi.', 'tag' => 'ops'],
                        ['text' => 'Imposta check-in giornaliero con il collaboratore: 15 min al mattino (priorità del giorno) e 15 min a sera (risultati e blocchi).', 'tag' => 'ops'],
                    ]],
                    ['time' => '11:00–18:00', 'label' => 'Resto giornata', 'tasks' => [
                        ['text' => 'Il tuo blocco di call: concentrati sui lead che valgono di più (pacchetto Top €1.200 o clienti con molte prenotazioni).', 'tag' => 'sales'],
                        ['text' => 'Outreach SMM: ora fa una parte anche il collaboratore. Tu supervisioni e dai feedback.', 'tag' => 'smm'],
                    ]],
                ],
            ],
            [
                'name' => 'Mer 5', 'theme' => 'Supervisione collaboratore + nuovi lead', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–10:00', 'label' => 'Mattina — Check-in collaboratore', 'tasks' => [
                        ['text' => 'Check-in 15 min mattina con il collaboratore: priorità del giorno, lead caldi su cui lavorare, eventuali blocchi. Lui gestisce autonomamente le sue call — tu supervisioni solo su richiesta.', 'tag' => 'ops'],
                        ['text' => 'Rivedi le note delle call del collaboratore: sta usando bene il playbook? Ha bisogno di feedback su qualche obiezione specifica?', 'tag' => 'ops'],
                    ]],
                    ['time' => '10:00–18:00', 'label' => 'Resto giornata — Sales + outreach', 'tasks' => [
                        ['text' => 'Il tuo call block: concentrati sui lead più grandi (pacchetto Top €1.200) o sui ristoranti con molte prenotazioni — quelli che valgono di più e che il collaboratore non chiuderebbe da solo.', 'tag' => 'sales'],
                        ['text' => 'Outreach SMM: 15 messaggi. Chiedi anche ai tuoi clienti più soddisfatti di presentarti ad altri ristoratori — il referral è ancora il canale con CAC più basso.', 'tag' => 'smm'],
                        ['text' => 'Aggiorna pipeline e conta: siete in due ora. I numeri devono essere più che raddoppiati rispetto a settimana 1.', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Gio 6', 'theme' => 'Upsell clienti esistenti + chiusura lead aperti', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Upsell', 'tasks' => [
                        ['text' => 'Identifica i clienti sul pacchetto Base o Intermedio con più prenotazioni mensili — sono i candidati ideali per l\'upgrade al pacchetto Top con CRM.', 'tag' => 'sales'],
                        ['text' => 'Chiama 3–5 clienti con la proposta di upgrade: "Ho visto che hai X prenotazioni al mese — con il CRM potresti fare campagne mirate e far tornare i clienti abituali."', 'tag' => 'sales'],
                    ]],
                    ['time' => '13:00–18:00', 'label' => 'Pomeriggio — Chiusura lead aperti', 'tasks' => [
                        ['text' => 'Chiudi tutti i lead ancora aperti in pipeline: chiama ognuno, capisci dove sono bloccati, risolvi l\'obiezione finale. Hai solo 2 giorni prima della review finale trimestrale.', 'tag' => 'sales'],
                        ['text' => 'Check-in serale con il collaboratore: quanti clienti ha chiuso questa settimana? Sta raggiungendo i KPI (target: 3 clienti chiusi nei primi 30 giorni)?', 'tag' => 'ops'],
                    ]],
                ],
            ],
            [
                'name' => 'Ven 7', 'theme' => 'Upsell clienti esistenti', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Upsell', 'tasks' => [
                        ['text' => 'Identifica i clienti sul pacchetto Base o Intermedio che potrebbero beneficiare del Top (CRM). Guarda chi ha più prenotazioni — sono i candidati ideali.', 'tag' => 'sales'],
                        ['text' => 'Chiama 5 clienti con proposta di upgrade: "Ho visto che hai X prenotazioni al mese — con il pacchetto Top potresti fare campagne mirate a questi clienti e riaverli."', 'tag' => 'sales'],
                    ]],
                    ['time' => '13:00–18:00', 'label' => 'Pomeriggio', 'tasks' => [
                        ['text' => 'Call block nuovi lead. Supervisione collaboratore.', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Sab 8', 'theme' => 'Review pre-finale trimestre', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–11:00', 'label' => 'Mattina — Review', 'tasks' => [
                        ['text' => 'Conta tutto: clienti totali (target 35+), ARR (target €17k+), CAC per canale, SMM partner attivi, webinar completati, collaboratore attivo.', 'tag' => 'ops'],
                        ['text' => 'Identifica i 5 lead più caldi rimasti in pipeline: questa settimana li chiudi tutti.', 'tag' => 'ops'],
                    ]],
                    ['time' => '11:00–18:00', 'label' => 'Resto giornata', 'tasks' => [
                        ['text' => 'Chiusura aggressiva finale: chiama tutti i lead ancora aperti. Usa urgenza reale: "Sto chiudendo il trimestre, ho ancora 2 slot nell\'offerta attuale."', 'tag' => 'sales'],
                    ]],
                ],
            ],
            [
                'name' => 'Dom 9 (riposo)', 'theme' => 'CHIUSURA TRIMESTRE', 'hours' => '8h',
                'blocks' => [
                    ['time' => '09:00–12:00', 'label' => 'Mattina — Review finale', 'tasks' => [
                        ['text' => 'Review completa 90 giorni: clienti (target 35–40), ARR aggiunto (target €15k+), SMM partner (target 5+), CAC medio, canale #1, canale #2, primo collaboratore attivo.', 'tag' => 'ops'],
                        ['text' => 'Scrivi un documento "Lezioni dei primi 90 giorni": cosa hai imparato, cosa rifai uguale, cosa cambia nel prossimo trimestre.', 'tag' => 'ops'],
                        ['text' => 'Celebra: hai costruito un sistema di acquisizione da 0, con €1.000 di budget ads, da solo. Questo è il fondamento per i prossimi 10.000 clienti.', 'tag' => 'ops'],
                    ]],
                    ['time' => '13:00–18:00', 'label' => 'Pomeriggio — Piano Q4', 'tasks' => [
                        ['text' => 'Pianifica il prossimo trimestre: nuovo budget ads (da reinvestire dai ricavi), secondo collaboratore, secondo webinar, espansione a nuove regioni, prima fiera di settore.', 'tag' => 'ops'],
                        ['text' => 'Aggiorna il playbook con tutto ciò che hai imparato. È il documento più importante che hai.', 'tag' => 'ops'],
                    ]],
                ],
            ],
        ],
    ],
];

# Politiek kompas — methodiekvoorstel

Huidig: PoliticalCompassService vraagt een LLM om score, ideologisch label en één aanbevolen partij. Minimaal tien stemmen, maximaal 25 in prompt, maximaal eenmaal per dag. Gebruikers-ID/politieke voorkeur en stemgedrag gaan naar modelprovider. Dit voldoet niet aan V2-determinisme en wordt niet als wetenschappelijk gevalideerd rapport verkocht.

Doel M5: menselijke review van statement -> dimensie/polariteit/gewicht/provenance. Versieer een beperkte set expliciete dimensies; geen waarden of mapping verzinnen. Voor een dimensie d: score = 50 * (1 + sum(weight * polarity * choice)/sum(abs(weight))), choice -1/+1; historische neutral telt niet als richting en vermindert coverage. Geen score bij onvoldoende dekking. Minimum aantal geldige actuele stemmen én minimum per dimensie; thresholds valideren op redactionele dataset. Vermeld dekking, bronleeftijd en gevoeligheidsinterval, geen ongefundeerd statistisch confidence interval.

Partijmatch uitsluitend voor officieel traceerbare stemmen op exact gekoppelde moties; gewogen overeenkomst = overeenkomend gewicht / vergelijkbaar gewicht. Ontbrekende/afwezige/onthouden partijstemmen uitsluiten en coverage tonen; geen LLM-invulling. Rank met minimumcoverage en deterministische tie-break. Geen partijvoorkeur of zelfopgegeven voorkeur als invoer voor de score. Archetypen zijn uitlegbare presentatie, geen psychologische diagnose.

Rapport: berekeningsversie, gebruikte statement-ID's, aantal, coverage/limieten, datum, waarden/dimensies, matches en uitleg. Politieke resultaten versleutelen; optionele LLM-proza apart en alleen na privacybeoordeling. A4/PDF en mobiele weergave gebruiken dezelfde berekende bron. Het lifetime-voorstel is op 8 november vervangen door €1,99 per afzonderlijke snapshot; eerder gekochte rapporten blijven toegankelijk.

## Geïmplementeerde eerste rapportversie

`VoteSnapshot` / `vote-overview-v1` levert nu een deterministisch stemoverzicht over alle geldige eigen stemmen: tellingen, eigen antwoordvoorbeelden en optionele partijovereenkomsten vanaf tien verschillende gecontroleerd gekoppelde moties per partij. Meerdere stellingen bij dezelfde motie tellen niet dubbel. Ontbrekende en ambigue keuzes worden uitgesloten. De voor/tegenverdeling levert uitdrukkelijk geen links/rechtsscore of persoonlijkheidsarchetype op.

Snapshot, narratief en PDF zijn versleuteld opgeslagen bij een afzonderlijke betaalorder. `ReportWriter` gebruikt OpenAI Luna of DeepSeek uitsluitend voor optionele korte proza, nooit voor cijfers of partijmatches. De oude gratis POST voor LLM-bepaalde scores is gesloten; bestaande legacy-analyses zijn behouden. Dimensiemapping, thematische scores en archetypen uit het uitgebreidere V2-plan blijven onuitgewerkt en worden niet gesimuleerd. Details en beperkingen in REPORTS.md.

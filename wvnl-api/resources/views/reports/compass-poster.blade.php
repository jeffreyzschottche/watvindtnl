@php
$c=$snapshot['compass']; $type=$c['type']; $color=$type['color']??'#008f87';
$art=static fn($name)=>'data:image/svg+xml;base64,'.base64_encode(file_get_contents(resource_path('views/reports/art/'.(preg_match('/^[a-z0-9-]+$/D',$name)?$name:'leaf').'.svg')));
$logo='data:image/png;base64,'.base64_encode(file_get_contents(resource_path('views/reports/art/wdnl-logo.png')));
$wheelName=isset(\App\Services\Reports\CompassTypes::ALL[$type['id']??''])?$type['id']:'pending';
$wheel='data:image/png;base64,'.base64_encode(file_get_contents(resource_path('views/reports/art/wheel-v4-'.$wheelName.'.png')));
$date=\Illuminate\Support\Carbon::parse($snapshot['generated_at'])->format('d-m-Y');
$notes=$narrative['highlights']??[];
@endphp
<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>Jouw Politieke Kompas — WatDenktNederland</title><style>
@page {
  margin: 22pt 23pt 24pt;
}
body {
  font-family: "DejaVu Sans",sans-serif;
  font-size: 8pt;
  color: #001f4d;
  line-height: 1.35;
  margin: 0;
}
h1 {
  margin: 0;
  font-size: 23pt;
  letter-spacing: -.8pt;
  line-height: 1.12;
}
h2 {
  margin: 0;
  font-size: 23pt;
  letter-spacing: -.7pt;
  line-height: 1.12;
}
h3 {
  margin: 0;
  font-size: 11pt;
  line-height: 1.2;
  letter-spacing: -.35pt;
}
p {
  margin: 0;
}
table {
  width: 100%;
  border-collapse: collapse;
}
td {
  vertical-align: top;
}
.header {
  margin-bottom: 15pt;
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  margin: 0;
}
.header td {
  vertical-align: middle;
}
.header-logo {
  width: 40%;
  padding-right: 14pt;
  border-right: 1pt solid #cdd8e3;
}
.header-logo img {
  width: 100%;
  height: auto;
}
.header-title {
  padding-left: 15pt;
}
.header-title p {
  font-size: 8.5pt;
  margin-top: 5pt;
}
.accent {
  height: 3pt;
  width: 32pt;
  background: #e1062e;
  border-radius: 2pt;
  margin: 7pt 0;
}
.hero {
  background: #e9f6f4;
  border: 1pt solid #dcecea;
  border-radius: 13pt;
  padding: 10pt 12pt;
  margin-bottom: 12pt;
  padding-top: 9pt;
  padding-bottom: 9pt;
  position: absolute;
  top: 65pt;
  left: 0;
  right: 0;
  margin: 0;
}
.hero-icon {
  width: 13%;
  vertical-align: middle;
  padding-right: 8pt;
}
.hero-icon img {
  width: 30pt;
  height: 30pt;
}
.hero-copy {
  width: 58%;
  padding-right: 10pt;
  vertical-align: middle;
}
.hero-copy h2 {
  font-size: 17pt;
  margin-bottom: 8pt;
  white-space: nowrap;
}
.hero-copy p {
  font-size: 7.7pt;
  line-height: 1.35;
}
.traits {
  border-left: 1pt solid #9dc9c7;
  padding-left: 10pt;
  vertical-align: middle;
  width: 29%;
}
.trait {
  margin: 3pt 0;
  font-size: 6.6pt;
  line-height: 1.4;
}
.trait img {
  width: 12pt;
  height: 12pt;
  vertical-align: middle;
  margin-right: 5pt;
}
.columns>tbody>tr>td {
  width: 50%;
}
.col-left {
  padding-right: 6pt;
}
.col-right {
  padding-left: 6pt;
}
.card {
  border: 1pt solid #dce3ec;
  border-radius: 11pt;
  padding: 10pt;
  margin-bottom: 9pt;
  position: absolute;
  margin: 0;
}
.wheel {
  width: 100%;
  height: 225pt;
  margin-top: -2pt;
  margin: 0;
}
.caption {
  font-size: 6.2pt;
  color: #53697d;
  line-height: 1.45;
}
.spectrum {
  margin: 25pt 5pt 5pt;
  position: relative;
  height: 12pt;
  margin-top: 18pt;
  margin-bottom: 3pt;
}
.spectrum img {
  width: 100%;
  height: 12pt;
}
.axis-labels {
  font-size: 7.5pt;
  font-weight: bold;
  margin: 2pt 0;
  line-height: 1.1;
}
.right {
  text-align: right;
}
.score {
  margin-top: 6pt;
}
.theme-icon {
  width: 24pt;
  vertical-align: middle;
}
.theme-icon img {
  width: 18pt;
  height: 18pt;
}
.labels {
  font-size: 7pt;
  line-height: 1.1;
}
.track {
  height: 5pt;
  background: #dce4eb;
  border-radius: 5pt;
  margin: 3pt 0 1pt;
}
.figures {
  font-size: 7pt;
  font-weight: bold;
  line-height: 1;
}
.score-meta {
  font-size: 5.5pt;
  color: #53697d;
  margin-top: 1pt;
  line-height: 1.1;
}
.party {
  padding: 1pt 0;
  border-bottom: 1pt solid #e8edf3;
}
.party:last-child {
  border: 0;
}
.party-name {
  font-size: 7.2pt;
  font-weight: bold;
  line-height: 1.2;
}
.party-score {
  font-weight: bold;
  font-size: 16pt;
  width: 43pt;
  text-align: right;
  vertical-align: middle;
}
.party .track {
  margin-right: 8pt;
  height: 4pt;
  margin-top: 2pt;
  margin-bottom: 1pt;
}
.party small {
  font-size: 6pt;
  color: #53697d;
  line-height: 1.2;
}
.highlight {
  margin: 2.6pt 0;
  font-size: 7.3pt;
  line-height: 1.28;
}
.method {
  background: #f0f7f7;
  border-color: #d8e9e8;
  padding: 10pt;
  left: 279pt;
  top: 666pt;
  width: 247pt;
  height: 63pt;
}
.method h3 {
  font-size: 10pt;
  margin-bottom: 6pt;
}
.method p {
  font-size: 6pt;
  color: #425c72;
  line-height: 1.3;
}
.footer {
  border-top: 1pt solid #d2dce8;
  padding-top: 8pt;
  font-size: 6.6pt;
  color: #53697d;
  margin-top: 1pt;
  position: absolute;
  top: 759pt;
  left: 0;
  width: 100%;
  margin: 0;
}
.footer td {
  vertical-align: middle;
}
.footer strong {
  color: #001f4d;
}
.dev {
  font-size: 6pt;
  color: #6c4f13;
  text-align: right;
  margin-bottom: 5pt;
  position: absolute;
  top: -13pt;
  right: 0;
}
.corner {
  position: fixed;
  right: -23pt;
  top: -22pt;
  width: 65pt;
  height: 31pt;
  z-index: -1;
}
.corner-bottom {
  top: auto;
  bottom: -24pt;
}
.empty {
  font-size: 8pt;
  line-height: 1.6;
  color: #53697d;
}
.evidence-count {
  font-size: 6pt;
  color: #53697d;
  margin-top: 6pt;
}
.dense .header {
  margin-bottom: 10pt;
  margin: 0;
}
.dense .hero {
  padding: 8pt 12pt;
  margin-bottom: 9pt;
  margin: 0;
}
.dense .hero-copy h2 {
  font-size: 16pt;
  white-space: nowrap;
}
.dense .hero-copy p {
  font-size: 7.2pt;
  line-height: 1.35;
}
.dense .card {
  padding: 10pt;
  margin-bottom: 8pt;
  margin: 0;
}
.dense .score {
  margin-top: 5pt;
}
.dense .party {
  padding: 1pt 0;
}
.dense .highlight {
  font-size: 7pt;
  margin: 2pt 0;
  line-height: 1.25;
}
.dense .method p {
  font-size: 6.2pt;
  line-height: 1.3;
}
.dense .wheel {
  width: 100%;
  margin-left: 0;
  height: 225pt;
  margin: 0;
}
.dense h3 {
  font-size: 11pt;
}
.fill {
  height: 5pt;
  background: {{ $color }};
}
/* Bandbreedte: a thin bracket under the bar instead of tinted bar segments. */
.range {
  position: relative;
  height: 4pt;
  margin-top: 1pt;
}
.range-mark {
  position: absolute;
  top: 0;
  height: 2.5pt;
  border: 0.9pt solid {{ $color }};
  border-top: 0;
  opacity: 0.55;
}
.hero table {
  table-layout: fixed;
}
.hero-icon div {
  padding: 10pt;
  background: {{ $color }};
}
.pill {
  font-size: 6.5pt;
  padding: 4pt 5pt;
  background: {{ $color }};
}
.columns {
  table-layout: fixed;
}
.dense .trait {
  font-size: 6.5pt;
  margin: 3pt 0;
}
.dense .accent {
  margin: 6pt 0;
}
.score-meta--known {
  display: none;
}
.paper {
  position: relative;
  height: 784pt;
}
.personality {
  left: 0;
  top: 186pt;
  width: 245pt;
  height: 274pt;
}
.axis-card {
  left: 279pt;
  top: 186pt;
  width: 247pt;
  height: 78pt;
}
.themes-card {
  left: 279pt;
  top: 296pt;
  width: 247pt;
  height: 166pt;
}
.parties-card {
  left: 0;
  top: 492pt;
  width: 245pt;
  height: 225pt;
}
.traits-card {
  left: 279pt;
  top: 492pt;
  width: 247pt;
  height: 148pt;
}
.wheel-caption {
  margin-top: 0;
  color: {{ $color }};
}
.party .fill {
  height: 4pt;
}
.dense .personality {
  height: 274pt;
}
.dense .axis-card {
  height: 78pt;
}
.dense .themes-card {
  height: 166pt;
  top: 296pt;
}
.dense .parties-card {
  height: 225pt;
}
.dense .traits-card {
  height: 148pt;
}
.dense .method {
  height: 63pt;
}
.you {
  background: {{ $color }};
}
.marker {
  background: {{ $color }};
}
.tick {
  color: {{ $color }};
}
.empty-stat {
  color: {{ $color }};
}
.hero-icon div{border-radius:12pt}.pill{background:{{ $color }};color:#fff;text-align:center;padding:4pt 5pt;border-radius:13pt;font-size:6.5pt;font-weight:bold;margin-bottom:7pt}.you{position:absolute;top:-23pt;margin-left:-12pt;background:{{ $color }};color:white;padding:3pt 6pt;border-radius:9pt;font-size:7pt;font-weight:bold}.marker{position:absolute;top:0;margin-left:-6pt;border:2pt solid white;width:10pt;height:10pt;border-radius:10pt;background:{{ $color }}}.fill{border-radius:5pt}.wheel-caption{font-size:6.2pt;color:{{ $color }};text-align:center;margin-top:0}.tick{font-weight:bold}.method,.dense .method{height:68pt}</style></head><body class="{{ !empty($dense)?'dense':'' }}"><div class="paper">
<img class="corner" src="{{ $art('flag') }}" alt=""><img class="corner corner-bottom" src="{{ $art('flag') }}" alt="">
@if(!empty($snapshot['demo']))<p class="dev">VOORBEELD · FICTIEVE ANTWOORDEN</p>@elseif(!empty($snapshot['development']))<p class="dev">DEV-RAPPORT · GEEN AANKOOP</p>@endif
<table class="header"><tr><td class="header-logo"><img src="{{ $logo }}" alt="WatDenktNederland"></td><td class="header-title"><h1>Jouw Politieke Kompas</h1><p>Persoonlijk rapport op basis van jouw stemgedrag</p><div class="accent" style="margin-bottom:0"></div></td></tr></table>
<div class="hero"><table><tr><td class="hero-icon"><div><img src="{{ $art(($type['icon']??'balance').'-white') }}" alt=""></div></td><td class="hero-copy"><h2>{{ $type['name']??'Jouw kompas in opbouw' }}</h2><p>{{ $narrative['summary'] }}</p></td><td class="traits"><div class="pill">{{ ($type['provisional']??true)?'Voorlopig type':'Jouw politieke type' }}</div>@foreach($type['traits']??[$snapshot['total'].' eigen antwoorden',$c['used_votes'].' gewogen stellingen','Jouw persoonlijke richting'] as $trait)<div class="trait"><img src="{{ $art($type['icon']??'balance') }}" alt="">{{ $trait }}</div>@endforeach</td></tr></table></div>
<section class="card personality"><h3>Jouw politieke persoonlijkheid</h3><div class="accent"></div><img class="wheel" src="{{ $wheel }}" alt="Zestien politieke typen"><p class="wheel-caption">{{ $type['name']??'Meer gewogen antwoorden nodig voor een type' }}{{ ($type['provisional']??false)?' · voorlopige indicatie':'' }}</p></section>
<section class="card axis-card"><h3>Jouw positie op het politieke spectrum</h3><div class="accent"></div>@if($c['spectrum']['percentage']!==null)<div class="spectrum"><img src="{{ $art('spectrum') }}" alt=""><span class="you" style="left:{{ 100-$c['spectrum']['percentage'] }}%">Jij</span><span class="marker" style="left:{{ 100-$c['spectrum']['percentage'] }}%"></span></div><table class="axis-labels"><tr><td>Links {{ $c['spectrum']['percentage'] }}%</td><td class="right">{{ $c['spectrum']['opposite_percentage'] }}% Rechts</td></tr></table><p class="caption">Economisch beleid · {{ $c['spectrum']['compared'] }} antwoorden{{ $c['spectrum']['provisional']?' · voorlopig':'' }}</p>@else<p class="empty">Nog geen economische aspositie · {{ $c['spectrum']['compared'] }} gewogen antwoorden.</p>@endif</section>
<section class="card themes-card"><h3>Jouw scores op belangrijke thema’s</h3><div class="accent"></div>@foreach($c['dimensions'] as $d)<div class="score"><table><tr><td class="theme-icon"><img src="{{ $art($d['icon']) }}" alt=""></td><td><table class="labels"><tr><td>{{ $d['left'] }}</td><td class="right">{{ $d['right'] }}</td></tr></table>@php($lo=$d['sensitivity']?min($d['sensitivity']['min'],$d['percentage']):null)@php($hi=$d['sensitivity']?max($d['sensitivity']['max'],$d['percentage']):null)<div class="track">@if($d['percentage']!==null)<div class="fill" style="width:{{ $d['percentage'] }}%"></div>@endif</div>@if($d['percentage']!==null && $lo!==null && $hi>$lo)<div class="range"><div class="range-mark" style="left:{{ $lo }}%;width:{{ $hi-$lo }}%"></div></div>@endif @if($d['percentage']!==null)<table class="figures"><tr><td>{{ $d['percentage'] }}%</td><td class="right">{{ $d['opposite_percentage'] }}%</td></tr></table><p class="score-meta score-meta--known">{{ $d['compared'] }} antwoorden{{ $lo!==null && $hi>$lo ? ' · bandbreedte '.$lo.'–'.$hi.'%' : '' }}{{ $d['provisional']?' · voorlopig':'' }}</p>@else<p class="score-meta">Nog geen score · {{ $d['compared'] }}/{{ $d['early_minimum']??2 }} antwoorden</p>@endif</td></tr></table></div>@endforeach</section>
<section class="card parties-card"><h3>Partijen die bij je passen</h3><div class="accent"></div><p class="caption">{{ isset($c['score_adjustment'])?'Matchscore op 100 · voorzichtig bij weinig moties.':'Overeenkomst bij officieel gekoppelde moties.' }}</p>@forelse(array_slice($snapshot['party_matches'],0,5) as $p)<div class="party"><table><tr><td><div class="party-name">{{ $p['name'] }}</div><div class="track"><div class="fill" style="width:{{ $p['balanced_score']??$p['percentage'] }}%"></div></div><small>{{ $p['matched'] }} van {{ $p['compared'] }} moties{{ ($p['provisional']??false)?' · eerste indicatie':'' }}</small></td><td class="party-score">{{ $p['balanced_score']??$p['percentage'] }}{{ isset($p['balanced_score'])?'':'%' }}</td></tr></table></div>@empty<p class="empty-stat">{{ $snapshot['party_coverage']['verified_motions']??0 }} <span style="font-size:9pt;font-weight:normal">vergelijkbare officiële moties</span></p><p class="empty">Er zijn nog te weinig gecontroleerde partijstemmen gekoppeld aan jouw antwoorden om partijen te rangschikken.</p><p class="caption" style="margin-top:9pt">De stemmen blijven bewaard. Een vergelijking wordt mogelijk zodra meer officiële stemmingen zijn gecontroleerd.</p>@endforelse<p class="caption" style="margin-top:8pt">{{ !empty($snapshot['party_matches'][0]['provisional'])?'Voorlopige historische vergelijking · geen stemadvies.':'Overeenkomst bij officiële moties · geen stemadvies.' }}</p></section>
<section class="card traits-card"><h3>Wat typeert jou?</h3><div class="accent"></div>@forelse($notes  as $note)<table class="highlight"><tr><td class="tick">✓</td><td>{{ $note['text'] }}</td></tr></table>@empty @foreach(array_slice($c['highlights'],0,5) as $text)<table class="highlight"><tr><td class="tick">✓</td><td>{{ $text }}</td></tr></table>@endforeach @endforelse</section>
<section class="card method"><h3>i &nbsp; Hoe dit rapport werkt</h3><p>Gebaseerd op {{ $snapshot['total'] }} eigen antwoorden. Scores en type volgen uit vaste gewichten; {{ ($narrative['analysis_origin']??'ai')==='ai'?'AI duidt je keuzes.':'de toelichting is zonder AI gemaakt.' }} {{ $c['historical_votes']??0 }} gewogen antwoorden zijn historisch. {{ isset($c['score_adjustment'])?'Weinig antwoorden? Scores blijven dichter bij het midden.':'Kleine aantallen geven een voorlopige richting.' }} Neutraal geeft geen richting. Het type is een visuele samenvatting, geen diagnose.</p><p style="margin-top:3pt"><a style="color:#0057b8" href="https://watdenktnl.nl/methodologie">Methode en beperkingen ↗</a></p></section>
<div class="footer"><table><tr><td style="width:45%">Gebaseerd op {{ $snapshot['total'] }} beantwoorde stellingen<br>{{ $snapshot['counts']['neutral'] }} neutraal · {{ $c['duplicate_answers']??0 }} dubbelen eenmaal geteld</td><td>{{ $date }}<br>Kompas {{ ['compass-v4.2'=>'4.2','compass-v4.1'=>'4.1'][$c['version']??'']??'4.0' }} · {{ $type&&$type['provisional']?'voorlopig':'persoonlijk' }}</td><td class="right"><strong>WatDenktNederland</strong><br>watdenktnl.nl</td></tr></table></div>
</div></body></html>

@php
$c = $snapshot['compass']; $type = $c['type']; $ctx = $snapshot['analysis_context'];
$votes = collect($ctx['votes'])->keyBy('id');
$choice = ['agree'=>'VOOR', 'disagree'=>'TEGEN', 'neutral'=>'NEUTRAAL'];
$svg = static fn ($name) => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(resource_path('views/reports/art/'.$name.'.svg')));
$sculpture = 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('views/reports/art/compass-sculpture-v3.png')));
$referenceIds = array_values(array_unique(array_merge(...array_map(fn ($i) => $i['evidence_ids'], $narrative['insights']))));
$referenceNumbers = array_flip($referenceIds);
$date = \Illuminate\Support\Carbon::parse($snapshot['generated_at'])->format('d-m-Y');
@endphp
<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>Jouw Politieke Kompas — WatDenktNederland</title><style>
@page {
  margin: 27pt 29pt 30pt;
}
* {
  box-sizing: border-box;
}
body {
  font-family: "DejaVu Sans",sans-serif;
  font-size: 9pt;
  line-height: 1.45;
  color: #001f4d;
  margin: 0;
}
table {
  border-collapse: collapse;
  width: 100%;
}
td {
  vertical-align: top;
}
h1,h2,h3,p {
  margin: 0;
}
h1 {
  font-size: 24pt;
  line-height: 1.13;
  letter-spacing: -.9pt;
}
h2 {
  font-size: 15pt;
  line-height: 1.25;
  letter-spacing: -.3pt;
}
h3 {
  font-size: 11pt;
  line-height: 1.3;
  margin-bottom: 5pt;
}
.muted {
  color: #53697d;
}
.tiny {
  font-size: 7pt;
  line-height: 1.4;
}
.kicker {
  font-size: 7pt;
  font-weight: bold;
  color: #00867e;
  letter-spacing: 1.3pt;
  text-transform: uppercase;
}
.brand {
  font-size: 10pt;
  font-weight: bold;
  vertical-align: middle;
}
.brand img {
  width: 24pt;
  height: 29pt;
  vertical-align: middle;
  margin-right: 5pt;
}
.blue {
  color: #0057b8;
}
.right {
  text-align: right;
}
.header {
  margin-bottom: 9pt;
}
.header td {
  vertical-align: middle;
}
.redline {
  height: 3pt;
  width: 28pt;
  background: #e1062e;
  border-radius: 2pt;
  margin: 9pt 0 12pt;
}
.hero {
  background: #edf7f6;
  border: 1pt solid #d9ece9;
  border-radius: 16pt;
  padding: 10pt 12pt;
  margin: 10pt 0;
}
.hero-copy {
  width: 73%;
  vertical-align: middle;
}
.hero-art {
  width: 27%;
  vertical-align: middle;
  text-align: center;
}
.hero-art img {
  width: 103pt;
  height: auto;
  margin-right: -12pt;
}
.hero h2 {
  font-size: 20pt;
  margin: 4pt 0 7pt;
  line-height: 1.1;
  letter-spacing: -.8pt;
}
.hero p {
  font-size: 8.5pt;
  line-height: 1.4;
}
.trait {
  font-size: 7pt;
  color: #006e68;
  margin-top: 8pt;
}
.stat {
  padding: 5pt 0 7pt;
  border-bottom: 1pt solid #dfe7ef;
  margin-bottom: 9pt;
}
.stat td {
  width: 25%;
  border-right: 1pt solid #dfe7ef;
  padding: 0 10pt;
}
.stat td:first-child {
  padding-left: 0;
}
.stat td:last-child {
  border: 0;
}
.number {
  font-size: 20pt;
  line-height: 1.1;
  font-weight: bold;
}
.stat-label {
  font-size: 7pt;
  color: #53697d;
  margin-top: 4pt;
}
.panel {
  border: 1pt solid #dfe7ef;
  border-radius: 12pt;
  padding: 9pt;
  margin-bottom: 10pt;
}
.score {
  margin-top: 6pt;
}
.score table {
  font-size: 8pt;
  line-height: 1.2;
}
.track {
  height: 5pt;
  background: #e3e9ef;
  border-radius: 4pt;
  margin: 3pt 0;
}
.fill {
  height: 5pt;
  background: #008f87;
  border-radius: 4pt;
}
.score-meta {
  font-size: 6.5pt;
  color: #53697d;
  line-height: 1.15;
}
.score-value {
  font-weight: bold;
  color: #008079;
}
.columns>tbody>tr>td {
  width: 50%;
}
.col-left {
  padding-right: 7pt;
}
.col-right {
  padding-left: 7pt;
}
.insight {
  padding: 6pt 0;
  border-bottom: 1pt solid #e6edf3;
}
.insight:last-child {
  border: 0;
  padding-bottom: 0;
}
.insight h3 {
  font-size: 9.5pt;
}
.insight p {
  font-size: 7.7pt;
  line-height: 1.35;
}
.ref {
  display: inline-block;
  font-size: 6.5pt;
  color: #006e68;
  background: #e4f3ef;
  padding: 2pt 5pt;
  border-radius: 3pt;
  margin: 3pt 3pt 0 0;
}
.note {
  background: #f4f7fa;
  border-radius: 9pt;
  padding: 8pt;
  font-size: 7pt;
  color: #53697d;
  line-height: 1.4;
}
.footer .right {
  font-weight: bold;
}
.next {
  page-break-before: always;
}
.page-title {
  margin: 14pt 0 8pt;
  margin-top: 10pt;
}
.intro {
  font-size: 8.5pt;
  color: #53697d;
  margin-bottom: 10pt;
}
.position {
  margin: 0 0 9pt;
  padding: 10pt 11pt;
  background: #f5f8fb;
  border: 1pt solid #e2e9f1;
  border-radius: 10pt;
  page-break-inside: avoid;
  margin-bottom: 8pt;
}
.position-title {
  font-size: 8.5pt;
  font-weight: bold;
  line-height: 1.3;
  margin-top: 5pt;
}
.position p {
  font-size: 8pt;
  color: #53697d;
  line-height: 1.4;
  margin-top: 5pt;
}
.badge {
  font-size: 6.5pt;
  font-weight: bold;
  color: #0057b8;
  background: #e3edfb;
  border-radius: 3pt;
  padding: 2pt 5pt;
}
.badge-disagree {
  background: #fce5eb;
  color: #ac1033;
}
.badge-neutral {
  background: #e6eaf0;
  color: #4f637b;
}
.aside {
  background: #001f4d;
  color: #fff;
  border-radius: 11pt;
  padding: 12pt;
  margin-top: 8pt;
}
.aside .kicker {
  color: #7fdbd1;
}
.aside h3 {
  margin: 5pt 0 7pt;
}
.aside p {
  font-size: 8pt;
  line-height: 1.5;
}
.aside .ref {
  background: #143d60;
  color: #c0eee6;
}
.topic {
  border-bottom: 1pt solid #e6edf3;
  padding: 5pt 0;
  font-size: 8pt;
}
.topic:last-child {
  border: 0;
}
.inventory {
  font-size: 7pt;
  margin-top: 7pt;
}
.inventory td {
  padding: 4pt 0;
  border-bottom: 1pt solid #e7edf2;
}
.label {
  padding-bottom: 3pt;
  font-size: 7pt;
  color: #53697d;
}
.notice {
  font-size: 6.5pt;
  background: #fff4d6;
  padding: 4pt 7pt;
  margin-bottom: 10pt;
  border-radius: 4pt;
}
.method {
  font-size: 7pt;
  line-height: 1.4;
  color: #53697d;
  margin-top: 5pt;
}
.hero-copy .trait {
  font-size: 6.5pt;
  margin-top: 5pt;
}
.dense .hero h2 {
  font-size: 18pt;
}
.dense .hero p {
  font-size: 8pt;
}
.dense .hero-art img {
  width: 85pt;
}
.dense .hero {
  padding: 8pt 10pt;
}
.dense .header {
  margin-bottom: 7pt;
}
.dense h1 {
  font-size: 22pt;
}
.dense .stat {
  margin-bottom: 7pt;
}
.dense .score {
  margin-top: 4pt;
}
.dense .panel {
  padding: 8pt;
  margin-bottom: 7pt;
}
.dense .position {
  padding: 7pt 8pt;
  margin-bottom: 6pt;
}
.dense .position-title {
  font-size: 8pt;
  line-height: 1.15;
}
.dense .position p {
  font-size: 7.5pt;
  line-height: 1.2;
}
.dense .inventory {
  font-size: 6.5pt;
  margin-top: 5pt;
}
.dense .aside {
  padding: 9pt;
}
.dense .aside p {
  font-size: 7.5pt;
}
.dense .intro {
  margin-bottom: 8pt;
  font-size: 8pt;
}
.dense .insight p {
  font-size: 7.5pt;
}
.dense .insight {
  padding: 5pt 0;
}
.dense .note {
  padding: 7pt;
  font-size: 6.8pt;
}
.dense .method {
  font-size: 6.5pt;
  line-height: 1.2;
  margin-top: 3pt;
}
.dense .page-title {
  margin-top: 6pt;
}
</style></head><body class="{{ !empty($dense) ? 'dense' : '' }}">
@if(!empty($snapshot['demo']))<p class="notice">VOORBEELDRAPPORT · FICTIEVE ANTWOORDEN</p>@elseif(!empty($snapshot['development']))<p class="notice">TIJDELIJK DEV-RAPPORT · GEEN AANKOOP</p>@endif
<table class="header"><tr><td class="brand"><img src="{{ $svg('mark') }}" alt="">WatDenkt<span class="blue">Nederland</span></td><td class="right tiny muted">PERSOONLIJK RAPPORT<br>{{ $date }} · 01 / 02</td></tr></table>
<p class="kicker">Jouw keuzes. Jouw richting.</p><h1>Jouw Politieke Kompas</h1>
<div class="hero"><table><tr><td class="hero-copy"><span class="kicker">{{ ($type['provisional'] ?? true) ? 'Een eerste indicatie' : 'Jouw politieke type' }}</span><h2>{{ $type['name'] ?? 'Jouw keuzes in beeld' }}</h2><p>{{ $narrative['summary'] }}</p>@if($type)<p class="trait">{{ implode(' · ', $type['traits']) }}</p>@endif</td><td class="hero-art"><img src="{{ $sculpture }}" alt=""></td></tr></table></div>
<table class="stat"><tr><td><div class="number">{{ $snapshot['total'] }}</div><div class="stat-label">antwoorden vastgelegd</div></td><td><div class="number">{{ $ctx['eligible_count'] }}</div><div class="stat-label">unieke, eenduidige keuzes</div></td><td><div class="number">{{ $c['used_votes'] }}</div><div class="stat-label">met themagewichten</div></td><td><div class="number">{{ $snapshot['counts']['neutral'] }}</div><div class="stat-label">neutraal geantwoord</div></td></tr></table>
<table class="columns"><tr><td class="col-left"><div class="panel"><p class="kicker">De richting in je antwoorden</p><h2 style="margin-top:5pt">Jouw politieke waarden</h2>
@foreach([...$c['dimensions'], $c['spectrum']] as $d)<div class="score"><table><tr><td>{{ $d['left'] }}</td><td class="right">{{ $d['right'] }}</td></tr></table>
@if($d['percentage'] !== null)<div class="track"><div class="fill" style="width:{{ $d['percentage'] }}%"></div></div><table><tr><td class="score-value">{{ $d['percentage'] }}%</td><td class="right score-value">{{ $d['opposite_percentage'] }}%</td></tr></table><p class="score-meta">{{ $d['compared'] }} verschillende antwoorden{{ $d['provisional'] ? ' · voorlopig' : '' }}</p>
@else<div class="track"></div><p class="score-meta">Nog geen score · {{ $d['compared'] }} van minimaal {{ $d['early_minimum'] ?? $d['minimum'] }} antwoorden</p>@endif</div>@endforeach
</div><div class="note"><strong>Een momentopname van jouw antwoorden.</strong><br>{{ $c['historical_votes'] ?? 0 }} gewogen antwoorden hebben een historische context. Kleine aantallen geven een voorlopige richting, geen vaststaand profiel. De verhouding voor/tegen is geen links-rechtsscore.</div></td>
<td class="col-right"><div class="panel"><p class="kicker">Wat je antwoorden vertellen</p><h2 style="margin-top:5pt">Dit valt op bij jou</h2>@forelse($narrative['insights'] as $insight)<div class="insight"><h3>{{ $insight['heading'] }}</h3><p>{{ $insight['body'] }}</p>@foreach($insight['evidence_ids'] as $id)<span class="ref">Antwoord {{ $referenceNumbers[$id]+1 }} · {{ $choice[$votes[$id]['choice']] }}</span>@endforeach</div>@empty<p class="method">AI-toelichting is niet beschikbaar voor dit rapport. Je vastgelegde antwoorden en berekende waarden staan wel in dit document.</p>@endforelse</div>
<div class="note"><strong>Jouw stemverdeling</strong><br>{{ $snapshot['counts']['agree'] }} voor · {{ $snapshot['counts']['disagree'] }} tegen · {{ $snapshot['counts']['neutral'] }} neutraal.</div></td></tr></table>

<div class="next">
<table class="header"><tr><td class="brand"><img src="{{ $svg('mark') }}" alt="">WatDenkt<span class="blue">Nederland</span></td><td class="right tiny muted">JOUW ANTWOORDEN UITGELEGD<br>{{ $date }} · 02 / 02</td></tr></table>
<p class="kicker">Van keuze naar inzicht</p><h1 class="page-title">Dit zijn jouw standpunten</h1><p class="intro">Concrete keuzes die de analyse onderbouwen. De toelichting duidt je antwoord; waarom je precies zo koos, blijft aan jou.</p>
<table class="columns"><tr><td class="col-left">
@foreach($narrative['positions'] as $position)@if($loop->index % 2 === 0)@php($v=$votes[$position['statement_id']])<div class="position"><span class="badge badge-{{ $v['choice'] }}">{{ $choice[$v['choice']] }}</span><div class="position-title">{{ $v['title'] }}</div><p>{{ $position['interpretation'] }}</p></div>@endif @endforeach
</td><td class="col-right">
@foreach($narrative['positions'] as $position)@if($loop->index % 2 === 1)@php($v=$votes[$position['statement_id']])<div class="position"><span class="badge badge-{{ $v['choice'] }}">{{ $choice[$v['choice']] }}</span><div class="position-title">{{ $v['title'] }}</div><p>{{ $position['interpretation'] }}</p></div>@endif @endforeach
</td></tr></table>
<table class="columns"><tr><td class="col-left"><div class="panel"><h3>De keuzes achter de inzichten</h3>@foreach($referenceIds as $id)<div class="inventory"><strong>{{ $referenceNumbers[$id]+1 }} · {{ $choice[$votes[$id]['choice']] }}</strong> <a style="color:#001f4d;text-decoration:none" href="https://watdenktnl.nl/stellingen/{{ rawurlencode($votes[$id]['slug']) }}">{{ \Illuminate\Support\Str::limit($votes[$id]['title'], 145) }}</a></div>@endforeach</div></td><td class="col-right">
@foreach($narrative['nuances'] as $nuance)<div class="panel"><p class="kicker">Ruimte voor nuance</p><h3 style="margin-top:5pt">{{ $nuance['heading'] }}</h3><p style="font-size:8pt">{{ $nuance['body'] }}</p>@foreach($nuance['evidence_ids'] as $id)<p class="tiny muted" style="margin-top:5pt">{{ $choice[$votes[$id]['choice']] }} · {{ \Illuminate\Support\Str::limit($votes[$id]['title'], 125) }}</p>@endforeach</div>@endforeach
@foreach($narrative['questions'] as $question)<div class="aside"><p class="kicker">Een vraag om mee te nemen</p><p style="margin-top:7pt">{{ $question['text'] }}</p></div>@endforeach
@if(count($snapshot['party_matches']))<div class="panel" style="margin-top:12pt"><h3>Overeenkomst bij officiële moties</h3>@foreach(array_slice($snapshot['party_matches'],0,3) as $p)<div class="topic">{{ $p['name'] }} · <strong>{{ $p['percentage'] }}%</strong><br><span class="tiny muted">{{ $p['matched'] }} van {{ $p['compared'] }} vergelijkbare moties</span></div>@endforeach</div>@endif
</td></tr></table>
<div class="note"><strong>Zo lees je dit rapport</strong><p class="method">{{ ($narrative['analysis_origin'] ?? 'ai') === 'code' ? 'Het overzicht gebruikt' : 'De AI duidt' }} {{ $ctx['included_count'] }} van {{ $ctx['eligible_count'] }} unieke antwoorden. Cijfers volgen uit vaste redactionele gewichten. {{ $c['duplicate_answers'] ?? 0 }} dubbelen zijn eenmaal geteld; {{ $ctx['excluded_conflicts'] }} groepen met verschillende antwoorden zijn uitgesloten. Neutraal geeft geen richting. Historische keuzes zijn geen actuele partijposities. Een type is een samenvatting, geen diagnose of stemadvies. Partijmatches vragen minimaal tien gecontroleerde motiekoppelingen.</p><p class="method">Stellingen en context: Mijn stemmen. Methode: <a href="https://watdenktnl.nl/methodologie" style="color:#0057b8">watdenktnl.nl/methodologie</a> · {{ $c['version'] }} · rapportontwerp v3</p></div>

</div></body></html>

"""16-segment type wheel using WDNL's existing mark/icons; no generated scores."""
from pathlib import Path
from html import escape
import ast, json, math, subprocess
ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/'public/report-art'
subprocess.run(['php', str(ROOT/'scripts/design/export-compass-types.php')], check=True)
rows=json.loads((ROOT/'scripts/design/compass-types-v4.json').read_text())
module=ast.parse((ROOT/'scripts/design/generate-compass-art.py').read_text())
icons=next(ast.literal_eval(n.value)for n in module.body if isinstance(n,ast.Assign)and any(isinstance(t,ast.Name)and t.id=='ICONS'for t in n.targets))
logo=(ROOT/'public/brand/wdnl-mark-concept.svg').read_text();logo=logo[logo.index('<g'):logo.rfind('</svg>')]
for selected in [None]+rows:
 active=selected['id']if selected else 'pending';color=selected['color']if selected else '#008f87'
 p=['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 620 600">']
 for i,t in enumerate(rows):
  a=math.radians(-101.25+i*22.5);b=a+math.radians(22.5);m=(a+b)/2
  point=lambda r,theta:f'{310+r*math.cos(theta):.2f},{300+r*math.sin(theta):.2f}'
  fill=color if t['id']==active else ('#bdcfdc'if i%2 else '#d3e0e9')
  p.append(f'<path d="M{point(69,a)}L{point(180,a)}A180,180 0 0 1 {point(180,b)}L{point(69,b)}A69,69 0 0 0 {point(69,a)}Z" fill="{fill}" stroke="white" stroke-width="3"/>')
  x=310+132*math.cos(m)-10;y=300+132*math.sin(m)-10
  p.append(f'<g transform="translate({x:.2f},{y:.2f}) scale(.86)" fill="white">{icons[t["icon"]]}</g>')
  words=t['name'].removeprefix('De ').split();tx=310+249*math.cos(m);ty=300+249*math.sin(m)-(len(words)-1)*7
  for j,w in enumerate(words):
   p.append(f'<text x="{tx:.2f}" y="{ty+j*14:.2f}" text-anchor="middle" font-family="DejaVu Sans,sans-serif" font-size="11" font-weight="bold" fill="{color if t["id"]==active else "#29415d"}">{escape(w)}</text>')
 p.append('<circle cx="310" cy="300" r="67" fill="white"/><g transform="translate(278,260) scale(.40)">'+logo+'</g></svg>')
 (OUT/f'wheel-v4-{active}.svg').write_text(''.join(p)+'\n')
print('17 SVG wheels created from the 16-type catalogue and existing WDNL artwork.')

"""Repository-native SVG artwork for the compass report. No generated scores/data."""
from pathlib import Path
import math
from html import escape
OUT=Path(__file__).resolve().parents[2]/'public/report-art'
OUT.mkdir(parents=True, exist_ok=True)
TYPES=[('sociale-vernieuwer',['De Sociale','Vernieuwer'],'people'),('pragmaticus',['De','Pragmaticus'],'balance'),('beschermer',['De','Beschermer'],'shield'),('traditionele-realist',['De Traditionele','Realist'],'building'),('ondernemer',['De','Ondernemer'],'chart'),('vrijdenker',['De','Vrijdenker'],'spark'),('nationale-verbinder',['De Nationale','Verbinder'],'people'),('groene-voorloper',['De Groene','Voorloper'],'leaf'),('idealist',['De','Idealist'],'flag')]
ICONS={
'globe':'<path d="M12 0a12 12 0 1 1 0 24 12 12 0 1 1 0-24zM12 2a10 10 0 1 0 0 20 10 10 0 1 0 0-20z" fill-rule="evenodd"/><path d="M1 11h22v2H1zM11 1h2v22h-2z"/>',
'people':'<circle cx="8" cy="7" r="3"/><circle cx="18" cy="8" r="2.5"/><path d="M2 21v-3a6 6 0 0 1 12 0v3zm14 0v-3a8 8 0 0 0-2-5 5 5 0 0 1 9 5v3z"/>',
'leaf':'<path d="M3 20C-1 5 10 1 23 1c0 13-4 23-17 20L19 5z"/>',
'spark':'<path d="m13 0 2 8 8 3-8 3-2 9-3-9-9-3 9-3z"/>',
'shield':'<path d="m12 1 10 4v7c0 6-6 10-10 12C8 22 2 18 2 12V5zm0 4L6 7v5c0 4 3 7 6 9 3-2 6-5 6-9V7z" fill-rule="evenodd"/>',
'building':'<path d="m1 8 11-7 11 7v3H1zm2 5h4v8H3zm7 0h4v8h-4zm7 0h4v8h-4zM1 22h22v2H1z"/>',
'chart':'<path d="M2 15h4v9H2zm8-5h4v14h-4zm8-6h4v20h-4zM1 9l8-7 6 2 6-4 2 2-8 6-6-3-6 6z"/>',
'flag':'<path d="M3 1h3v22H3zm4 1c6-5 9 6 16 0v12c-7 6-10-5-16 0z"/>',
'balance':'<path d="M11 1h2v4h8v2h-8v14h6v3H5v-3h6V7H3V5h8zM3 8l5 10H0zm18 0 3 10H16z"/>'}
logo=(OUT.parent/'brand/wdnl-mark-concept.svg').read_text()
logo=logo[logo.index('<g'):logo.rfind('</svg>')]
def icon(key,color): return f'<g fill="{color}">{ICONS[key]}</g>'
for key in ICONS:
 (OUT/f'{key}.svg').write_text(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">{icon(key,"#00978e")}</svg>')
 (OUT/f'{key}-white.svg').write_text(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">{icon(key,"#ffffff")}</svg>')
for active in ['pending']+[t[0] for t in TYPES]:
 parts=['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 480">']
 for i,(key,words,kind) in enumerate(TYPES):
  a=(-110+i*40)*math.pi/180;b=a+40*math.pi/180;m=(a+b)/2
  xy=lambda r,t:f'{240+r*math.cos(t):.2f},{240+r*math.sin(t):.2f}'
  color='#009b90' if key==active else ['#b5cbdb','#c6d6e2'][i%2]
  parts.append(f'<path d="M{xy(54,a)} L{xy(145,a)} A145,145 0 0 1 {xy(145,b)} L{xy(54,b)} A54,54 0 0 0 {xy(54,a)}Z" fill="{color}" stroke="white" stroke-width="3"/>')
  parts.append(f'<g transform="translate({240+102*math.cos(m)-13:.2f},{240+102*math.sin(m)-13:.2f}) scale(1.08)">{icon(kind,"white")}</g>')
  tx=240+201*math.cos(m);ty=240+201*math.sin(m)-((len(words)-1)*7)
  for j,word in enumerate(words):
   parts.append(f'<text x="{tx:.2f}" y="{ty+j*15:.2f}" text-anchor="middle" fill="{ "#007a73" if key==active else "#001f4d"}" font-family="DejaVu Sans, sans-serif" font-size="11" font-weight="bold">{escape(word)}</text>')
 parts.append('<circle cx="240" cy="240" r="53" fill="white"/><g transform="translate(212,207) scale(.35)">'+logo+'</g></svg>')
 (OUT/f'wheel-{active}.svg').write_text(''.join(parts))
(OUT/'flag.svg').write_text('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 230 95"><path d="M0 95C55 2 145 35 230 0v18C141 55 71 13 0 95" fill="#e1062e"/><path d="M16 95C78 35 149 72 230 33v22C150 95 84 53 16 95" fill="#0057b8"/></svg>')
# Explicit vector strips: the PDF SVG renderer does not implement gradient painting.
stops=[(0,87,184),(171,185,206),(225,6,46)]
strips=[]
for i in range(200):
 t=(i+.5)/200*2; a,b=(stops[0],stops[1]) if t<1 else (stops[1],stops[2]); t=t if t<1 else t-1
 color='#'+''.join(f'{round(x+(y-x)*t):02x}' for x,y in zip(a,b))
 x=i*2.5; middle=x+1.25; edge=min(middle,500-middle)
 y=12-math.sqrt(max(0,144-(12-edge)**2)) if edge<12 else 0
 strips.append(f'<rect x="{x:.2f}" y="{y:.2f}" width="2.6" height="{24-2*y:.2f}" fill="{color}"/>')
(OUT/'spectrum.svg').write_text('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 24">'+''.join(strips)+'</svg>')
(OUT/'wave.svg').write_text('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 650 90"><path d="M0 10c100 0 86 75 230 80H0z" fill="#9cddda"/><path d="M0 35c75 0 75 50 162 55H0z" fill="#4ab7b2"/></svg>')
for p in OUT.glob('*.svg'): p.write_text(p.read_text().rstrip()+'\n')
print(f'{len(list(OUT.glob("*.svg")))} reusable SVG assets generated')

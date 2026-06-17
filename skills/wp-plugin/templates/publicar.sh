#!/usr/bin/env bash
#
# publicar.sh — Publica uma nova versão do plugin (template /wp-plugin).
#
# Faz, em uma passada: lê a versão do arquivo principal → builda um .zip LIMPO →
# gera o {{PLUGIN_SLUG}}.json (com changelog do readme.txt) → sobe (atômico) para
# os dois canais (auto-update + download) → ajusta permissões → valida via HTTPS.
#
# Uso:  ./scripts/publicar.sh [--dry-run]
#
# Pré-requisitos: zip, curl, python3, ssh/scp com acesso ao servidor.
# ⚠️ AJUSTE o bloco "config" abaixo para o seu servidor.
set -euo pipefail

# ----------------------------------------------------------------- config ----
PLUGIN_SLUG="{{PLUGIN_SLUG}}"               # slug do plugin (= top-dir do zip)
MAIN_FILE="{{PLUGIN_SLUG}}.php"             # arquivo principal
VERSION_CONST="{{PREFIX}}_VERSION"          # constante da versão no arquivo principal
SSH_HOST="root@SEU.SERVIDOR.IP"             # acesso SSH ao servidor de updates
WEBROOT="/var/www/SEU-SITE/htdocs"          # web root do site
UPDATES_DIR="${WEBROOT}/updates"            # canal de auto-update
DOWNLOAD_DIR="${WEBROOT}/${PLUGIN_SLUG}"    # canal de download (landing)
OWNER="www-data:www-data"                   # dono dos arquivos no servidor
BASE_URL="https://SEU-SITE"                 # URL pública (https)
DESCRIPTION="Descrição curta do plugin (usada no modal Ver detalhes)."

# ---------------------------------------------------------------- helpers ----
c0=$'\033[0m'; cg=$'\033[32m'; cp=$'\033[35m'; cy=$'\033[33m'; cr=$'\033[31m'
say(){ printf '%s▸%s %s\n' "$cp" "$c0" "$*"; }
ok(){ printf '%s  ✓%s %s\n' "$cg" "$c0" "$*"; }
warn(){ printf '%s  ! %s%s\n' "$cy" "$*" "$c0"; }
die(){ printf '%s  ✗ %s%s\n' "$cr" "$*" "$c0" >&2; exit 1; }

DRY=0; [ "${1:-}" = "--dry-run" ] && DRY=1

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PARENT="$(dirname "$REPO")"; NAME="$(basename "$REPO")"
[ -f "${REPO}/${MAIN_FILE}" ] || die "Não achei ${MAIN_FILE} em ${REPO}"

say "Verificando ambiente"
for b in zip curl python3 ssh scp; do command -v "$b" >/dev/null || die "Falta: $b"; done
VERSION="$(grep -oE "${VERSION_CONST}', '[0-9.]+'" "${REPO}/${MAIN_FILE}" | grep -oE '[0-9.]+' | head -1)"
[ -n "$VERSION" ] || die "Não consegui ler ${VERSION_CONST}"
ok "versão a publicar: ${cp}${VERSION}${c0}"

# --------------------------------------------------------- build do zip -----
STAGE="$(mktemp -d)"; trap 'rm -rf "$STAGE"' EXIT
ZIP="${STAGE}/${PLUGIN_SLUG}.zip"
say "Buildando o pacote limpo"
( cd "$PARENT" && zip -rqX "$ZIP" "$NAME" \
    -x "${NAME}/.git/*" "${NAME}/docs/*" "${NAME}/landing/*" "${NAME}/scripts/*" \
       "${NAME}/README.md" "${NAME}/.gitignore" '*/.DS_Store' '*.bak' '*.map' )
# IMPORTANTE: capturar a lista numa var e usar here-string — `unzip -l | grep -q`
# falha com `set -o pipefail` (grep -q fecha o pipe → unzip SIGPIPE).
ZIPLIST="$(unzip -l "$ZIP")"
grep -q "${NAME}/${MAIN_FILE}" <<<"$ZIPLIST" || die "O zip não contém ${MAIN_FILE}."
grep -qiE "${NAME}/(landing|docs|scripts)/" <<<"$ZIPLIST" && die "Vazou arquivo não-plugin pro zip." || true
FILES="$(tail -1 <<<"$ZIPLIST" | awk '{print $2}')"; SIZE="$(wc -c < "$ZIP" | tr -d ' ')"
ok "pacote: ${FILES} arquivos · $((SIZE/1024)) KB"

# ------------------------------------------------- changelog → JSON ---------
say "Gerando ${PLUGIN_SLUG}.json"
JSON="${STAGE}/${PLUGIN_SLUG}.json"
DESCRIPTION="$DESCRIPTION" VERSION="$VERSION" SLUG="$PLUGIN_SLUG" BASE_URL="$BASE_URL" \
DATE_UTC="$(date -u +'%Y-%m-%d %H:%M:%S')" SECTIONS_DIR="${REPO}/scripts/sections" \
python3 - "${REPO}/readme.txt" > "$JSON" <<'PY'
import os, re, sys, json
txt = open(sys.argv[1], encoding='utf-8').read() if os.path.exists(sys.argv[1]) else ''
changelog = ''
m = re.search(r'==\s*Changelog\s*==(.*)$', txt, re.S | re.I)
if m:
    blocks = re.split(r'\n=\s*([0-9][0-9A-Za-z.\-]*)\s*=\n', m.group(1))
    for ver, content in list(zip(blocks[1::2], blocks[2::2]))[:6]:
        items = [l.strip()[1:].strip() for l in content.splitlines() if l.strip().startswith('*')]
        if items:
            changelog += '<h4>%s</h4><ul>%s</ul>' % (ver, ''.join('<li>%s</li>' % i.replace('&','&amp;').replace('<','&lt;') for i in items))
def section(name, fb=''):
    p = os.path.join(os.environ.get('SECTIONS_DIR',''), name + '.html')
    return open(p, encoding='utf-8').read().strip() if os.path.exists(p) else fb
sections = {
    'description':  section('description', '<p>%s</p>' % os.environ['DESCRIPTION']),
    'installation': section('installation'),
    'faq':          section('faq'),
    'screenshots':  section('screenshots'),
    'changelog':    changelog or '<p>Sem changelog.</p>',
}
sections = {k: v for k, v in sections.items() if v}
data = {
    'name': os.environ['SLUG'], 'slug': os.environ['SLUG'], 'version': os.environ['VERSION'],
    'requires': '6.0', 'tested': '7.0', 'requires_php': '7.4',
    'download_url': '%s/updates/%s.zip' % (os.environ['BASE_URL'], os.environ['SLUG']),
    'last_updated': os.environ['DATE_UTC'], 'sections': sections, 'banners': {},
}
print(json.dumps(data, ensure_ascii=False, indent=2))
PY
python3 -c "import json;json.load(open('$JSON'))" || die "JSON inválido"
ok "json ok (version $(python3 -c "import json;print(json.load(open('$JSON'))['version'])"))"

if [ "$DRY" = "1" ]; then
  echo; warn "DRY-RUN — nada enviado."; cat "$JSON"; exit 0
fi

# ----------------------------------------------------------------- upload ---
# Upload ATÔMICO (sobe .tmp → mv): o rename é atômico, o servidor nunca serve
# arquivo no meio da escrita.
say "Enviando (atômico) para ${SSH_HOST}"
ssh -o ConnectTimeout=20 "$SSH_HOST" "mkdir -p '$UPDATES_DIR' '$DOWNLOAD_DIR'"
UJ="${UPDATES_DIR}/${PLUGIN_SLUG}.json"; UZ="${UPDATES_DIR}/${PLUGIN_SLUG}.zip"
DZ="${DOWNLOAD_DIR}/${PLUGIN_SLUG}.zip"; DV="${DOWNLOAD_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
scp -q "$JSON" "${SSH_HOST}:${UJ}.tmp"
scp -q "$ZIP"  "${SSH_HOST}:${UZ}.tmp"
scp -q "$ZIP"  "${SSH_HOST}:${DZ}.tmp"
scp -q "$ZIP"  "${SSH_HOST}:${DV}.tmp"
ssh -o ConnectTimeout=20 "$SSH_HOST" "set -e
  for f in '$UJ' '$UZ' '$DZ' '$DV'; do mv -f \"\$f.tmp\" \"\$f\"; chown ${OWNER} \"\$f\"; chmod 644 \"\$f\"; done"
ok "auto-update + download publicados (atômico)"

# --------------------------------------------------------------- validação --
# O nginx pode ter open_file_cache (~60s) servindo metadados antigos. Validamos
# com paciência (~80s) e, se não confirmar, AVISAMOS (o disco já está certo).
say "Validando no ar — pode levar até ~1min (cache do servidor)"
LIVE=""; for i in $(seq 1 16); do
  sleep 5
  LIVE="$(curl -fsS --max-time 15 "${BASE_URL}/updates/${PLUGIN_SLUG}.json?nc=$(date +%s%N)" 2>/dev/null | python3 -c 'import sys,json;print(json.load(sys.stdin)["version"])' 2>/dev/null || true)"
  [ "$LIVE" = "$VERSION" ] && break
  [ "$i" -lt 16 ] && warn "tentativa ${i}/16: propagando…"
done
[ "$LIVE" = "$VERSION" ] && ok "json publicado: versão ${LIVE}" || warn "ainda servindo '${LIVE:-?}' — cache do servidor (≤60s); o disco já está correto."

echo; printf '%s━━━ %s %s publicado ━━━%s\n' "$cg" "$PLUGIN_SLUG" "$VERSION" "$c0"
echo "  Download : ${BASE_URL}/${PLUGIN_SLUG}/${PLUGIN_SLUG}.zip"
echo "  Update   : clientes veem a atualização em até 6h (ou na hora via 'verificar novamente')."

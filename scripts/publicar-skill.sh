#!/usr/bin/env bash
#
# publicar-skill.sh — Publica uma nova versão da SKILL para todos os usuários.
#
# Bump de versão no plugin.json → commit → push → tag → release no GitHub.
# Quem tem a skill instalada pelo marketplace recebe no próximo startup do
# Claude Code (auto-update). Quem usa git clone recebe via a checagem interna.
#
# Uso:
#   ./scripts/publicar-skill.sh                      # bump de patch (x.y.Z+1)
#   ./scripts/publicar-skill.sh 1.2.0                # versão explícita
#   ./scripts/publicar-skill.sh 1.2.0 "o que mudou"  # versão + descrição
#
set -euo pipefail

c0=$'\033[0m'; cg=$'\033[32m'; cp=$'\033[35m'; cr=$'\033[31m'
say(){ printf '%s▸%s %s\n' "$cp" "$c0" "$*"; }
ok(){ printf '%s  ✓%s %s\n' "$cg" "$c0" "$*"; }
die(){ printf '%s  ✗ %s%s\n' "$cr" "$*" "$c0" >&2; exit 1; }

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO"
MANIFEST=".claude-plugin/plugin.json"
[ -f "$MANIFEST" ] || die "Não achei $MANIFEST (rode da raiz do repo da skill)."
for b in git python3 gh; do command -v "$b" >/dev/null || die "Falta: $b"; done

CUR="$(python3 -c "import json;print(json.load(open('$MANIFEST'))['version'])")"
NEW="${1:-}"
MSG="${2:-melhorias e ajustes}"
if [ -z "$NEW" ]; then
  NEW="$(python3 -c "v='$CUR'.split('.');v[2]=str(int(v[2])+1);print('.'.join(v))")"
fi
[ "$NEW" != "$CUR" ] || die "A versão nova (${NEW}) é igual à atual."
say "Publicando skill: ${cp}${CUR} → ${NEW}${c0}"

# 1) bump no plugin.json
python3 - "$MANIFEST" "$NEW" <<'PY'
import json,sys
p=sys.argv[1]; d=json.load(open(p)); d['version']=sys.argv[2]
json.dump(d,open(p,'w'),ensure_ascii=False,indent=2); open(p,'a').write('\n')
PY
ok "plugin.json → ${NEW}"

# 2) changelog (prepend entrada sob a versão nova)
if [ -f CHANGELOG.md ]; then
  DATE="$(date +%Y-%m-%d)"
  python3 - "$NEW" "$DATE" "$MSG" <<'PY'
import sys,io
ver,date,msg=sys.argv[1],sys.argv[2],sys.argv[3]
t=io.open('CHANGELOG.md',encoding='utf-8').read()
if "## ["+ver+"]" in t:
    raise SystemExit(0)  # já existe, não duplica
entry=f"## [{ver}] — {date}\n\n### Alterado\n- {msg}\n\n"
# Insere DEPOIS do bloco "[Não lançado]" (se houver), senão antes da 1ª entrada.
unrel=t.find("## [Não lançado]")
if unrel!=-1:
    nxt=t.find("## [", unrel+1)            # próxima seção após "Não lançado"
    i=nxt if nxt!=-1 else len(t)
else:
    i=t.find("## [")
    if i==-1: i=len(t)
t=t[:i]+entry+t[i:]
io.open('CHANGELOG.md','w',encoding='utf-8').write(t)
PY
  ok "CHANGELOG atualizado"
fi

# 3) commit + push + tag + release
git add -A
git commit -q -m "🚀 release: v${NEW} — ${MSG}" || die "nada para commitar?"
git push -q origin main
git tag -f "v${NEW}" -m "v${NEW} — ${MSG}" >/dev/null 2>&1 || true
git push -q origin "v${NEW}" 2>/dev/null || true
gh release create "v${NEW}" --title "v${NEW} — /wp-plugin" --notes "${MSG}" 2>/dev/null \
  && ok "release v${NEW} criada" || ok "tag v${NEW} publicada (release opcional)"

echo
printf '%s━━━ skill /wp-plugin v%s publicada ━━━%s\n' "$cg" "$NEW" "$c0"
echo "  Usuários do marketplace recebem no próximo startup do Claude Code."
echo "  Usuários via git clone recebem pela checagem interna da skill."

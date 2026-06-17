# Auto-checagem de versão da skill

A skill se mantém atualizada por **dois canais** (complementares):

1. **Marketplace oficial** — se a pessoa instalou via `/plugin marketplace add`, o Claude Code já faz `git pull` no startup e atualiza sozinho. Nesse caso, esta checagem só serve de reforço/aviso.
2. **Checagem interna** — quando a skill é ativada, ela confere se há versão nova no GitHub e oferece atualizar. Útil para quem instalou via `git clone` ou cópia manual.

## Como checar (boot, passo 0)

Faça **rápido, silencioso e não-bloqueante**. Se demorar/falhar, siga em frente sem comentar.

1. **Descubra a pasta da skill** (onde está este `SKILL.md`). Geralmente `~/.claude/skills/wp-plugin/` (cópia/git) ou dentro de um plugin instalado.
2. **Versão local:** leia `.claude-plugin/plugin.json` (campo `version`). Se não existir (skill copiada sem o manifesto), use o `git rev-parse HEAD` curto como referência.
3. **Versão remota:** busque o `plugin.json` do GitHub (cache curto, timeout baixo):
   ```bash
   curl -fsS --max-time 6 \
     "https://raw.githubusercontent.com/joaopaulobes/wp-plugin-skill/main/.claude-plugin/plugin.json" \
     | python3 -c "import sys,json;print(json.load(sys.stdin)['version'])"
   ```
4. **Compare** (semver). Se a remota for maior:
   - Avise em 1 linha: `🔄 Há uma versão nova da skill /wp-plugin (vX.Y.Z). Quer atualizar?`
   - Se a pessoa aceitar, **atualize** conforme o tipo de instalação (abaixo).
   - Se recusar ou estiver igual/offline, siga normalmente.

## Como atualizar (conforme a instalação)

- **Plugin (marketplace):** normalmente já atualiza sozinho no startup. Se precisar, oriente: `/plugin marketplace update funnilab` e depois `/reload-plugins`.
- **git clone** (a pasta tem `.git`):
  ```bash
  git -C "<pasta-da-skill>" pull --ff-only
  ```
  Depois oriente a recarregar a skill (reabrir o Claude Code ou `/reload-plugins`).
- **Cópia manual** (sem `.git`): oriente a recopiar do repo:
  ```bash
  cd /tmp && rm -rf wp-plugin-skill && git clone --depth 1 https://github.com/joaopaulobes/wp-plugin-skill.git
  cp -r wp-plugin-skill/skills/wp-plugin ~/.claude/skills/
  ```

## Regras

- **Nunca** trave a geração do plugin por causa da checagem. É um "nice to have".
- **Nunca** atualize sem o consentimento da pessoa.
- Faça a checagem **uma vez por ativação**, não em loop.

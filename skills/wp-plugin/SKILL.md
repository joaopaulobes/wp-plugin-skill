---
name: wp-plugin
description: Cria plugins de WordPress completos, profissionais e prontos para distribuir a partir de uma descrição em linguagem natural. Use quando a pessoa quiser construir, gerar, montar ou estruturar um plugin de WordPress (qualquer tipo), ou pedir ajuda com a arquitetura, o auto-update, o readme.txt ou a landing de um plugin. O usuário descreve o que o plugin deve fazer (pode comparar com plugins existentes) e a skill entrega o código, o pacote .zip, a configuração do servidor de auto-update e a landing page.
---

# /wp-plugin — Construtor de Plugins de WordPress

Você é um **engenheiro sênior de WordPress**. Esta skill transforma a descrição de um plugin (em linguagem natural) num **plugin completo, seguro e profissional**, pronto para instalar e distribuir — junto com o pacote `.zip`, o guia do servidor de auto-update e uma landing page.

**Idioma:** converse e gere toda a documentação em **português (BR)**. O CÓDIGO segue convenções internacionais (nomes de função/variável em inglês), mas as strings de interface usam i18n com tradução PT-BR.

> **Regra de ouro:** nunca copie código de plugins de terceiros (BetterLinks, Yoast, etc.). Implemente funcionalidades equivalentes do zero — padrões de UX e funcionalidade não são protegidos, mas código-fonte é. Cite isso para o usuário se ele pedir para "clonar" um plugin comercial.

---

## 🟢 Boot Routine (ao ativar a skill)

Quando a skill é chamada, **antes de qualquer coisa**:

0. **Checagem de versão (rápida, silenciosa, não-bloqueante).** Veja `references/self-update.md`. Em resumo: compare a versão local (`.claude-plugin/plugin.json`) com a do GitHub. Se houver versão nova, avise em 1 linha e **ofereça atualizar** (`git pull` se for repo git; senão, instrua). Se estiver offline ou já atualizada, **siga em frente sem comentar**. Nunca trave o fluxo por causa disso.

1. Apresente-se em 3-4 linhas:
   ```
   🧩 /wp-plugin — Construtor de Plugins de WordPress

   Me descreva, com o máximo de detalhes, o plugin que você quer criar:
   o que ele faz, onde aparece (painel/site), o que o usuário configura,
   e — se ajudar — compare com plugins que você já conhece.

   Quanto mais detalhe, melhor o resultado. Pode mandar.
   ```
2. **Aguarde a descrição do usuário.** Não comece a gerar nada antes disso.
3. Se já existir um plugin em andamento nesta sessão (pasta criada), ofereça **continuar/aperfeiçoar** em vez de começar do zero.

---

## 📋 Fase 1 — Entrevista (entender o plugin)

Depois que o usuário descrever o plugin, **valide o entendimento** e preencha as lacunas. Faça as perguntas necessárias usando `AskUserQuestion` (nunca em texto puro) — mas só o que for realmente ambíguo; não interrogue à toa.

Mapeie estes pontos (inferir o que der, perguntar o que faltar):

- **Nome do plugin** e um **slug** (ex.: `meu-plugin`) + **prefixo de código** (ex.: `Mpl_` / `mpl_`).
- **O que ele faz** (o core) e **onde vive**: só no painel admin, só no front-end, ou ambos.
- **Telas no painel**: menu próprio? submenus? página de configurações?
- **Dados**: precisa guardar dados? Em **tabelas próprias** (volume/consulta específica) ou em **CPT + post meta** (conteúdo editorial) ou só em **options**? (ver `references/blueprint.md` → "Onde guardar dados").
- **Front-end**: shortcode? bloco Gutenberg? rota/rewrite? widget?
- **Integrações**: REST API? WP-Cron (tarefas agendadas)? e-mail? API externa?
- **Interação**: formulários (admin-post), AJAX, ações em massa?
- **Distribuição**: vai ser **white-label** (rebrandável)? vai ter **auto-update** próprio? landing page?
- **Marca/visual**: cores, identidade (se for ter UI rica).

Ao final, **ecoe um resumo do escopo** (bullet points) e confirme com o usuário antes de gerar. Se o usuário disser "pode ir", avance.

---

## 🏗️ Fase 2 — Plano de arquitetura

Com base no escopo, monte um **plano técnico curto** (em PT-BR) escolhendo os blocos do `references/blueprint.md` que se aplicam. Liste:

- Estrutura de arquivos (quais classes/arquivos vão existir).
- Onde os dados ficam (tabelas/CPT/options) e o schema.
- Hooks principais (admin_menu, init, template_redirect, wp_ajax_*, etc.).
- O que é front-end vs admin.
- Recursos de distribuição (i18n, white-label, auto-update, uninstall).

Mostre o plano e siga (não precisa de aprovação formal a cada item — o usuário já confirmou o escopo).

---

## ⚙️ Fase 3 — Geração do plugin

Gere o plugin numa pasta `./<slug>/` (no diretório de trabalho atual do usuário). **Use os templates** em `templates/plugin/` como ponto de partida e os snippets em `references/snippets.md` para cada padrão. Substitua os placeholders:

| Placeholder | Vira |
|---|---|
| `{{PLUGIN_NAME}}` | Nome exibido (ex.: "Meu Plugin") |
| `{{PLUGIN_SLUG}}` | slug em kebab-case (ex.: `meu-plugin`) |
| `{{PREFIX}}` | Prefixo de classe (ex.: `Mpl`) |
| `{{prefix}}` | Prefixo de função/constante minúsculo (ex.: `mpl`) |
| `{{AUTHOR}}` / `{{AUTHOR_URI}}` | Autor e URL |
| `{{DESCRIPTION}}` | Descrição curta (header) |

**Princípios inegociáveis** (ver `references/checklist.md`):

- **Segurança sempre**: `nonce` em toda escrita, `current_user_can()` (capability) em toda ação de admin, `$wpdb->prepare()` em toda query, **escape na saída** (`esc_html`/`esc_attr`/`esc_url`/`wp_kses`), sanitização na entrada.
- **Sem dependências externas** a menos que essencial; se embarcar lib de terceiros, preserve a licença e credite.
- **i18n** desde o início: strings em inglês envolvidas em `__()`/`esc_html__()` com text domain = slug.
- **Autoloader** simples para as classes `{{PREFIX}}_*`.
- **`uninstall.php`** que só remove dados se o usuário optar (setting `delete_on_uninstall`, default off).
- **Versionamento de schema** (`{{PREFIX}}_DB_VERSION`) + migração via `dbDelta`.
- Código limpo, comentado em PT-BR onde ajudar, seguindo o padrão WordPress.

Implemente **realmente** o core descrito — não deixe "TODO". Se o escopo for grande, construa em incrementos funcionais e diga ao usuário o que já está pronto.

### Entregáveis do plugin
1. **Arquivo principal** `<slug>.php` (header + bootstrap).
2. **Classes** em `includes/` (install/migração, dados, admin, front, etc. conforme o escopo).
3. **Assets** (`assets/css`, `assets/js`) se tiver UI.
4. **i18n** (`languages/<slug>.pot` + `<slug>-pt_BR.po/.mo`).
5. **`readme.txt`** no formato WordPress.org (ver template).
6. **`uninstall.php`**.

---

## 📦 Fase 4 — Empacotar (.zip de instalação)

Gere o `.zip` instalável (top-dir = slug do plugin), excluindo lixo:

```bash
cd <pasta-pai> && zip -rqX <slug>.zip <slug> \
  -x "<slug>/.git/*" "<slug>/docs/*" "<slug>/landing/*" "<slug>/scripts/*" \
     "<slug>/README.md" '*/.DS_Store' '*.bak'
```

Confirme que o zip contém o `<slug>.php` e **não** contém docs/landing. Esse é o arquivo que a pessoa envia em **Plugins → Adicionar plugin → Enviar plugin**.

---

## 🔄 Fase 5 — Auto-update (servidor próprio)

Se o usuário quiser auto-update (recomende para quem vai distribuir), adicione a classe `class-{{prefix}}-updater.php` (ver `templates/plugin/includes/` e `references/auto-update.md`) e **gere o guia de configuração do servidor** para o usuário, explicando:

- Onde hospedar `<slug>.json` (metadados) + `<slug>.zip` (pacote) — qualquer servidor estático ou subpasta de um site.
- O formato do JSON (versão, changelog, download_url, sections, banners).
- Como o cliente do plugin consome (filtros `pre_set_site_transient_update_plugins` + `plugins_api`).
- O script `publicar.sh` (template) que builda + sobe + valida.
- A pegadinha de cache (CDN/`open_file_cache`) e como o script trata.

Entregue isso como um `docs/AUTO-UPDATE.md` dentro do projeto + explique no chat.

---

## 🖥️ Fase 6 — Landing page

Gere uma **landing page profissional** do plugin (igual ao padrão do FunniLinks) em `landing/index.html`, autocontida, responsiva, com dark-mode automático — a partir de `templates/landing/index.html`. Inclua: hero com CTA de download, faixa de confiança, grid de recursos, showcase de telas, "como funciona", FAQ e rodapé. Use as cores da marca do plugin (pergunte se não souber).

Se o plugin tiver UI, **capture screenshots reais** (instruções em `references/landing.md`) para a landing e para o modal "Ver detalhes". Se não houver site para capturar, deixe placeholders e instrua o usuário.

Inclua também a **página de ajuda de instalação** (`landing/instalar/index.html`) com passo a passo.

---

## ♻️ Fase 7 — Iteração

O usuário vai aperfeiçoar. A cada pedido:
1. Faça a mudança no código.
2. Re-lint (se tiver PHP disponível: `php -l arquivo.php`; senão avise).
3. Bump de versão quando fizer sentido (header + constante + `readme.txt` + changelog).
4. Re-empacote o `.zip`.
5. Se houver auto-update configurado, lembre de re-publicar com o `publicar.sh`.

Mantenha um `docs/CHANGELOG.md` e o `readme.txt` em dia.

---

## 🧭 Referências (leia sob demanda)

- `references/blueprint.md` — **o mapa completo** da arquitetura de plugins WP (anatomia, onde guardar dados, hooks, front-end, REST, cron, blocos, segurança, distribuição). **Leia sempre** antes de planejar.
- `references/snippets.md` — trechos prontos por padrão (tabela+migração, tela admin, AJAX, rewrite, shortcode, CPT, settings, hooks, i18n).
- `references/auto-update.md` — servidor de updates + cliente + `publicar.sh` + pegadinhas.
- `references/landing.md` — gerar a landing + capturar screenshots + modal "Ver detalhes" rico.
- `references/checklist.md` — checklist de qualidade/segurança/distribuição antes de entregar.

## 🧩 Templates

- `templates/plugin/` — esqueleto do plugin (arquivos com placeholders).
- `templates/publicar.sh` — script de publicação do auto-update.
- `templates/landing/` — landing + página de instalação.

---

## ✅ Definição de "pronto"

Um plugin entregue por esta skill tem, no mínimo:
- [ ] Plugin funcional, seguro (nonce/capability/prepare/escape) e i18n.
- [ ] `.zip` instalável validado.
- [ ] `readme.txt` + `uninstall.php`.
- [ ] (se pedido) auto-update + guia de servidor + `publicar.sh`.
- [ ] Landing page + página de instalação.
- [ ] Resumo final no chat: o que foi entregue, como instalar, próximos passos.

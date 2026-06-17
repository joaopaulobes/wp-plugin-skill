<div align="center">

# 🧩 /wp-plugin — Construtor de Plugins de WordPress

**Uma Skill para o Claude Code que cria plugins de WordPress completos e profissionais a partir de uma descrição em linguagem natural.**

![Skill](https://img.shields.io/badge/Claude%20Code-Skill-4e2783)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-24a655)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-7c3aed)
![Licença](https://img.shields.io/badge/licença-MIT-24a655)

[📖 Guia de instalação completo →](https://funnilab.com/skill-wpplugin)

</div>

---

## 📑 Sumário

- [O que é](#-o-que-é)
- [O que a skill gera](#-o-que-a-skill-gera)
- [Como instalar a skill](#-como-instalar-a-skill)
- [Como usar](#-como-usar)
- [Estrutura do repositório](#-estrutura-do-repositório)
- [Exemplos de pedidos](#-exemplos-de-pedidos)
- [FAQ](#-faq)
- [Licença](#-licença)

---

## 📖 O que é

A **`/wp-plugin`** é uma skill (habilidade) para o **Claude Code**. Depois de instalada, você a chama, **descreve em português o plugin que quer** (pode comparar com plugins que já existem) e ela **constrói o plugin inteiro** — código, pacote `.zip`, configuração de auto-update e landing page. Você vai aperfeiçoando até ficar do jeito que quer e instala no seu WordPress.

Ela carrega um **WordPress Plugin Blueprint** completo — o mapa de arquitetura de plugins (admin, front-end, banco, REST, cron, blocos, segurança, distribuição) — e usa esse conhecimento + templates prontos para gerar plugins seguros e no padrão WordPress.

## 📦 O que a skill gera

A partir da sua descrição, ela entrega:

- ✅ **Plugin completo e funcional** — arquivo principal, classes, assets, seguro (nonce, capability, prepare, escape) e com i18n (PT-BR).
- ✅ **Arquivo de instalação (`.zip`)** — pronto para enviar em *Plugins → Adicionar plugin → Enviar plugin*.
- ✅ **`readme.txt`** (formato WordPress.org) + **`uninstall.php`**.
- ✅ **Auto-update próprio** — cliente no plugin + JSON de metadados + **guia completo de como configurar o servidor** + script `publicar.sh`.
- ✅ **Landing page profissional** do plugin (igual à do FunniLinks) + **página de instalação** passo a passo.
- ✅ **Iteração** — você pede ajustes e ela refaz código, versão, zip e landing.

## 🚀 Como instalar a skill

> Guia ilustrado e completo: **https://funnilab.com/skill-wpplugin**

A skill é a pasta `skill/wp-plugin/`. Para instalar no seu Claude Code:

```bash
# 1. Clone (ou baixe) este repositório
git clone https://github.com/joaopaulobes/wp-plugin-skill.git

# 2. Copie a pasta da skill para as suas skills do Claude Code
cp -r wp-plugin-skill/skill/wp-plugin ~/.claude/skills/

# 3. Reabra o Claude Code. Pronto — a skill /wp-plugin está disponível.
```

Requisitos no seu computador: **Claude Code**, e para empacotar/validar localmente: `zip`, `php` (opcional, para lint) e `curl`/`python3` (para o auto-update). Para distribuir com auto-update, um servidor/hospedagem que sirva 2 arquivos estáticos.

## 💬 Como usar

No Claude Code, digite:

```
/wp-plugin
```

A skill se apresenta e pede a descrição. **Descreva o plugin com o máximo de detalhes** — o que faz, onde aparece (painel/site), o que o usuário configura, e compare com plugins que você conhece, se ajudar. Quanto mais detalhe, melhor o resultado. A partir daí ela planeja, gera e você vai refinando.

## 🗂️ Estrutura do repositório

```
wp-plugin-skill/
├── skill/wp-plugin/            ← a SKILL (copie para ~/.claude/skills/)
│   ├── SKILL.md                  cérebro: entrevista → geração → iteração
│   ├── references/
│   │   ├── blueprint.md          mapa completo da arquitetura de plugins WP
│   │   ├── snippets.md           trechos prontos por padrão
│   │   ├── auto-update.md         servidor de updates + cliente + pegadinhas
│   │   ├── landing.md            landing + screenshots + modal "Ver detalhes"
│   │   └── checklist.md          qualidade/segurança antes de entregar
│   └── templates/
│       ├── plugin/               esqueleto do plugin (placeholders)
│       ├── publicar.sh           script de publicação do auto-update
│       └── landing/              template da landing page
├── guia/                        ← página-guia de instalação (funnilab.com/skill-wpplugin)
└── docs/
```

## 🧪 Exemplos de pedidos

- "Quero um plugin que mostra um aviso de cookies (LGPD) configurável, com cores e texto editáveis no painel e um botão de aceitar."
- "Um plugin de avaliações de produtos: custom post type para reviews, nota de 1 a 5, shortcode pra exibir numa página, e média no painel."
- "Algo parecido com o BetterLinks: encurtador de links com redirect, categorias e rastreio de cliques." *(a skill constrói uma versão original — nunca copia código de terceiros.)*

## ❓ FAQ

**Preciso saber programar?** Não. Você descreve em português; a skill escreve o código.

**Funciona pra qualquer tipo de plugin?** Para a grande maioria (admin, front, shortcode, CPT, REST, cron, blocos dinâmicos, auto-update). Casos muito específicos podem precisar de ajustes manuais — a skill avisa.

**Ela copia plugins existentes?** Não. Você pode comparar com plugins conhecidos para explicar o que quer, mas a skill implementa tudo **do zero** (código de terceiros é protegido; funcionalidade/UX não).

**O auto-update é obrigatório?** Não — é opcional. Se quiser distribuir, ela monta o servidor de updates e explica a configuração.

## 📄 Licença

MIT — veja [LICENSE](LICENSE). Os plugins que você gera com a skill são seus.

<div align="center">

—

por **[FunniLab](https://funnilab.com)**

</div>

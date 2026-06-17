# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.
O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/) e o projeto usa [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.1.0] — 2026-06-17

### Alterado
* marketplace + auto-update da skill (plugin Claude Code) e checagem interna de versão

## [Não lançado]

### Planejado
- Captura automática de screenshots quando há um WordPress de teste disponível.
- Scaffold de blocos Gutenberg com UI (`@wordpress/scripts`).
- Templates extras de auto-update (GitHub Releases, S3/R2).

## [1.0.0] — 2026-06-17

### Adicionado
- **Skill `/wp-plugin`** (`skill/wp-plugin/SKILL.md`): entrevista → plano → geração → empacotamento → auto-update → landing → iteração.
- **WordPress Plugin Blueprint** (`references/blueprint.md`): mapa completo da arquitetura de plugins WP.
- **References**: `snippets.md`, `auto-update.md`, `landing.md`, `checklist.md`.
- **Templates**: esqueleto do plugin (com placeholders), `publicar.sh` e template de landing + favicon.
- **Página-guia de instalação** (`guia/`) publicada em `funnilab.com/skill-wpplugin`.
- **README** profissional (badges, diagramas, exemplos, FAQ), `LICENSE` (MIT), `CONTRIBUTING`, `SECURITY`, `CODE_OF_CONDUCT` e templates de issue/PR.

[Não lançado]: https://github.com/joaopaulobes/wp-plugin-skill/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/joaopaulobes/wp-plugin-skill/releases/tag/v1.0.0

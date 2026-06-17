# Contribuindo com a `/wp-plugin`

Obrigado por querer melhorar a skill! Toda contribuição é bem-vinda — de uma correção de typo a um novo padrão no blueprint.

## Como contribuir

1. **Abra uma issue antes** para mudanças grandes, descrevendo a ideia (evita retrabalho).
2. Faça um **fork** e crie uma branch a partir da `main`:
   ```bash
   git checkout -b feat/minha-melhoria
   ```
3. Faça as mudanças e **teste** (veja abaixo).
4. Abra um **Pull Request** para a `main` explicando o que mudou e por quê.

## O que dá para melhorar

| Área | Onde | Exemplos |
|---|---|---|
| **Conhecimento** | `skill/wp-plugin/references/` | novos padrões no `blueprint.md`, snippets, pegadinhas |
| **Templates** | `skill/wp-plugin/templates/` | esqueleto, `publicar.sh`, landing |
| **Comportamento** | `skill/wp-plugin/SKILL.md` | melhorar a entrevista, o fluxo de geração |
| **Guia** | `guia/index.html` | UX, conteúdo, acessibilidade |
| **Docs** | `README.md`, este arquivo | clareza, exemplos |

## Padrões

- **Idioma:** PT-BR na documentação e nas mensagens ao usuário. Código segue convenções internacionais (nomes em inglês) com strings traduzíveis.
- **Commits:** use mensagens claras no formato `<emoji> <tipo>: <descrição>` (ex.: `✨ feat: adiciona snippet de cron`, `🐛 fix:`, `📚 docs:`, `💄 style:`, `♻️ refactor:`).
- **Snippets/templates de PHP:** devem seguir o `references/checklist.md` (nonce, capability, `$wpdb->prepare`, escaping, i18n, prefixação).
- **Sem código de terceiros:** nunca inclua trechos copiados de plugins comerciais. Tudo original.

## Testando

- **Templates de plugin:** os arquivos têm placeholders (`{{PREFIX}}` etc.), então `php -l` não roda direto neles — valide gerando um plugin de exemplo pela skill e rodando `php -l` no resultado.
- **Guia/landing:** abra o HTML no navegador e confira responsividade + dark-mode.
- **`publicar.sh`:** teste com `--dry-run` (não envia nada).

## Reportando bugs e ideias

Use os templates de [issue](https://github.com/joaopaulobes/wp-plugin-skill/issues/new/choose). Para vulnerabilidades de segurança, veja [SECURITY.md](SECURITY.md).

## Licença

Ao contribuir, você concorda que sua contribuição será licenciada sob a **MIT**, como o resto do projeto.

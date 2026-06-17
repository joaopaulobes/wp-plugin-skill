# Política de Segurança

## Reportar uma vulnerabilidade

A segurança é levada a sério — tanto na própria skill quanto nos plugins que ela gera.

Se você encontrar uma vulnerabilidade, **não abra uma issue pública**. Em vez disso:

- Use o **[Private Vulnerability Reporting](https://github.com/joaopaulobes/wp-plugin-skill/security/advisories/new)** do GitHub, ou
- Entre em contato em **https://funnilab.com**.

Inclua, se possível: descrição do problema, passos para reproduzir, impacto e versão afetada.

Faremos o possível para responder rapidamente, confirmar o recebimento e corrigir.

## Escopo

- **A skill** (`skill/wp-plugin/`) e os scripts auxiliares (ex.: `publicar.sh`).
- **Os padrões/templates** que geram código — uma falha aqui se propaga para os plugins gerados, então é prioridade.

## Boas práticas já embutidas

Os plugins gerados seguem um checklist de segurança (`references/checklist.md`): nonces, capabilities, queries preparadas (`$wpdb->prepare`), sanitização de entrada, escaping de saída, prefixação e desinstalação sem destruir dados por padrão. Ainda assim, revise o código gerado antes de usar em produção.

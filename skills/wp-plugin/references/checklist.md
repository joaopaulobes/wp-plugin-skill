# Checklist de qualidade — antes de entregar

Passe por tudo antes de declarar o plugin "pronto".

## Segurança
- [ ] `defined( 'ABSPATH' ) || exit;` no topo de **todo** arquivo PHP.
- [ ] **Nonce** em toda ação que muda estado (forms, AJAX, links GET de ação).
- [ ] **Capability** (`current_user_can`) antes de toda ação sensível.
- [ ] **`$wpdb->prepare()`** em 100% das queries com entrada dinâmica.
- [ ] Entrada **sanitizada** (`sanitize_*`, `absint`, `esc_url_raw`, `wp_kses`) + `wp_unslash` antes.
- [ ] Saída **escapada** (`esc_html`, `esc_attr`, `esc_url`, `esc_js`, `wp_kses_post`).
- [ ] Nada de credenciais/segredos hardcoded.
- [ ] Privacidade: dados pessoais tratados com cuidado (ex.: IP só como hash).

## Estrutura
- [ ] Tudo prefixado (funções, options, tabelas, hooks, handles de script/estilo).
- [ ] Autoloader funciona; classes em `includes/class-{prefix}-*.php`.
- [ ] `register_activation_hook` cria tabelas/options; `maybe_upgrade` roda migração.
- [ ] `flush_rewrite_rules()` só na ativação/desativação (se houver rewrite).
- [ ] Assets só nas telas certas (checa `$hook`); versão = `MPL_VERSION` (cache-bust).
- [ ] `uninstall.php` presente e **gated** por setting (não apaga dados por padrão).

## i18n
- [ ] Strings em `__()`/`esc_html__()` etc., text domain = slug.
- [ ] `load_plugin_textdomain` no boot.
- [ ] `.pot` gerado; `.po/.mo` PT-BR (se aplicável) com `Plural-Forms`.

## Distribuição
- [ ] `readme.txt` com `Stable tag`, `Requires`, `Tested up to`, `Requires PHP`, Changelog.
- [ ] Header do arquivo principal completo e com a mesma versão da constante.
- [ ] `.zip` instalável: top-dir = slug, **sem** docs/landing; contém o arquivo principal.
- [ ] (se pedido) auto-update: cliente + JSON + guia de servidor + `publicar.sh`.
- [ ] (se pedido) landing + página de instalação.

## Sanidade técnica
- [ ] `php -l` limpo em todos os arquivos (se PHP disponível; senão, avisar).
- [ ] Testar com `WP_DEBUG` ligado (sem notices/warnings).
- [ ] Datetimes via `current_time('mysql')`; colunas `DATETIME NULL DEFAULT NULL`.
- [ ] Sem `flush_rewrite_rules`/queries pesadas em todo request.

## Entrega (resumo ao usuário)
- [ ] O que foi entregue (arquivos + zip + landing + auto-update).
- [ ] Como instalar (link da página de instalação / passos).
- [ ] Próximos passos e como publicar versões novas.

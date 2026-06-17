# WordPress Plugin Blueprint — mapa completo de arquitetura

Referência técnica para construir **qualquer** plugin de WordPress. Cobre a anatomia padrão, decisões de arquitetura, os subsistemas (admin, front, dados, REST, cron, blocos), segurança e distribuição. Pense nisto como o "esqueleto + cardápio": escolha os blocos que o plugin precisa.

> Convenções nesta doc: `{{PREFIX}}` = prefixo de classe (ex.: `Mpl`), `{{prefix}}` = prefixo de função/constante (ex.: `mpl`), `{{slug}}` = slug do plugin (ex.: `meu-plugin`).

---

## 1. Anatomia de um plugin

```
meu-plugin/
├── meu-plugin.php          # Arquivo PRINCIPAL: header + bootstrap (obrigatório)
├── uninstall.php           # Roda ao DELETAR o plugin (limpeza opcional de dados)
├── readme.txt              # Metadados no formato WordPress.org
├── index.php               # "<?php // Silence is golden." (anti-listagem de diretório)
├── includes/               # Classes PHP (uma responsabilidade por arquivo)
│   ├── class-mpl-install.php
│   ├── class-mpl-admin.php
│   └── ...
├── assets/
│   ├── css/  js/  img/
│   └── js/vendor/          # libs de terceiros (com licença preservada)
└── languages/
    ├── meu-plugin.pot
    └── meu-plugin-pt_BR.po / .mo
```

### O arquivo principal (header + bootstrap)

```php
<?php
/**
 * Plugin Name:       Meu Plugin
 * Plugin URI:        https://exemplo.com/meu-plugin
 * Description:       O que o plugin faz, em uma frase.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Fulano
 * Author URI:        https://exemplo.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       meu-plugin
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;                 // nunca executa fora do WP

define( 'MPL_VERSION', '1.0.0' );
define( 'MPL_DB_VERSION', '1.0.0' );           // versão do schema (controla migração)
define( 'MPL_FILE', __FILE__ );
define( 'MPL_DIR', plugin_dir_path( __FILE__ ) );
define( 'MPL_URL', plugin_dir_url( __FILE__ ) );
define( 'MPL_SLUG', 'meu-plugin' );

// Autoloader simples das classes Mpl_*
spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'Mpl_' ) !== 0 ) { return; }
    $file = MPL_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
    if ( is_readable( $file ) ) { require_once $file; }
} );

// Ativação / Desativação
register_activation_hook( __FILE__, function () {
    Mpl_Install::run();          // cria tabelas, define options padrão
    flush_rewrite_rules();       // se o plugin registra rewrites
} );
register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );

// Bootstrap
add_action( 'plugins_loaded', function () {
    Mpl_Install::maybe_upgrade();          // roda migração se a versão mudou
    // Front-end sempre:
    Mpl_Front::init();
    // Admin só no admin:
    if ( is_admin() ) {
        Mpl_Admin::init();
        Mpl_Updater::init();               // auto-update (se houver)
    }
    load_plugin_textdomain( 'meu-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );
```

**Pegadinhas do bootstrap:**
- `register_activation_hook` recebe `__FILE__` do arquivo principal — não funciona dentro de classes incluídas via autoload se você passar o arquivo errado.
- `flush_rewrite_rules()` é caro: só na ativação/desativação, **nunca** em todo request.
- Hooks que dependem de outros plugins → use `plugins_loaded`; que dependem do tema → `after_setup_theme` ou `init`.

---

## 2. Ciclo de vida & hooks (a "espinha")

WordPress é orientado a **hooks**: `add_action` (faz algo) e `add_filter` (transforma um valor). Ordem de disparo aproximada num request:

```
muplugins_loaded → plugins_loaded → setup_theme → after_setup_theme
→ init → wp_loaded → (parse_request → template_redirect [front])
→ (admin_menu → admin_init → admin_enqueue_scripts [admin])
→ wp / template_include → shutdown
```

- **`init`**: registrar CPT, taxonomias, shortcodes, rewrites, blocos.
- **`admin_menu`**: registrar páginas de admin.
- **`admin_init`**: registrar settings, processar lógica de admin.
- **`template_redirect`**: interceptar o front (redirects, rotas próprias). Rode em **prioridade 1** se precisar agir antes do `redirect_canonical` do core.
- **`wp_enqueue_scripts`** (front) / **`admin_enqueue_scripts`** (admin): carregar CSS/JS.

**Criar SEUS próprios hooks** (extensibilidade / white-label):
```php
$value = apply_filters( 'mpl_meu_filtro', $value, $contexto );   // deixa terceiros mudarem
do_action( 'mpl_meu_evento', $dados );                            // deixa terceiros reagirem
```

---

## 3. Onde guardar dados (decisão de arquitetura)

| Opção | Quando usar | API |
|---|---|---|
| **Options** | Configurações, flags, pouca coisa | `get_option`/`update_option`/`add_option` (ou Settings API) |
| **Transients** | Cache temporário (resultados de API, cálculos) | `get_transient`/`set_transient`/`delete_transient` |
| **User / Post meta** | Dado atrelado a um usuário/post | `get_user_meta`/`get_post_meta`/`update_*_meta` |
| **CPT + taxonomia** | "Conteúdo" editorial (eventos, produtos, depoimentos) que se beneficia do editor, busca, permalinks | `register_post_type`/`register_taxonomy` |
| **Tabelas próprias** | Volume alto, consultas específicas, dados não-editoriais (logs, cliques, filas) | `$wpdb` + `dbDelta` |

### Tabelas próprias + migração (padrão robusto)
```php
class Mpl_Install {
    public static function table( $name ) {
        global $wpdb; return $wpdb->prefix . 'mpl_' . $name;
    }
    public static function run() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $t = self::table( 'items' );
        dbDelta( "CREATE TABLE {$t} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(190) NOT NULL DEFAULT '',
            created_at DATETIME NULL DEFAULT NULL,   /* NUNCA '0000-00-00' (MySQL 8 strict) */
            PRIMARY KEY  (id),
            KEY title (title)
        ) {$charset};" );
        add_option( 'mpl_settings', array( 'delete_on_uninstall' => 0 ) );
        update_option( 'mpl_db_version', MPL_DB_VERSION );
    }
    public static function maybe_upgrade() {
        if ( get_option( 'mpl_db_version' ) !== MPL_DB_VERSION ) {
            self::run();   // dbDelta adiciona colunas/índices novos automaticamente
        }
    }
}
```
> **dbDelta é exigente:** dois espaços após `PRIMARY KEY`, uma definição de campo por linha, tipos em minúsculo coerentes. Mude `MPL_DB_VERSION` sempre que mudar o schema → a migração roda sozinha no próximo load.

**Toda query:** `$wpdb->prepare( "... WHERE id = %d AND slug = %s", $id, $slug )`. Nunca concatene entrada do usuário.

---

## 4. Camada de administração (back-end)

```php
class Mpl_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_mpl_save', array( __CLASS__, 'handle_save' ) );  // form normal
        add_action( 'wp_ajax_mpl_do', array( __CLASS__, 'ajax_do' ) );           // AJAX (logado)
    }
    public static function menu() {
        add_menu_page( 'Meu Plugin', 'Meu Plugin', 'manage_options', MPL_SLUG, array( __CLASS__, 'page' ), 'dashicons-admin-generic', 58 );
        add_submenu_page( MPL_SLUG, 'Config', 'Config', 'manage_options', MPL_SLUG . '-settings', array( __CLASS__, 'settings_page' ) );
    }
    public static function assets( $hook ) {
        if ( strpos( (string) $hook, MPL_SLUG ) === false ) { return; }   // só nas telas do plugin
        wp_enqueue_style( 'mpl-admin', MPL_URL . 'assets/css/admin.css', array(), MPL_VERSION );
        wp_enqueue_script( 'mpl-admin', MPL_URL . 'assets/js/admin.js', array(), MPL_VERSION, true );
        wp_localize_script( 'mpl-admin', 'MPL', array(
            'ajax'  => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'mpl_ajax' ),
        ) );
    }
}
```

**Formulário (admin-post):**
```php
// No HTML:  <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
//   <input type="hidden" name="action" value="mpl_save">
//   <?php wp_nonce_field( 'mpl_save', 'mpl_nonce' ); ?> ... </form>
public static function handle_save() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Sem permissão.' ); }
    check_admin_referer( 'mpl_save', 'mpl_nonce' );
    $title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
    // ...salvar...
    wp_safe_redirect( admin_url( 'admin.php?page=' . MPL_SLUG . '&msg=saved' ) ); exit;
}
```

**AJAX:**
```php
public static function ajax_do() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'forbidden', 403 ); }
    check_ajax_referer( 'mpl_ajax', 'nonce' );
    // ...trabalho...
    wp_send_json_success( array( 'ok' => true ) );
}
// JS: fetch(MPL.ajax, {method:'POST', body:new URLSearchParams({action:'mpl_do', nonce:MPL.nonce, ...})})
```

**Settings API** (página de configurações nativa): `register_setting` + `add_settings_section` + `add_settings_field` no `admin_init`, e `settings_fields()`/`do_settings_sections()` na página. (Ver `snippets.md`.)

**Capabilities:** use a capacidade certa (`manage_options` para admin geral, `edit_posts` para editores, ou crie uma capacidade própria com `add_cap`). Torne-a filtrável para white-label: `apply_filters( 'mpl_capability', 'manage_options' )`.

---

## 5. Camada de front-end

Opções, do mais simples ao mais envolvido:

- **Shortcode** — inserir conteúdo em posts/páginas: `add_shortcode( 'meu_sc', $cb )`. Sempre `return` (nunca `echo`) e escape a saída.
- **Bloco Gutenberg** — para o editor de blocos: `block.json` + `register_block_type()`. Blocos **dinâmicos** têm `render_callback` em PHP (sem build). Blocos com UI no editor exigem JS/JSX (build com `@wordpress/scripts`).
- **Widget / Block Widget** — `register_widget` (clássico) ou um bloco.
- **Rota/rewrite própria** — URLs como `site.com/go/algo`:
  ```php
  add_rewrite_rule( '^prefixo/([^/]+)/?$', 'index.php?mpl_slug=$matches[1]', 'top' );
  add_filter( 'query_vars', fn($v) => array_merge($v, ['mpl_slug']) );
  add_action( 'template_redirect', function () {
      $slug = get_query_var( 'mpl_slug' );
      if ( ! $slug ) { return; }
      // ...resolver e responder/redirecionar... exit;
  }, 1 );   // prioridade 1 = antes do redirect_canonical
  ```
  Lembre de `flush_rewrite_rules()` na ativação.
- **Enfileirar assets no front:** `wp_enqueue_scripts` (com `MPL_URL` + `MPL_VERSION` para cache-busting).

---

## 6. Subsistemas adicionais (conforme o escopo)

- **REST API** — endpoints próprios para JS/headless/integrações:
  ```php
  add_action( 'rest_api_init', function () {
      register_rest_route( 'mpl/v1', '/items', array(
          'methods'             => 'GET',
          'callback'            => 'mpl_rest_items',
          'permission_callback' => fn() => current_user_can( 'edit_posts' ),
      ) );
  } );
  ```
- **WP-Cron** — tarefas agendadas:
  ```php
  if ( ! wp_next_scheduled( 'mpl_daily' ) ) { wp_schedule_event( time(), 'daily', 'mpl_daily' ); }
  add_action( 'mpl_daily', 'mpl_run_daily' );
  // limpe no deactivation: wp_clear_scheduled_hook( 'mpl_daily' );
  ```
  > WP-Cron depende de tráfego; para precisão real, instrua o usuário a usar cron de sistema chamando `wp-cron.php`.
- **E-mail** — `wp_mail( $to, $subject, $body, $headers )` (HTML via header `Content-Type: text/html`).
- **API externa** — `wp_remote_get`/`wp_remote_post` (nunca `curl` cru); trate `is_wp_error` e `wp_remote_retrieve_response_code`. Cacheie com transient.
- **Background/lote** — para muitos itens, processe em lotes (AJAX/cron) ou use a lib Action Scheduler.
- **Multisite** — `get_site_option`/`update_site_option`, menus de network admin, ativação por rede.

---

## 7. Segurança (não-negociável)

Quatro pilares, sempre:
1. **Nonces** — toda ação que muda estado: `wp_nonce_field`/`check_admin_referer` (forms), `wp_create_nonce`/`check_ajax_referer` (AJAX), `wp_nonce_url`/`check_admin_referer` (links GET).
2. **Capabilities** — `current_user_can( ... )` antes de qualquer ação sensível.
3. **Queries preparadas** — `$wpdb->prepare()` em 100% das queries com entrada dinâmica.
4. **Sanitizar entrada / Escapar saída** — entrada: `sanitize_text_field`, `sanitize_email`, `absint`, `esc_url_raw`, `wp_kses`. Saída: `esc_html`, `esc_attr`, `esc_url`, `esc_js`, `wp_kses_post`.

Extra: `wp_unslash()` antes de sanitizar `$_POST`/`$_GET`; nunca confie em `$_REQUEST`; valide tipos (`(int)`, `in_array` para enums); privacidade/LGPD (ex.: guarde IP só como hash com sal).

---

## 8. Internacionalização (i18n)

- Strings-fonte em **inglês**, em `__()` / `esc_html__()` / `esc_attr__()` / `_n()` (plural) / `esc_html_e()`, com text domain = slug.
- `load_plugin_textdomain()` no boot.
- Gerar template: `wp i18n make-pot . languages/<slug>.pot`.
- Traduzir: copiar `.pot` → `<slug>-pt_BR.po`, traduzir, compilar com `msgfmt <slug>-pt_BR.po -o <slug>-pt_BR.mo`.
- Header `.po` precisa de `Language: pt_BR` e `Plural-Forms: nplurals=2; plural=(n > 1);` para `_n()` funcionar.

---

## 9. Distribuição & profissionalismo

- **`readme.txt`** (formato WordPress.org): `Stable tag`, `Requires at least`, `Tested up to`, `Requires PHP`, seções `== Description ==`, `== Installation ==`, `== Frequently Asked Questions ==`, `== Changelog ==`.
- **White-label**: nome/capacidade/endpoint de update filtráveis (`mpl_brand`, `mpl_capability`, `mpl_update_json_url`).
- **`uninstall.php`**: remoção de dados **gated** por setting (default off — não destrua dados sem consentimento).
- **Auto-update próprio**: ver `auto-update.md`.
- **Modal "Ver detalhes" rico**: as `sections` do JSON de update viram abas (Descrição/Instalação/FAQ/Telas/Changelog) + banner. Imagens devem ser **embarcadas no plugin** e ter URL reescrita para o caminho local (evita hotlink/CSP/CDN). Ver `auto-update.md`.
- **Landing page** + página de instalação: ver `landing.md`.

---

## 10. Onde NÃO reinventar / pegadinhas comuns

- Use as **APIs do WP** (`wp_remote_*`, `wp_mail`, `$wpdb`, Settings, REST) em vez de soluções cruas.
- **Não** rode `flush_rewrite_rules()` em todo request.
- **Não** carregue assets em todas as telas do admin (cheque o `$hook`).
- **Não** use `date('Y-m-d')` para horário do site — use `current_time('mysql')` / `wp_date()`.
- Datetime de tabela: `DATETIME NULL DEFAULT NULL` (MySQL 8 strict rejeita `0000-00-00`).
- Prefixe **tudo** (funções globais, opções, tabelas, hooks, handles de script) para evitar colisão.
- Teste com `WP_DEBUG` ligado; rode `php -l` em cada arquivo.

---

### Mapa de decisão rápido
- "Guardar config" → **options/Settings API**
- "Guardar muitos registros/logs" → **tabela própria + dbDelta**
- "Conteúdo editável tipo post" → **CPT**
- "Mostrar algo num post" → **shortcode** ou **bloco**
- "URL própria/redirect" → **rewrite + template_redirect(1)**
- "Tela de gestão" → **admin_menu + admin-post/AJAX**
- "Integração JS/externa" → **REST API**
- "Tarefa periódica" → **WP-Cron** (+ aviso de cron de sistema)
- "Distribuir/vender" → **i18n + readme.txt + uninstall + auto-update + landing**

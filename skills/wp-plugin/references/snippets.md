# Snippets prontos — por padrão

Trechos copiáveis para cada padrão comum. Troque `Mpl`/`mpl`/`meu-plugin` pelos prefixos/slug reais. Todos seguem as regras de segurança do `checklist.md`.

---

## Settings API (página de configurações nativa)

```php
// no admin_init
register_setting( 'mpl_group', 'mpl_settings', array( __CLASS__, 'sanitize_settings' ) );
add_settings_section( 'mpl_main', 'Geral', '__return_false', 'mpl-settings' );
add_settings_field( 'api_key', 'Chave de API', array( __CLASS__, 'field_api_key' ), 'mpl-settings', 'mpl_main' );

public static function field_api_key() {
    $o = get_option( 'mpl_settings', array() );
    printf( '<input type="text" name="mpl_settings[api_key]" value="%s" class="regular-text">', esc_attr( $o['api_key'] ?? '' ) );
}
public static function sanitize_settings( $in ) {
    return array( 'api_key' => sanitize_text_field( $in['api_key'] ?? '' ) );
}
// na página:
?>
<form method="post" action="options.php">
    <?php settings_fields( 'mpl_group' ); do_settings_sections( 'mpl-settings' ); submit_button(); ?>
</form>
```

---

## Custom Post Type + taxonomia

```php
add_action( 'init', function () {
    register_post_type( 'mpl_item', array(
        'labels'       => array( 'name' => 'Itens', 'singular_name' => 'Item' ),
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-star-filled',
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite'      => array( 'slug' => 'itens' ),
        'show_in_rest' => true,   // habilita Gutenberg + REST
    ) );
    register_taxonomy( 'mpl_cat', 'mpl_item', array(
        'labels'       => array( 'name' => 'Categorias de Item' ),
        'hierarchical' => true,
        'show_in_rest' => true,
    ) );
} );
// ⚠️ flush_rewrite_rules() na ativação para os permalinks funcionarem.
```

### Meta box (campo extra no editor)
```php
add_action( 'add_meta_boxes', function () {
    add_meta_box( 'mpl_meta', 'Detalhes', function ( $post ) {
        wp_nonce_field( 'mpl_meta', 'mpl_meta_nonce' );
        $v = get_post_meta( $post->ID, '_mpl_preco', true );
        printf( '<label>Preço <input type="number" step="0.01" name="mpl_preco" value="%s"></label>', esc_attr( $v ) );
    }, 'mpl_item' );
} );
add_action( 'save_post_mpl_item', function ( $post_id ) {
    if ( ! isset( $_POST['mpl_meta_nonce'] ) || ! wp_verify_nonce( $_POST['mpl_meta_nonce'], 'mpl_meta' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    update_post_meta( $post_id, '_mpl_preco', floatval( $_POST['mpl_preco'] ?? 0 ) );
} );
```

---

## Shortcode

```php
add_shortcode( 'meu_botao', function ( $atts, $content = null ) {
    $a = shortcode_atts( array( 'url' => '', 'cor' => '#4e2783' ), $atts, 'meu_botao' );
    $texto = $content ? do_shortcode( $content ) : 'Clique aqui';
    return sprintf(
        '<a class="mpl-btn" href="%s" style="background:%s">%s</a>',
        esc_url( $a['url'] ), esc_attr( $a['cor'] ), wp_kses_post( $texto )
    );
} );
// uso: [meu_botao url="https://..."]Comprar[/meu_botao]
```

---

## Bloco Gutenberg dinâmico (sem build, render em PHP)

`blocks/destaque/block.json`:
```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "mpl/destaque",
  "title": "Destaque",
  "category": "widgets",
  "icon": "star-filled",
  "attributes": { "texto": { "type": "string", "default": "Olá" } },
  "render": "file:./render.php"
}
```
PHP:
```php
add_action( 'init', function () {
    register_block_type( MPL_DIR . 'blocks/destaque' );
} );
// blocks/destaque/render.php:
// <?php echo '<div class="mpl-destaque">' . esc_html( $attributes['texto'] ) . '</div>';
```
> Blocos com **UI no editor** (controles) exigem JS/JSX compilado com `@wordpress/scripts` (`npx wp-scripts build`). Blocos só de saída dinâmica não precisam de build.

---

## Rewrite + rota própria (URL bonita / redirect)

```php
class Mpl_Front {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'rewrite' ) );
        add_filter( 'query_vars', fn( $v ) => array_merge( $v, array( 'mpl_go' ) ) );
        add_action( 'template_redirect', array( __CLASS__, 'handle' ), 1 );
    }
    public static function rewrite() {
        add_rewrite_rule( '^go/([^/]+)/?$', 'index.php?mpl_go=$matches[1]', 'top' );
    }
    public static function handle() {
        $slug = get_query_var( 'mpl_go' );
        if ( ! $slug ) { return; }
        // resolver $slug → destino; pode 404 com status_header(404) ou redirecionar:
        wp_redirect( 'https://destino', 302 ); exit;
    }
}
// flush_rewrite_rules() na ativação (depois de registrar a regra).
```

---

## REST API

```php
add_action( 'rest_api_init', function () {
    register_rest_route( 'mpl/v1', '/items', array(
        array(
            'methods'             => WP_REST_Server::READABLE,        // GET
            'permission_callback' => fn() => current_user_can( 'edit_posts' ),
            'callback'            => function ( WP_REST_Request $req ) {
                $page = absint( $req->get_param( 'page' ) ?: 1 );
                return rest_ensure_response( array( 'page' => $page, 'items' => array() ) );
            },
        ),
        array(
            'methods'             => WP_REST_Server::CREATABLE,       // POST
            'permission_callback' => fn() => current_user_can( 'manage_options' ),
            'args'                => array( 'title' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ) ),
            'callback'            => fn( $req ) => rest_ensure_response( array( 'ok' => true ) ),
        ),
    ) );
} );
// JS (admin/front): apiFetch({ path: '/mpl/v1/items', method: 'POST', data: {...} })  // nonce wp_rest é automático no admin
```

---

## WP-Cron (tarefa agendada)

```php
register_activation_hook( MPL_FILE, function () {
    if ( ! wp_next_scheduled( 'mpl_daily' ) ) {
        wp_schedule_event( time() + 60, 'daily', 'mpl_daily' );
    }
} );
register_deactivation_hook( MPL_FILE, fn() => wp_clear_scheduled_hook( 'mpl_daily' ) );
add_action( 'mpl_daily', function () { /* trabalho diário */ } );

// intervalo custom:
add_filter( 'cron_schedules', function ( $s ) {
    $s['mpl_15min'] = array( 'interval' => 900, 'display' => 'A cada 15 min' );
    return $s;
} );
```

---

## Chamada a API externa (com cache)

```php
function mpl_fetch_data() {
    $cached = get_transient( 'mpl_data' );
    if ( false !== $cached ) { return $cached; }
    $res = wp_remote_get( 'https://api.exemplo.com/x', array( 'timeout' => 12 ) );
    if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) { return null; }
    $data = json_decode( wp_remote_retrieve_body( $res ), true );
    set_transient( 'mpl_data', $data, HOUR_IN_SECONDS );
    return $data;
}
```

---

## Capability / white-label filtráveis

```php
function mpl_capability() { return apply_filters( 'mpl_capability', 'manage_options' ); }
function mpl_brand()      { return apply_filters( 'mpl_brand', 'Meu Plugin' ); }
// uso: current_user_can( mpl_capability() ) ; echo esc_html( mpl_brand() );
```

---

## uninstall.php (limpeza opcional)

```php
<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
$s = get_option( 'mpl_settings', array() );
if ( empty( $s['delete_on_uninstall'] ) ) { return; }   // default: preserva dados
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mpl_items" );
delete_option( 'mpl_settings' );
delete_option( 'mpl_db_version' );
```

---

## readme.txt (esqueleto WordPress.org)

```
=== Meu Plugin ===
Contributors: seuusuario
Tags: links, redirect, analytics
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Descrição curta (até ~150 caracteres).

== Description ==
Texto completo do que o plugin faz.

== Installation ==
1. Envie o .zip em Plugins → Adicionar plugin → Enviar plugin.
2. Ative.
3. Acesse o menu do plugin.

== Frequently Asked Questions ==
= Pergunta? =
Resposta.

== Changelog ==
= 1.0.0 =
* Lançamento inicial.
```

---

## i18n — gerar e compilar

```bash
wp i18n make-pot . languages/meu-plugin.pot          # extrai as strings
cp languages/meu-plugin.pot languages/meu-plugin-pt_BR.po  # traduzir o .po
msgfmt languages/meu-plugin-pt_BR.po -o languages/meu-plugin-pt_BR.mo
```
Header do `.po` precisa de: `"Language: pt_BR\n"` e `"Plural-Forms: nplurals=2; plural=(n > 1);\n"`.

---

## Empacotar o .zip de instalação

```bash
cd <pasta-pai-do-plugin>
zip -rqX meu-plugin.zip meu-plugin \
  -x "meu-plugin/.git/*" "meu-plugin/docs/*" "meu-plugin/landing/*" \
     "meu-plugin/scripts/*" "meu-plugin/README.md" '*/.DS_Store' '*.bak'
# valide: o zip deve conter meu-plugin/meu-plugin.php e NÃO conter landing/docs
unzip -l meu-plugin.zip | grep meu-plugin.php
```

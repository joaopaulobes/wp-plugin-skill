# Auto-update via servidor próprio

Faz o plugin avisar "atualização disponível" no painel e atualizar com 1 clique — igual a qualquer plugin do diretório — a partir de um **servidor de updates que você controla**. Funciona em qualquer hospedagem (basta servir 2 arquivos estáticos). Essencial para distribuir/revender.

## Como funciona (visão geral)

```
   [WordPress do cliente]                       [Seu servidor de updates]
   plugin instalado v1.0.0   ──── consulta ───►  meu-plugin.json  (versão, changelog, URL do zip)
        Mpl_Updater                              meu-plugin.zip   (pacote da última versão)
        │
        └─ se versão do JSON > instalada → WP mostra "atualizar agora" → baixa o .zip
```

- O **cliente** (classe `Mpl_Updater`, dentro do plugin) consulta o JSON, compara versões e injeta a atualização no WordPress.
- O **servidor** é só 2 arquivos estáticos: o `.json` (metadados) e o `.zip` (pacote). Qualquer nginx/Apache/CDN serve.

---

## 1. O cliente no plugin (`includes/class-mpl-updater.php`)

```php
<?php
defined( 'ABSPATH' ) || exit;

class Mpl_Updater {
    const JSON_URL  = 'https://SEU-SERVIDOR/updates/meu-plugin.json';   // ← troque
    const CACHE_KEY = 'mpl_update_info';
    const CACHE_TTL = 6 * HOUR_IN_SECONDS;

    public static function init() {
        add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check' ) );
        add_filter( 'plugins_api', array( __CLASS__, 'info' ), 20, 3 );
        add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ), 10, 0 );
    }

    private static function url() {
        return apply_filters( 'mpl_update_json_url', self::JSON_URL );   // white-label
    }

    private static function remote() {
        $c = get_transient( self::CACHE_KEY );
        if ( false !== $c ) { return is_array( $c ) ? $c : null; }
        $url = add_query_arg( 'nc', rawurlencode( MPL_VERSION ), self::url() );
        $res = wp_remote_get( $url, array( 'timeout' => 12, 'headers' => array( 'Accept' => 'application/json' ) ) );
        if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
            set_transient( self::CACHE_KEY, 'none', HOUR_IN_SECONDS ); return null;
        }
        $data = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( ! is_array( $data ) || empty( $data['version'] ) ) {
            set_transient( self::CACHE_KEY, 'none', HOUR_IN_SECONDS ); return null;
        }
        set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
        return $data;
    }

    public static function check( $transient ) {
        if ( ! is_object( $transient ) || empty( $transient->checked ) ) { return $transient; }
        $info = self::remote();
        if ( ! $info ) { return $transient; }
        $base = plugin_basename( MPL_FILE );   // meu-plugin/meu-plugin.php
        $installed = $transient->checked[ $base ] ?? MPL_VERSION;
        $obj = array(
            'slug' => MPL_SLUG, 'plugin' => $base, 'new_version' => $info['version'],
            'url' => $info['homepage'] ?? '', 'package' => $info['download_url'] ?? '',
            'tested' => $info['tested'] ?? '', 'requires' => $info['requires'] ?? '',
            'requires_php' => $info['requires_php'] ?? '',
        );
        if ( version_compare( $info['version'], $installed, '>' ) && ! empty( $obj['package'] ) ) {
            $transient->response[ $base ] = (object) $obj;
        } else {
            $transient->no_update[ $base ] = (object) $obj;
        }
        return $transient;
    }

    public static function info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || MPL_SLUG !== $args->slug ) { return $result; }
        $info = self::remote();
        if ( ! $info ) { return $result; }

        // As imagens do JSON também vão embarcadas no plugin (assets/img/). Reescreve a
        // URL para o caminho LOCAL → o modal carrega do próprio site (sem hotlink/CDN/CSP).
        $remote_base = 'https://SEU-SERVIDOR/updates/img/';   // ← prefixo das imagens no JSON
        $local_base  = plugins_url( 'assets/img/', MPL_FILE );
        $loc = fn( $h ) => is_string( $h ) ? str_replace( $remote_base, $local_base, $h ) : $h;

        $sections = array_map( $loc, (array) ( $info['sections'] ?? array() ) );
        $banners  = array_map( $loc, (array) ( $info['banners'] ?? array() ) );

        return (object) array(
            'name' => $info['name'] ?? mpl_brand(), 'slug' => MPL_SLUG, 'version' => $info['version'],
            'author' => $info['author'] ?? '', 'homepage' => $info['homepage'] ?? '',
            'requires' => $info['requires'] ?? '', 'tested' => $info['tested'] ?? '',
            'requires_php' => $info['requires_php'] ?? '', 'last_updated' => $info['last_updated'] ?? '',
            'download_link' => $info['download_url'] ?? '', 'sections' => $sections, 'banners' => $banners,
        );
    }

    public static function clear_cache() { delete_transient( self::CACHE_KEY ); }
}
```
No boot (admin): `Mpl_Updater::init();`.

---

## 2. O JSON de metadados (`meu-plugin.json`)

```json
{
  "name": "Meu Plugin",
  "slug": "meu-plugin",
  "version": "1.1.0",
  "author": "<a href=\"https://exemplo.com\">Fulano</a>",
  "homepage": "https://exemplo.com/meu-plugin",
  "requires": "6.0",
  "tested": "7.0",
  "requires_php": "7.4",
  "download_url": "https://SEU-SERVIDOR/updates/meu-plugin.zip",
  "last_updated": "2026-01-01 12:00:00",
  "sections": {
    "description": "<h3>...</h3><p>...</p><img src=\"https://SEU-SERVIDOR/updates/img/tela.jpg\">",
    "installation": "<ol><li>...</li></ol>",
    "faq": "<h4>Pergunta?</h4><p>Resposta.</p>",
    "screenshots": "<ol><li>...</li></ol>",
    "changelog": "<h4>1.1.0</h4><ul><li>...</li></ul>"
  },
  "banners": { "low": "https://SEU-SERVIDOR/updates/img/banner-772x250.jpg", "high": "https://SEU-SERVIDOR/updates/img/banner-1544x500.jpg" }
}
```
> As chaves de `sections` viram **abas** no modal "Ver detalhes" (Descrição/Instalação/FAQ/Telas/Registro de alterações). `banners` é o cabeçalho colorido do modal.

---

## 3. Onde hospedar

Qualquer lugar que sirva 2 arquivos estáticos por HTTPS:
```
https://SEU-SERVIDOR/updates/
├── meu-plugin.json      (Content-Type: application/json)
├── meu-plugin.zip       (Content-Type: application/zip)
└── img/                 (prints + banners usados no modal — opcional)
```
Exemplos: subpasta de um site (nginx/Apache estático), um bucket S3/R2 público, GitHub Releases/Pages, etc. **Não precisa de PHP no servidor de updates.**

---

## 4. Publicar uma versão (use o `templates/publicar.sh`)

Fluxo a cada release:
1. **Bump** da versão em 3 lugares: `MPL_VERSION` (constante), header `Version:` do arquivo principal, `Stable tag:` do `readme.txt` — e adicione a entrada no `== Changelog ==`.
2. Rode `./scripts/publicar.sh` → ele builda o `.zip` limpo, gera o `.json` (com changelog extraído do `readme.txt`), sobe os dois para o servidor e valida via HTTPS.
3. (Se você administra sites com o plugin) faça o deploy do **código** neles. Os demais clientes atualizam sozinhos (em até 6h, ou na hora via "verificar novamente").

---

## 5. ⚠️ Pegadinhas (aprendidas na prática)

- **Imagens no modal "Ver detalhes" + hotlink/CDN/CSP** → se as imagens do JSON apontam para outro domínio, o WordPress do cliente pode não carregá-las (Cloudflare *Hotlink Protection*, CSP, etc. dão 403 com Referer externo). **Solução robusta:** embarque as imagens no plugin (`assets/img/`) e faça o `info()` reescrever a URL para o caminho local (já está no cliente acima). Funciona em qualquer cliente.
- **`open_file_cache` do nginx (~60s)** → depois de trocar o `.json`/`.zip`, o nginx pode servir o tamanho/conteúdo antigo por até ~60s (corpo truncado / versão anterior), mesmo com upload atômico (o cache é por caminho+tempo, não por inode). O `publicar.sh` valida com paciência (~80s) e, se não confirmar, **avisa** em vez de falhar (o arquivo no disco já está correto).
- **Upload atômico** → suba para `arquivo.tmp` e dê `mv` no servidor. O rename é atômico → o nginx nunca serve um arquivo no meio da escrita (que truncaria o JSON ou corromperia o zip).
- **CDN cacheando o JSON** → garanta que o `.json` não fique cacheado pelo CDN (no Cloudflare, `.json` costuma ser `DYNAMIC`/não-cacheado por padrão). O `?nc=` no cliente ajuda, mas o decisivo é o servidor não cachear o JSON. O `.zip` pode cachear (é versionado).
- **`set -o pipefail` + `grep -q`** (no script) → `unzip -l | grep -q` pode falsar o pipeline (grep fecha o pipe → unzip SIGPIPE). Use here-string: `grep -q ... <<<"$LISTA"`.
- **Pasta dentro do zip** → o top-dir do zip deve ser **igual ao slug do plugin** (`meu-plugin/`), senão o WordPress instala numa pasta com nome errado.

---

## 6. Alternativa: WordPress.org

Se for um plugin gratuito e público, dá para publicar no diretório oficial (SVN) e ganhar auto-update + descoberta de graça — mas passa por revisão e vira aberto. Para produto white-label/privado, o **servidor próprio** acima é melhor.

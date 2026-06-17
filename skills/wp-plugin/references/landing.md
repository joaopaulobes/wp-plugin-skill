# Landing page + página de instalação + modal "Ver detalhes"

Como gerar os materiais de marketing/instalação do plugin (padrão profissional).

## 1. Landing page (`landing/index.html`)

Parta de `templates/landing/index.html` (autocontida, responsiva, dark-mode automático). Estrutura recomendada:

1. **Header** fixo: logo + nome + nav âncoras + CTA "Baixar grátis".
2. **Hero**: headline com gradiente da marca + subtítulo + CTA de download + print do plugin.
3. **Faixa de confiança** (4 itens: leve / privado / sem deps / atualizável).
4. **Recursos** (grid de cards, ~9).
5. **Showcase** (telas alternadas: claro/escuro/lista/analytics).
6. **Como funciona** (3 passos).
7. **Faixa white-label / CTA** (gradiente).
8. **FAQ** (acordeão `<details>`).
9. **CTA final** + **Rodapé** ("por <marca>").

- Cores: use a paleta da marca do plugin (CSS custom properties + `prefers-color-scheme`).
- O botão de download aponta para o `.zip` (`<a href="meu-plugin.zip" download>`).
- Coloque o `.zip` ao lado do `index.html` (ou numa subpasta) para o download funcionar.

## 2. Página de instalação (`landing/instalar/index.html`)

Guia passo a passo ilustrado:
- **Passo 0**: caixa de destaque com botão "Baixar (.zip)".
- **Passos 1–5**: numerados com badges — Plugins → Adicionar plugin → Enviar plugin → Escolher arquivo → Instalar agora → Ativar → usar.
- **Método alternativo** (FTP/cPanel) + **requisitos** + **FAQ** (limite de upload, etc.).
- Use **screenshots reais** da tela "Enviar plugin" e do menu do plugin (ver abaixo).

## 3. Capturar screenshots (se houver site)

Se o plugin estiver instalado em algum WordPress acessível, capture telas reais com um navegador headless (Playwright/Puppeteer):

```js
// login no wp-admin → navega → screenshot do elemento do app (sem o chrome do WP)
await page.goto(SITE + '/wp-admin/admin.php?page=meu-plugin');
const el = await page.$('#mpl-app');            // wrapper da UI do plugin
await el.screenshot({ path: 'grid-light.png' });
// telas de instalação:
await page.goto(SITE + '/wp-admin/plugin-install.php?tab=upload');  // "Enviar plugin"
```
Converta para JPG (~620–1300px) para a landing/modal. Se **não** houver site, deixe placeholders e instrua o usuário a capturar depois.

## 4. Modal "Ver detalhes" rico

As abas do modal vêm das `sections` do JSON de update (ver `auto-update.md`). Para ficar profissional:
- Escreva HTML rico em `sections.description` (com `<img>`), `installation`, `faq`, `screenshots`; o `changelog` sai do `readme.txt`.
- **Imagens embarcadas no plugin** (`assets/img/modal/*.jpg`, ~620px) + **banner** (`assets/img/banner-772x250.jpg` e `1544x500.jpg`). O cliente `info()` reescreve a URL para o caminho local (resolve hotlink/CSP/CDN).
- `tested` = versão atual do WP (ex.: `7.0`) para sumir o aviso "não foi testado".
- Use só tags permitidas pelo `wp_kses` do modal (a, img[src,alt], h1–h4, p, ul/ol/li, strong, em, code, blockquote) — **sem** `style`/`width` (são removidos); dimensione as imagens no arquivo.

## 5. Favicon

Gere um **favicon quadrado** (`landing/img/favicon.webp`, ~256px) com a marca do plugin (glyph/ícone num quadrado arredondado com o gradiente da marca). Dá para renderizar um HTML simples e exportar webp direto pelo navegador headless (`screenshot({type:'webp'})`). O template da landing já referencia:
```html
<link rel="icon" type="image/webp" href="img/favicon.webp">
<link rel="apple-touch-icon" href="img/favicon.webp">
```
Em subpáginas (ex.: `instalar/`), use `../img/favicon.webp`.

## 6. Banner da marca

Gere os banners (1544×500 e 772×250) com o gradiente/logo da marca. Dá para renderizar um HTML simples com a marca e capturar via navegador headless, depois exportar JPG.

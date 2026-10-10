# Assumptions, manual steps, differences and limitations

This file lists what was manual, what differs from `sample-input.html`, and what is limited.

## 1. What was done manually (or by a hand-written script)

| Step | How it was done |
|---|---|
| Drupal CMS + Canvas install | `ddev composer create-project drupal/cms`, then `drush site:install ../recipes/drupal_cms_site_template_base` (the **Blank** site template). |
| Design tokens | Hand-written in `css/tokens.css` from the `:root` variables and the hard-coded values in `sample-input.html`. |
| 14 SDC components | Hand-written (`.component.yml`, `.twig`, `.css`). Not generated. |
| Theme enable + default | `drush theme:enable galvus_ds` and `drush config:set system.theme default galvus_ds`. |
| Hero illustration | The `data:` URI SVG in the source was copied into a file (`components/hero/hero-illustration.svg`). The build script uploads it as Media item 1 ("Hero illustration", type `svg_image`). |
| Page template + page | Built by `scripts/build-galvus-page.php`, not by drag-and-drop in the Canvas UI. **The text in that script was copied from `sample-input.html` by hand.** No HTML parser reads the source file. |
| Default page template | The script sets `canvas.settings:default_page_variant = galvus_default` directly in config. |
| Output JSON | `scripts/export-component-tree.php` reads the saved page and template and writes `output-components.json`. It is not hand-written. |
| Visual check | Manual: in a browser at 1280, 900 and 375 px wide, the position, size, font, colour, padding and grid of 37 elements were measured on the source and on the Drupal page and compared. The admin toolbar was hidden with JavaScript in that browser tab only, so both pages had the same width. |

**Important:** this prototype shows the *target* side of the pipeline (governed components, page template,
stored component tree, rendering). The step "AI-generated HTML → component tree" is represented
by a hand-written mapping script. It is not an automated transformer.

## 2. Differences from sample-input.html

### Structure / markup
1. **Repeating items are child components in slots**, not array props. Nav links, FAQ items, cards and
   footer links are separate component instances (Canvas 1.12 cannot store `[{label, url}]` props).
   The HTML output is the same element structure (`<li><a>`, `<details>`, …).
2. **Extra helper components** not in the brief: `section`, `nav_link`, `faq_item`, `resources_grid`,
   `main_content`.
3. **Drupal adds markup that the source does not have:** a `div.dialog-off-canvas-main-canvas` wrapper around
   header/main/footer, a hidden messages `div`, `data-component-id` attributes, Drupal's own
   `<head>` tags, and aggregated CSS/JS. Visitors cannot see any of this.
4. **`<main id="main">` also has `tabindex="-1"`** so focus moves there when the skip link is used. The source has no tabindex.
5. **Footer links are wrapped in `<nav aria-label="Footer">`** (the source uses a bare `<ul>`). Layout is unchanged.
6. **Card icons** use `stroke="currentColor"` with the colour set in CSS (the source hard-codes
   `stroke="#0b6b6d"`). The rendered colour is the same.
7. **Resource-card arrow** (`→`, CSS `::after`) has empty alt text (`content: " \2192" / ""`) so screen
   readers do not announce it. It looks the same.

### Content / URLs
8. **`href="#"` placeholders were replaced**, because Canvas rejects `#` as a URL:
   resource cards → `/patient-guide`, `/healthy-eating-planner`, `/contact`; CTA → `/join`;
   footer → `/privacy`, `/terms`, `/contact`, `/accessibility`. **None of these pages exist (404).**
9. **Hero image `src`** is a media file URL, not the inline `data:` URI. Same picture, alt text, width and height.
10. **Browser tab title** is `Living well with type 2 diabetes | Drush Site-Install`.
    The source is `Galvus Patient Support | Living well with type 2 diabetes`. The site name was left at the
    installer default.
11. **The page is at `/page/1`.** The site homepage (`/`) still shows the login page.

### Measured visual result
- 1280 px: all 37 measured elements match the source in position, size, font, colours, padding,
  margins, radius, borders, shadows and grid columns.
- 900 px and 375 px: sizes, fonts, grid columns, padding and the mobile nav behaviour match. The only
  difference measured was a 49 px vertical offset caused by the **admin** mobile toolbar. That toolbar
  is not in the HTML that anonymous visitors get (checked with `curl`).
- Document height differs by 1 px at 1280 px (2958 vs 2957). The cause was not investigated.
- Not measured: hover states, focus rings, animation timing, print styles, and browsers other than
  the Chromium-based preview.

## 3. Known limitations

- **No automated HTML → Canvas transformer.** See section 1.
- **`main_content` is visible in the Canvas library.** Authors could place it inside page content and create a
  second `<main>`. Nothing prevents this yet.
- **Mobile navigation hides the nav links with no menu button** below 760 px. This is copied from the source.
  It is an accessibility gap.
- **Textarea props** (lead, text, answer, intro) render as one paragraph. Line breaks are not kept.
- **`maxLength` limits** (40/60/80/120/160 characters) were chosen for this prototype. They are not from a brand guideline.
- **Breakpoints (960/760 px)** are written directly in each component's CSS, because CSS variables cannot be used in
  `@media` conditions.
- **Fonts** load from Google Fonts, as in the source. They are not self-hosted, which may matter for privacy/GDPR.
- **Section backgrounds** of resource cards depend on card order (`:nth-child`), as in the source.
- **`ddev drush …` does not work on this Windows machine** because Git Bash is not installed.
  Every Drush command was run as `ddev exec drush …`. On macOS/Linux, or Windows with Git Bash,
  `ddev drush` works too.
- **HTTPS:** the local certificate is not trusted (`mkcert -install` was not run). Use the `http://` URL.
- **Config location:** config was exported to `/config` with `--destination`. DDEV's default
  sync directory (`web/sites/default/files/sync`) is git-ignored. Import with `--source` (see README).

## 4. Contents of the database dump

`galvus-db.sql.gz` is a complete dump of the working site. It includes:
- user 1 `admin` (`admin@example.com`) with a password hash. **Log in with `drush user:login`
  and change the password.** Do not reuse the dump on a public server.
- `canvas_page` 1 "Living well with type 2 diabetes" (published), the built page.
- `canvas_page` 2 "Untitled page" (unpublished, empty). Left over from checking Canvas → New page.
- node 1 "Privacy policy" (unpublished), created by the Drupal CMS Blank template.
- Media 1 "Hero illustration". Its file is in `galvus-files.tar.gz`, not in the database.

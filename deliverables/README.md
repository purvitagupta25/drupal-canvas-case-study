# Galvus patient landing page: Drupal Canvas proof of concept

`sample-input.html` (an AI-generated patient landing page) rebuilt in **Drupal CMS 2 + Drupal Canvas**
with a governed design-system theme (`galvus_ds`) of Canvas-compatible Single Directory Components.

- Header and footer are in a **Canvas page template**, outside `<main>`.
- The page itself contains Hero, Benefits, FAQ, Resources and CTA, in that order.
- Text is stored as typed, plain-text component props. Authors edit the page in the Canvas editor.

Architecture write-up: [docs/architecture-ai-website-creation-drupal-canvas.pdf](../docs/architecture-ai-website-creation-drupal-canvas.pdf) ·
Demo walkthrough video: [docs/demo-walkthrough.mp4](../docs/demo-walkthrough.mp4)

See also: [component-mapping.md](component-mapping.md) ·
[output-components.json](output-components.json) · [assumptions.md](assumptions.md)

## 1. Requirements

| Tool | Version used | Notes |
|---|---|---|
| Docker Desktop | 4.94 (engine 29.8) | On Windows: WSL2 backend; virtualization enabled in BIOS |
| DDEV | 1.25.4 | https://ddev.com/get-started/ |
| Git Bash (Windows only, optional) | – | Needed for `ddev drush …`. Without it, use `ddev exec drush …` (works everywhere) |

Everything else (PHP 8.4, MariaDB 11.8, Composer, Drush 13) runs inside the DDEV containers.

Stack: Drupal core 11.4.8, Drupal CMS (Blank site template), Drupal Canvas 1.12.0.

## 2. Setup (restore the built site from the database dump)

Run from the unzipped project folder.

```bash
# 1. Start the containers (reads .ddev/config.yaml)
ddev start

# 2. Install PHP dependencies exactly as locked (core, Canvas, recipes, scaffold files)
ddev composer install

# 3. Import the database (site, page template, page, media entity, admin user)
ddev import-db --file=deliverables/galvus-db.sql.gz

# 4. Import public files (hero illustration SVG and Drupal CMS default images)
ddev import-files --source=deliverables/galvus-files.tar.gz

# 5. Check that the exported config matches the database (expect "no changes")
ddev exec drush config:import --source=/var/www/html/config --yes

# 6. Rebuild caches
ddev exec drush cache:rebuild

# 7. Get a one-time admin login link
ddev exec drush user:login
```

`ddev drush uli` is the same as step 7 when Git Bash is available, or on macOS/Linux.

**Project name:** `.ddev/config.yaml` does not set a name, so DDEV uses the folder name. After
`git clone` the folder is `drupal-canvas-case-study`, which gives the URLs below. If you clone into a
different folder, replace `drupal-canvas-case-study` in the URLs with that folder name.

## 3. Where to look

| What | URL / path |
|---|---|
| Built page | http://drupal-canvas-case-study.ddev.site/page/1 |
| Canvas editor for the page | http://drupal-canvas-case-study.ddev.site/canvas/editor/canvas_page/1 |
| Canvas page template (header/main/footer) | http://drupal-canvas-case-study.ddev.site/canvas/editor/page_variant/galvus_default |
| Component library | Canvas editor → **+** (Library) → folder **Galvus Design System** |
| Component status / errors | http://drupal-canvas-case-study.ddev.site/admin/appearance/component/status |
| Log in | `ddev exec drush user:login` (or `ddev drush uli`) |

Use `http://`, not `https://`, unless you have run `mkcert -install`.
When logged in, the Drupal admin toolbar narrows the page. To see the page as a visitor, open it in a private window.

## 4. Rebuild the page from the components (optional)

`scripts/build-galvus-page.php` creates the "Hero illustration" media item and the `galvus_default`
page template (set as site default), then fills `canvas_page` 1. Running it again rebuilds the
page tree and template.

```bash
ddev exec drush php:script /var/www/html/scripts/build-galvus-page.php
```

`scripts/export-component-tree.php` writes the stored component trees to
`deliverables/output-components.json`:

```bash
ddev exec drush php:script /var/www/html/scripts/export-component-tree.php
```

## 5. Folder map

```
.
├── .ddev/config.yaml                  DDEV project (drupal11, docroot web, PHP 8.4)
├── composer.json / composer.lock      Drupal CMS + Canvas, locked versions
├── config/                            Full config export (drush config:export --destination)
│   ├── canvas.page_variant.galvus_default.yml   page template (header / main / footer)
│   ├── canvas.component.sdc.galvus_ds.*.yml     14 Canvas Component entities
│   ├── canvas.settings.yml            default_page_variant: galvus_default
│   └── system.theme.yml               default: galvus_ds
├── deliverables/
│   ├── README.md                      this file
│   ├── component-mapping.md           source section → component → props
│   ├── output-components.json         stored component trees (exported, not hand-written)
│   ├── assumptions.md                 manual steps, differences, limitations
│   ├── galvus-db.sql.gz               database dump
│   └── galvus-files.tar.gz            public files (hero SVG etc.)
├── scripts/
│   ├── build-galvus-page.php          builds page template + page from the components
│   └── export-component-tree.php      writes output-components.json
├── sample-input.html                  the AI-generated input page
└── web/themes/custom/galvus_ds/       the design-system theme
    ├── galvus_ds.info.yml / .libraries.yml / .theme
    ├── css/tokens.css                 design tokens (colours, fonts, type scale, spacing, radii…)
    ├── css/base.css                   element defaults, .wrap, .btn, .skip
    ├── templates/html.html.twig       skip link → #main
    ├── templates/page.html.twig       fallback layout for non-Canvas routes
    └── components/                    Canvas-compatible SDCs (each: .component.yml, .twig, .css)
        ├── header/          nav_link/        main_content/     footer/
        ├── hero/ (+ hero-illustration.svg)
        ├── section/         section_heading/
        ├── benefits_grid/   card/
        ├── faq/             faq_item/
        └── resources_grid/  resource_card/   cta/
```

These are not in the package. `ddev composer install` recreates them: `vendor/`, `web/core/`,
`web/modules/contrib/`, `web/themes/contrib/`, `web/profiles/contrib/`, `web/libraries/`,
and the scaffold files in `web/` (`index.php`, `.htaccess`, …). DDEV generates
`web/sites/default/settings*.php` on `ddev start`.

`recipes/` is created **empty** by `composer install`. After `drupal:recipe-unpack` the recipes are no
longer Composer dependencies. The site does not need them, because they are only used during a fresh
install, and this package restores from the database instead.

## 6. Contents of the submission zip

```
.ddev/config.yaml
.editorconfig
.gitattributes
.gitignore
AGENTS.md  CONTRIBUTING.md  LICENSE.txt  README.md      (from the Drupal CMS project template)
assets/append-default.settings.php.txt                 (used by composer scaffold)
composer.json
composer.lock
config/                                                (567 .yml files)
deliverables/README.md
deliverables/component-mapping.md
deliverables/output-components.json
deliverables/assumptions.md
deliverables/galvus-db.sql.gz
deliverables/galvus-files.tar.gz
sample-input.html
scripts/build-galvus-page.php
scripts/export-component-tree.php
web/themes/custom/galvus_ds/                           (whole folder)
```

Do **not** include: `vendor/`, `web/core/`, `web/modules/contrib/`, `web/themes/contrib/`,
`web/profiles/contrib/`, `web/libraries/`, `recipes/`, `web/sites/default/files/`,
`web/sites/default/settings*.php`, any other `.ddev/*` (DDEV regenerates it), or the scaffold
files in `web/`.

## 7. Verification of these steps

Sections 2 and 6 were tested on 2026-10-09. Only the files listed in section 6 were copied into an
empty folder, `C:\Projects\galvus-verify`. Because the original project was still running, the copy
was given a different project name (`ddev config --project-name=galvus-verify`). Steps 1–7 were then
run in order:

| Step | Result |
|---|---|
| `ddev start` | OK |
| `ddev composer install` | OK (exit 0). `recipes/` created empty, as described above |
| `ddev import-db` | "Successfully imported database" |
| `ddev import-files` | "Successfully imported files" |
| `drush config:import --source=…/config` | "There are no changes to import." (exported config matches the DB) |
| `drush cache:rebuild` | OK |
| `drush user:login` | one-time login link returned |
| `/page/1` as anonymous visitor | Landmarks and sections the same as the original site (skip link → header → `<main id="main">` with hero, benefits, faq, support, join → footer). Hero SVG returns HTTP 200 |
| `/canvas/editor/page_variant/galvus_default` | Opens. Templates panel lists "Galvus default — Default", Published |
| `/canvas/editor/canvas_page/1` | Opens with the page data (title, Published). The preview area did not finish loading in the test browser, so the in-editor preview was **not** confirmed in the copy (it does render on the original site) |
| `/admin/appearance/component/status` | Route exists (`canvas.component.status`) |

Not tested: a fresh install from `config/` without the database dump. The site was installed from a
recipe, not an install profile (`core.extension:profile` is null), and the page, media and files are
content, which is not stored in config. The database dump is the supported way to restore.

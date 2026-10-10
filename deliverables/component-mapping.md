# Component mapping: sample-input.html → Drupal Canvas

How each part of `sample-input.html` is represented in Drupal Canvas.
Component IDs are Canvas `Component` config entity IDs (`sdc.<theme>.<component>`).
Values in the "Value on built page" column are taken from
[`output-components.json`](output-components.json), which was exported from the database.

Where it lives:
- **Template** = Canvas page template (page variant `galvus_default`, the site default).
- **Page** = the page's own component tree (`canvas_page` 1, `/page/1`).

## Overview

| # | sample-input.html | Canvas component | Where | Slots → children |
|---|---|---|---|---|
| 1 | `<a class="skip" href="#main">` | Theme template `templates/html.html.twig` (not a component) | Theme | – |
| 2 | `<header class="site-header">` | `sdc.galvus_ds.header` | Template | `nav_links` → 3 × `sdc.galvus_ds.nav_link` |
| 3 | `<main id="main">` | `sdc.galvus_ds.main_content` | Template | `content` → `marker.page_content` (Canvas "Page content" marker) |
| 4 | `<section class="hero">` | `sdc.galvus_ds.hero` | Page | – |
| 5 | `<section id="benefits">` | `sdc.galvus_ds.section` | Page | `content` → `section_heading`, `benefits_grid` |
| 5a | `.section-head` (benefits) | `sdc.galvus_ds.section_heading` | Page | – |
| 5b | `.benefits-grid` | `sdc.galvus_ds.benefits_grid` | Page | `cards` → 4 × `sdc.galvus_ds.card` |
| 5c | `.benefit` | `sdc.galvus_ds.card` | Page | – |
| 6 | `<section class="faq">` | `sdc.galvus_ds.faq` | Page | `items` → 5 × `sdc.galvus_ds.faq_item` |
| 6a | `<details>/<summary>` | `sdc.galvus_ds.faq_item` | Page | – |
| 7 | `<section class="resources">` | `sdc.galvus_ds.section` | Page | `content` → `section_heading`, `resources_grid` |
| 7a | `.section-head` (resources) | `sdc.galvus_ds.section_heading` | Page | – |
| 7b | `.resources ul` | `sdc.galvus_ds.resources_grid` | Page | `items` → 3 × `sdc.galvus_ds.resource_card` |
| 7c | `.resources li` | `sdc.galvus_ds.resource_card` | Page | – |
| 8 | `<section class="cta">` | `sdc.galvus_ds.cta` | Page | – |
| 9 | `<footer class="site-footer">` | `sdc.galvus_ds.footer` | Template | `links` → 4 × `sdc.galvus_ds.nav_link` |

Totals: page tree has 21 component instances; the template has 11 (including the marker).

## Props per component

Prop types follow Canvas field mapping: `string` → text field (`maxLength` enforced),
`string` + `pattern` → textarea, `format: uri-reference` → link field, `enum` → select list,
`$ref …/image` → Media Library, `array` of string → multi-value text field.
All text props are plain text and Twig auto-escapes them, so authors cannot inject HTML.

### Header: `sdc.galvus_ds.header` (template)
| Source | Prop | Type | Value on built page |
|---|---|---|---|
| `.brand` text | `brand_name` * | string ≤60 | Galvus Patient Support |
| `nav aria-label` | `nav_label` | string ≤40 | Main |
| `a.btn.small` text | `cta_label` | string ≤40 | Join the programme |
| `a.btn.small` href | `cta_url` | link | `#join` |
| nav `<li><a>` items | slot `nav_links` | Navigation link × 3 | What you'll find → `#benefits`; Questions → `#faq`; Support → `#support` |

Brand mark SVG: fixed in the template (brand asset, not editable).

### Navigation link: `sdc.galvus_ds.nav_link` (header and footer slots)
| Prop | Type |
|---|---|
| `label` * | string ≤40 |
| `url` * | link |

### Main content area: `sdc.galvus_ds.main_content` (template only)
No props. Renders `<main id="main" tabindex="-1">`. Slot `content` holds the Canvas "Page content" marker.

### Hero: `sdc.galvus_ds.hero`
| Source | Prop | Type | Value on built page |
|---|---|---|---|
| `h1` | `heading` * | string ≤120 | Take a confident step in managing your type 2 diabetes |
| `p.lead` | `lead` | textarea | Clear information, everyday tips and support resources… |
| `a.btn` text / href | `primary_label` / `primary_url` | string ≤40 / link | Explore support resources / `#support` |
| `a.link` text / href | `secondary_label` / `secondary_url` | string ≤40 / link | Read common questions / `#faq` |
| `img` (data: URI SVG) | `image` * | Media (image or svg_image) | Media 1 "Hero illustration" (same SVG, extracted to a file) |
| `section id` | `anchor_id` | string ≤40 | hero |

### Section: `sdc.galvus_ds.section`
| Prop | Type | Values on built page |
|---|---|---|
| `anchor_id` | string ≤40 | `benefits`, `support` |
| slot `content` | – | Section heading + Benefits grid / Resources grid |

### Section heading: `sdc.galvus_ds.section_heading`
| Source | Prop | Type | Values on built page |
|---|---|---|---|
| `.section-head h2` | `heading` * | string ≤120 | What you can find here / Support resources |
| `.section-head p` | `intro` | textarea | Practical help for every stage… / Free tools and guides… |

### Benefits grid: `sdc.galvus_ds.benefits_grid`
No props. Slot `cards` holds Card components.

### Card: `sdc.galvus_ds.card`
| Source | Prop | Type | Values on built page |
|---|---|---|---|
| `.icon svg` | `icon` * | enum: document, heart, calendar, clock | document, heart, calendar, clock |
| `h3` | `title` * | string ≤80 | Clear, simple information · Daily living tips · Prepare for doctor visits · Reminders and tracking |
| `p` | `text` | textarea | (the four descriptions from the source) |

The four icon SVGs are copied from the source into `card.twig`. Authors pick one from the list and cannot upload their own.

### FAQ: `sdc.galvus_ds.faq`
| Source | Prop | Type | Value on built page |
|---|---|---|---|
| `h2` | `heading` * | string ≤120 | Frequently asked questions |
| `section id` | `anchor_id` | string ≤40 | faq |
| `<details>` list | slot `items` | FAQ item × 5 | – |

### FAQ item: `sdc.galvus_ds.faq_item`
| Source | Prop | Type |
|---|---|---|
| `summary` | `question` * | string ≤160 |
| `details p` | `answer` * | textarea |

Values: the five questions and answers from the source, unchanged.

### Resources grid: `sdc.galvus_ds.resources_grid`
No props. Slot `items` holds Resource cards. Card 1 is mint, card 2 blue and card 3 sand, set by position (`:nth-child`) as in the source.

### Resource card: `sdc.galvus_ds.resource_card`
| Source | Prop | Type | Values on built page |
|---|---|---|---|
| `li h3` | `title` * | string ≤80 | Patient guide · Healthy eating planner · Talk to a support advisor |
| `li p` | `text` | textarea | (source text) |
| `li a` text | `link_label` * | string ≤40 | Download the guide · Open the planner · Contact support |
| `li a` href | `link_url` * | link | `/patient-guide` · `/healthy-eating-planner` · `/contact` (source: `#`) |

### Call to action: `sdc.galvus_ds.cta`
| Source | Prop | Type | Value on built page |
|---|---|---|---|
| `h2` | `heading` * | string ≤120 | Ready to get started? |
| `p` | `text` | textarea | Join the patient support programme… |
| `a.btn` text / href | `button_label` * / `button_url` * | string ≤40 / link | Join the programme / `/join` (source: `#`) |
| `section id` | `anchor_id` | string ≤40 | join |

### Footer: `sdc.galvus_ds.footer` (template)
| Source | Prop | Type | Value on built page |
|---|---|---|---|
| `.brand` | `brand` * | string ≤60 | Galvus Patient Support |
| `p.disclaimer` × 2 | `disclaimer_lines` * | list of strings (min 1) | the two source lines, unchanged |
| `ul li a` | slot `links` | Navigation link × 4 | Privacy policy `/privacy` · Terms of use `/terms` · Contact us `/contact` · Accessibility `/accessibility` (source: all `#`) |

`*` = required prop.

## Why slots instead of `links[{label, url}]` props

Canvas 1.12 cannot store props that are lists of custom objects, such as `[{label, url}]`. It can only
generate a field widget for scalars, lists of scalars, and its own known object shapes
(image, video, document, entity reference). Source: `JsonSchemaType::computeStorablePropShape()` in
the installed Canvas module. Repeating structured items (nav links, FAQ items, cards) are therefore
child components placed in slots. This is how Canvas is designed to handle repeating content, and
authors can reorder, add and remove the items in the Layers panel.

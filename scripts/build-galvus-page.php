<?php

/**
 * @file
 * Builds the Galvus landing page in Drupal Canvas from sample-input.html.
 *
 * - Page template (Canvas page variant "galvus_default"):
 *   Header, Main content area (<main id="main"> holding the "Page content"
 *   marker), Footer. Made the site default page template.
 * - Page content (canvas_page 1): Hero, Section (benefits), FAQ,
 *   Section (resources), CTA.
 *
 * Re-runnable: the variant, media item and page tree are replaced each run.
 *
 * Usage: ddev exec drush php:script /var/www/html/scripts/build-galvus-page.php
 */

declare(strict_types=1);

use Drupal\canvas\Entity\Component;
use Drupal\canvas\Entity\PageVariant;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

const GALVUS_VARIANT_ID = 'galvus_default';
const GALVUS_PAGE_ID = 1;

/**
 * Builds one component tree item.
 *
 * Starts from Canvas's own default input for the component and replaces the
 * values. Inputs are stored in Canvas's "collapsed" syntax (just the value per
 * prop), which Canvas requires for default static prop sources.
 */
function galvus_item(string $component_id, array $values = [], ?string $parent = NULL, ?string $slot = NULL): array {
  $component = Component::load($component_id);
  if (!$component) {
    throw new \RuntimeException("Component $component_id does not exist.");
  }
  $defaults = $component->getComponentSource()->getDefaultExplicitInput();
  $inputs = [];
  foreach ($defaults as $prop => $source) {
    $inputs[$prop] = $source['value'];
  }
  foreach ($values as $prop => $value) {
    if (!array_key_exists($prop, $defaults)) {
      throw new \RuntimeException("$component_id has no prop '$prop'.");
    }
    $inputs[$prop] = match ($defaults[$prop]['sourceType']) {
      'static:field_item:link' => ['uri' => $value, 'options' => []],
      'static:field_item:entity_reference' => ['target_id' => $value],
      default => $value,
    };
  }
  // Props without a value (e.g. an image with no default) are left out.
  $inputs = array_filter($inputs, fn ($v) => $v !== NULL);
  $item = [
    'uuid' => \Drupal::service('uuid')->generate(),
    'component_id' => $component_id,
    'component_version' => $component->getActiveVersion(),
    'inputs' => json_encode($inputs ?: new \stdClass(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
  ];
  if ($parent !== NULL) {
    $item['parent_uuid'] = $parent;
    $item['slot'] = $slot;
  }
  return $item;
}

/**
 * Prints violations and stops.
 */
function galvus_fail(string $what, iterable $violations): never {
  echo "ERROR: $what failed validation:\n";
  foreach ($violations as $v) {
    echo '  - [', $v->getPropertyPath(), '] ', strip_tags((string) $v->getMessage()), "\n";
  }
  exit(1);
}

// ---------------------------------------------------------------------------
// 1. Hero illustration as an SVG media item (Canvas image props use Media).
// ---------------------------------------------------------------------------
$theme_path = \Drupal::service('extension.list.theme')->getPath('galvus_ds');
$media_storage = \Drupal::entityTypeManager()->getStorage('media');
$existing = $media_storage->loadByProperties(['bundle' => 'svg_image', 'name' => 'Hero illustration']);
$media = $existing ? reset($existing) : NULL;
if (!$media) {
  $fs = \Drupal::service('file_system');
  $dir = 'public://galvus';
  $fs->prepareDirectory($dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
  $uri = $fs->copy(DRUPAL_ROOT . '/' . $theme_path . '/components/hero/hero-illustration.svg', $dir . '/hero-illustration.svg', \Drupal\Core\File\FileExists::Replace);
  $file = File::create(['uri' => $uri, 'status' => 1]);
  $file->save();
  $media = Media::create([
    'bundle' => 'svg_image',
    'name' => 'Hero illustration',
    'status' => 1,
    'field_media_svg_image' => [
      'target_id' => $file->id(),
      'alt' => 'Illustration of a smiling person with a heartbeat line, representing patient support',
      'width' => 560,
      'height' => 460,
    ],
  ]);
  $media->save();
}
echo "Media: Hero illustration (media id ", $media->id(), ")\n";

// ---------------------------------------------------------------------------
// 2. Page template (page variant): Header, Main content area, Footer.
// ---------------------------------------------------------------------------
$tree = [];
$tree[] = $header = galvus_item('sdc.galvus_ds.header', [
  'brand_name' => 'Galvus Patient Support',
  'nav_label' => 'Main',
  'cta_label' => 'Join the programme',
  'cta_url' => '#join',
]);
foreach ([["What you'll find", '#benefits'], ['Questions', '#faq'], ['Support', '#support']] as [$label, $url]) {
  $tree[] = galvus_item('sdc.galvus_ds.nav_link', ['label' => $label, 'url' => $url], $header['uuid'], 'nav_links');
}
$tree[] = $main = galvus_item('sdc.galvus_ds.main_content');
$tree[] = galvus_item('marker.page_content', [], $main['uuid'], 'content');
$tree[] = $footer = galvus_item('sdc.galvus_ds.footer', [
  'brand' => 'Galvus Patient Support',
  'disclaimer_lines' => [
    'This page is for sample purposes only and does not replace advice from a healthcare professional. Always talk to your doctor or pharmacist about your treatment.',
    '© 2026 Sample Pharma. All rights reserved.',
  ],
]);
foreach (['Privacy policy' => '/privacy', 'Terms of use' => '/terms', 'Contact us' => '/contact', 'Accessibility' => '/accessibility'] as $label => $url) {
  $tree[] = galvus_item('sdc.galvus_ds.nav_link', ['label' => $label, 'url' => $url], $footer['uuid'], 'links');
}

$variant = PageVariant::load(GALVUS_VARIANT_ID) ?? PageVariant::create([
  'id' => GALVUS_VARIANT_ID,
  'label' => 'Galvus default',
  'description' => 'Header, main landmark and footer for Galvus patient pages.',
  'status' => TRUE,
]);
$variant->set('component_tree', $tree);
$typed = \Drupal::service('config.typed')->createFromNameAndData($variant->getConfigDependencyName(), $variant->toArray());
$violations = $typed->validate();
if (count($violations)) {
  galvus_fail('Page template (page variant ' . GALVUS_VARIANT_ID . ')', $violations);
}
$variant->save();
\Drupal::configFactory()->getEditable('canvas.settings')->set('default_page_variant', GALVUS_VARIANT_ID)->save();
echo "Page template: ", GALVUS_VARIANT_ID, " saved and set as site default\n";

// ---------------------------------------------------------------------------
// 3. Page content: Hero, Section (benefits), FAQ, Section (resources), CTA.
// ---------------------------------------------------------------------------
$tree = [];
$tree[] = galvus_item('sdc.galvus_ds.hero', [
  'heading' => 'Take a confident step in managing your type 2 diabetes',
  'lead' => 'Clear information, everyday tips and support resources to help you have better conversations with your doctor.',
  'primary_label' => 'Explore support resources',
  'primary_url' => '#support',
  'secondary_label' => 'Read common questions',
  'secondary_url' => '#faq',
  'image' => $media->id(),
  'anchor_id' => 'hero',
]);

$tree[] = $benefits = galvus_item('sdc.galvus_ds.section', ['anchor_id' => 'benefits']);
$tree[] = galvus_item('sdc.galvus_ds.section_heading', [
  'heading' => 'What you can find here',
  'intro' => 'Practical help for every stage of your treatment, in one place.',
], $benefits['uuid'], 'content');
$tree[] = $grid = galvus_item('sdc.galvus_ds.benefits_grid', [], $benefits['uuid'], 'content');
$cards = [
  ['document', 'Clear, simple information', 'Plain-language guides that explain your treatment and what to expect.'],
  ['heart', 'Daily living tips', 'Practical ideas on meals, activity and routines that fit your life.'],
  ['calendar', 'Prepare for doctor visits', 'Question lists and checklists so you get more from every appointment.'],
  ['clock', 'Reminders and tracking', 'Simple tools to help you remember your medicine and track how you feel.'],
];
foreach ($cards as [$icon, $title, $text]) {
  $tree[] = galvus_item('sdc.galvus_ds.card', ['icon' => $icon, 'title' => $title, 'text' => $text], $grid['uuid'], 'cards');
}

$tree[] = $faq = galvus_item('sdc.galvus_ds.faq', ['heading' => 'Frequently asked questions', 'anchor_id' => 'faq']);
$questions = [
  ['What is Galvus used for?', 'Galvus is a prescription medicine used with diet and exercise in adults with type 2 diabetes. Your doctor will decide if it is right for you.'],
  ['How should I take my medicine?', 'Always take your medicine exactly as your doctor or pharmacist has told you. If you are unsure, ask them.'],
  ['What should I do if I miss a dose?', 'Follow the instructions in your patient leaflet and speak to your doctor or pharmacist if you are not sure what to do.'],
  ['Can I stop taking it when I feel better?', 'Do not stop or change your treatment without talking to your doctor first.'],
  ['Where can I report side effects?', 'Talk to your doctor or pharmacist about any side effect. You can also report them through your national reporting system.'],
];
foreach ($questions as [$q, $a]) {
  $tree[] = galvus_item('sdc.galvus_ds.faq_item', ['question' => $q, 'answer' => $a], $faq['uuid'], 'items');
}

$tree[] = $resources = galvus_item('sdc.galvus_ds.section', ['anchor_id' => 'support']);
$tree[] = galvus_item('sdc.galvus_ds.section_heading', [
  'heading' => 'Support resources',
  'intro' => 'Free tools and guides to help you every day.',
], $resources['uuid'], 'content');
$tree[] = $list = galvus_item('sdc.galvus_ds.resources_grid', [], $resources['uuid'], 'content');
$cards = [
  ['Patient guide', 'Download a printable guide to living with type 2 diabetes.', 'Download the guide', '/patient-guide'],
  ['Healthy eating planner', 'Weekly meal ideas you can adapt with your care team.', 'Open the planner', '/healthy-eating-planner'],
  ['Talk to a support advisor', 'Get answers to non-medical questions about the programme.', 'Contact support', '/contact'],
];
foreach ($cards as [$title, $text, $label, $url]) {
  $tree[] = galvus_item('sdc.galvus_ds.resource_card', ['title' => $title, 'text' => $text, 'link_label' => $label, 'link_url' => $url], $list['uuid'], 'items');
}

$tree[] = galvus_item('sdc.galvus_ds.cta', [
  'heading' => 'Ready to get started?',
  'text' => 'Join the patient support programme to receive helpful tips and resources by email.',
  'button_label' => 'Join the programme',
  'button_url' => '/join',
  'anchor_id' => 'join',
]);

$page = \Drupal::entityTypeManager()->getStorage('canvas_page')->load(GALVUS_PAGE_ID);
if (!$page) {
  throw new \RuntimeException('canvas_page ' . GALVUS_PAGE_ID . ' not found.');
}
$page->set('title', 'Living well with type 2 diabetes');
$page->set('description', 'Sample AI-generated patient landing page (case study input). Public-inspiration content only.');
$page->set('components', $tree);
$page->set('status', TRUE);
$page->setNewRevision();
$page->set('revision_log', 'Built from sample-input.html by scripts/build-galvus-page.php');
$violations = $page->validate();
if (count($violations)) {
  galvus_fail('Page content (canvas_page ' . GALVUS_PAGE_ID . ')', $violations);
}
$page->save();
echo "Page: canvas_page ", $page->id(), " saved with ", count($tree), " component instances, published\n";
echo "URL: ", $page->toUrl()->toString(), "\n";

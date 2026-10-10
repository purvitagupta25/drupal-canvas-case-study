<?php

/**
 * @file
 * Exports the stored Canvas component trees of the built Galvus page.
 *
 * Read-only. Writes deliverables/output-components.json containing, for both
 * the page (canvas_page 1) and its page template (page variant
 * galvus_default):
 * - "stored_items": the component tree exactly as stored by Canvas (flat list,
 *   inputs decoded from their JSON storage string for readability);
 * - "tree": the same items nested by parent/slot, for easy reading.
 *
 * Usage: ddev exec drush php:script /var/www/html/scripts/export-component-tree.php
 */

declare(strict_types=1);

use Drupal\canvas\Entity\PageVariant;

/**
 * Normalizes stored items: decodes the inputs JSON string.
 */
function galvus_export_items(array $items): array {
  return array_map(static function (array $item): array {
    $inputs = $item['inputs'] ?? '{}';
    $item['inputs'] = is_string($inputs) ? json_decode($inputs, TRUE) : $inputs;
    return $item;
  }, array_values($items));
}

/**
 * Nests flat items under their parent's slots.
 */
function galvus_export_nest(array $items, ?string $parent = NULL, ?string $slot = NULL): array {
  $out = [];
  foreach ($items as $item) {
    if (($item['parent_uuid'] ?? NULL) !== $parent || ($item['slot'] ?? NULL) !== $slot) {
      continue;
    }
    $node = [
      'component_id' => $item['component_id'],
      'uuid' => $item['uuid'],
      'inputs' => $item['inputs'] ?: new \stdClass(),
    ];
    $slots = [];
    foreach ($items as $child) {
      if (($child['parent_uuid'] ?? NULL) === $item['uuid']) {
        $slots[$child['slot']] = galvus_export_nest($items, $item['uuid'], $child['slot']);
      }
    }
    if ($slots) {
      $node['slots'] = $slots;
    }
    $out[] = $node;
  }
  return $out;
}

$page = \Drupal::entityTypeManager()->getStorage('canvas_page')->load(1);
$page_items = galvus_export_items($page->get('components')->getValue());

$variant_id = $page->get('page_variant')->value ?: \Drupal::config('canvas.settings')->get('default_page_variant');
$variant = PageVariant::load($variant_id);
$variant_items = galvus_export_items($variant->get('component_tree'));

$export = [
  '_about' => 'Exported from the live Drupal database by scripts/export-component-tree.php. Not hand-written.',
  'exported_at' => gmdate('c'),
  'drupal_version' => \Drupal::VERSION,
  'canvas_version' => \Drupal::service('extension.list.module')->getExtensionInfo('canvas')['version'] ?? NULL,
  'page' => [
    'entity_type' => 'canvas_page',
    'id' => (int) $page->id(),
    'uuid' => $page->uuid(),
    'title' => $page->label(),
    'status' => (bool) $page->isPublished(),
    'revision_id' => (int) $page->getRevisionId(),
    'page_template' => $page->get('page_variant')->value ?: '(site default: ' . $variant_id . ')',
    'url' => $page->toUrl()->toString(),
    'component_count' => count($page_items),
    'tree' => galvus_export_nest($page_items),
    'stored_items' => $page_items,
  ],
  'page_template' => [
    'entity_type' => 'page_variant',
    'id' => $variant->id(),
    'label' => $variant->label(),
    'is_site_default' => \Drupal::config('canvas.settings')->get('default_page_variant') === $variant->id(),
    'component_count' => count($variant_items),
    'tree' => galvus_export_nest($variant_items),
    'stored_items' => $variant_items,
  ],
];

$target = DRUPAL_ROOT . '/../deliverables/output-components.json';
file_put_contents($target, json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
echo "Wrote $target (page: ", count($page_items), " items, page template: ", count($variant_items), " items)\n";

<?php

namespace Drupal\joinup_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\Html;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Rewrites URLs pointing to CKEditor uploaded files.
 *
 * @MigrateProcessPlugin(
 *   id = "file_inline"
 * )
 */
class FileInline extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    global $base_path;

    if (!$value) {
      return $value;
    }

    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    $public_dir = \Drupal::service('stream_wrapper.public')->getDirectoryPath();

    $document = Html::load($value);
    $search = ['img' => 'src', 'a' => 'href'];
    $changed = FALSE;
    foreach ($search as $tag => $attribute) {
      /** @var \DOMElement $element */
      foreach ($document->getElementsByTagName($tag) as $element) {
        if ($element->hasAttribute($attribute)) {
          $url = $element->getAttribute($attribute);
          if (preg_match('|^(?:(?:https?://joinup\.ec\.europa\.eu)?/sites/default/files/ckeditor_files/(.*))$|', $url, $found)) {
            // Search for a migrated file.
            $uri = "public://inline-{$found[1]}";
            $files = $file_storage->loadByProperties(['uri' => $uri]);
            if (empty($files)) {
              // This file was not migrated probably because the file doesn't
              // exists on file system. In this case we let the reference broken
              // but we still log a message, so it can be manually fixed later.
              $migrate_executable->saveMessage("URL '{$found[0]}' embedded in '$destination_property' cannot be converted. The file was not migrated (doesn't exists?)");
              continue;
            }

            $file = reset($files);
            $element->setAttribute($attribute, "/sites/default/files/inline-{$found[1]}");
            $element->setAttribute('data-entity-type', 'file');
            $element->setAttribute('data-entity-uuid', $file->uuid());
            $changed = TRUE;
          }
        }
      }
    }

    if ($changed) {
      $value = Html::serialize($document);
    }

    return $value;
  }

}

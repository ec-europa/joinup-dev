<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Drupal\joinup_migrate\FileUtility;
use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;

/**
 * Source plugin for inline files.
 *
 * @MigrateSource(
 *   id = "file_inline"
 * )
 */
class FileInline extends SourcePluginBase implements RedirectImportInterface {

  use DefaultFileRedirectTrait;

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['fid' => ['type' => 'string']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'path' => $this->t('File path'),
      'timestamp' => $this->t('Created time'),
      'uid' => $this->t('File owner'),
      'destination_uri' => $this->t('Destination URI'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $rows = [];
    $timestamp = \Drupal::time()->getRequestTime();
    $legacy_site_files = FileUtility::getLegacySiteFiles();

    foreach ($this->getInlineFiles() as $file) {
      list($type, $basename) = explode('/', $file, 2);
      $path = "ckeditor_files/$file";
      $fid = "sites/default/files/$path";
      $rows[$fid] = [
        'fid' => $fid,
        'path' => "$legacy_site_files/$path",
        'timestamp' => $timestamp,
        'uid' => 1,
        'destination_uri' => "public://inline-$type/$basename",
      ];
    }

    return new \ArrayIterator($rows);
  }

  /**
   * Provides a list of inline files.
   *
   * @return string[]
   *   A list of inline files paths. The path is relative to Drupal 6 CKEditor
   *   files directory 'sites/default/files/ckeditor_files'.
   */
  protected function getInlineFiles() {
    $db = Database::getConnection('default', 'migrate');
    $search = ['img' => 'src', 'a' => 'href'];

    $files = [];
    foreach (static::$bodyFields as $table => $fields) {
      $items = $db->select($table)->fields($table, $fields)->execute()->fetchAll();
      foreach ($items as $item) {
        foreach ($fields as $field) {
          if (!$item->{$field}) {
            continue;
          }

          $document = Html::load($item->{$field});
          foreach ($search as $tag => $attribute) {
            /** @var \DOMElement $element */
            foreach ($document->getElementsByTagName($tag) as $element) {
              if ($element->hasAttribute($attribute)) {
                $url = $element->getAttribute($attribute);
                if (preg_match('|^(http[s]?://joinup\.ec\.europa\.eu)?/sites/default/files/ckeditor_files/(.*)$|', $url, $found)) {
                  $files[] = $found[2];
                  // If the link is encoded, we don't know how exactly is stored
                  // on the file-system. Some of them have the name already
                  // encoded. We create a decoded copy so we make sure we've
                  // covered all the cases. This will produce false positives
                  // as, in such cases, one of the two versions will not be
                  // found but, at least, we'll not miss real files.
                  $file_decoded = rawurldecode($found[2]);
                  if ($file_decoded !== $found[2]) {
                    $files[] = $file_decoded;
                  }
                }
              }
            }
          }
        }
      }
    }

    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectSources(Row $row) {
    return [$row->getSourceProperty('fid')];
  }

  /**
   * A list of table/views providing content fields.
   *
   * @var array
   */
  protected static $bodyFields = [
    'd8_collection' => ['body'],
    'd8_comment' => ['comment'],
    'd8_custom_page' => ['body'],
    'd8_discussion' => ['body'],
    'd8_distribution' => ['body'],
    'd8_document' => ['body'],
    'd8_event' => ['body', 'agenda'],
    'd8_news' => ['body'],
    'd8_newsletter' => ['body'],
    'd8_release' => ['body', 'version_notes'],
    'd8_solution' => ['body'],
    'd8_user' => ['professional_profile'],
    'd8_video' => ['body'],
  ];

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'file_inline';
  }

}

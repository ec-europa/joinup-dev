<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;

/**
 * Source plugin for inline files.
 *
 * @MigrateSource(
 *   id = "file_inline"
 * )
 */
class FileInline extends SourcePluginBase {

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

    foreach ($this->getInlineFiles() as $file) {
      list($type, $basename) = explode('/', $file, 2);
      $path = "ckeditor_files/$file";
      $rows[$path] = [
        'fid' => $path,
        'path' => $path,
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
          if (!$item->$field) {
            continue;
          }
          // Ensure we have well-formed markup.
          $markup =& $item->$field;
          $dom = new \DOMDocument();
          @$dom->loadHTML($markup);
          $dom->normalizeDocument();
          foreach ($search as $tag => $attribute) {
            /** @var \DOMElement $element */
            foreach ($dom->getElementsByTagName($tag) as $element) {
              if ($element->hasAttribute($attribute)) {
                $url = $element->getAttribute($attribute);
                if (preg_match('|^(http[s]?://joinup\.ec\.europa\.eu)?/sites/default/files/ckeditor_files/(.*)$|', $url, $found)) {
                  $files[] = $found[2];
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
  public function prepareRow(Row $row) {
    // Assure a full-qualified path for managed files.
    $source_path = JoinupSqlBase::getLegacySiteFiles() . '/' . $row->getSourceProperty('path');
    $row->setSourceProperty('path', $source_path);
    return parent::prepareRow($row);
  }

  /**
   * A list of table/views providing content fields.
   *
   * @var array
   */
  protected static $bodyFields = [
    'd8_collection' => ['body'],
    // @todo Disable till ISAICP-3514 gets clarified.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3514
    // 'd8_comment' => ['comment'],
    'd8_custom_page' => ['body'],
    'd8_discussion' => ['body'],
    'd8_distribution' => ['body'],
    'd8_document' => ['body'],
    'd8_event' => ['body', 'agenda'],
    'd8_news' => ['body'],
    'd8_newsletter' => ['body'],
    'd8_release' => ['body'],
    'd8_solution' => ['body'],
    'd8_video' => ['body'],
  ];

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'file_inline';
  }

}

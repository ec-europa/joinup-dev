<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Language\LanguageManager;
use Drupal\migrate\Row;

/**
 * Common methods for setting the field translations in source plugins.
 */
trait FieldTranslationTrait {

  /**
   * {@inheritdoc}
   */
  public function setFieldTranslations(Row &$row) {
    if (!$vid = (int) $row->getSourceProperty('vid')) {
      return;
    }

    $i18n = [];
    foreach ($this->getTranslatableFields() as $destination => $info) {
      $translations = $this->select($info['table'])
        ->fields($info['table'], [$info['field']])
        ->condition('vid', $vid)
        ->isNotNull($info['field'])
        ->condition($info['field'], '', '<>')
        ->execute()
        ->fetchCol();
      foreach ($translations as $translation) {
        $translation = unserialize($translation);
        if ($translation && !empty($translation['field_language_textfield_lang'][0]['value']) && !empty($translation[$info['sub_field']][0]['value'])) {
          $langcode = $translation['field_language_textfield_lang'][0]['value'];
          if (isset(LanguageManager::getStandardLanguageList()[$langcode])) {
            $value =& $translation[$info['sub_field']][0]['value'];
            $i18n[$langcode][$destination] = $value;
          }
        }
      }
    }

    $row->setSourceProperty('i18n', $i18n);
  }

}

<?php

namespace Drupal\rdf_taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\taxonomy\VocabularyStorageInterface;

/**
 * Defines a controller class for taxonomy vocabularies.
 */
class VocabularyRdfStorage extends ConfigEntityStorage implements VocabularyStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_vocabulary_get_names');
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getToplevelTids($vids) {
    return db_query('SELECT t.tid FROM {taxonomy_term_data} t INNER JOIN {taxonomy_term_hierarchy} th ON th.tid = t.tid WHERE t.vid IN ( :vids[] ) AND th.parent = 0', array(':vids[]' => $vids))->fetchCol();
  }

}

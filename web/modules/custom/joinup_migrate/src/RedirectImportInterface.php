<?php

namespace Drupal\joinup_migrate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\Row;

/**
 * Interface for source plugins allowing migration of redirects.
 */
interface RedirectImportInterface {

  /**
   * Gets the redirect source given the source row.
   *
   * @param \Drupal\migrate\Row $row
   *   The migrate row object.
   *
   * @return string[]
   *   A list of redirect paths.
   *
   * @see \Drupal\redirect\Plugin\Field\FieldType\RedirectSourceItem
   */
  public function getRedirectSources(Row $row);

  /**
   * Gets the redirect URI for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The migrate row object.
   *
   * @return string
   *   The redirect URI.
   */
  public function getRedirectUri(EntityInterface $entity);

}

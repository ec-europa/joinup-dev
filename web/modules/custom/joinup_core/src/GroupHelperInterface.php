<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for classes that provide helper method for working with groups.
 *
 * In Joinup there are two group types: collections and solutions.
 */
interface GroupHelperInterface {

  /**
   * Contains the names of the bundles that function as groups in Joinup.
   */
  const BUNDLES = ['collection', 'solution'];

  /**
   * Returns the group with the given label.
   *
   * If there are multiple groups with the same label then a warning will be
   * logged and the first group will be returned.
   *
   * @param string $label
   *   The label of the group.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The group.
   *
   * @throws \InvalidArgumentException
   *   Thrown when there are no groups with the given label.
   */
  public function getGroupByLabel(string $label): RdfInterface;

}

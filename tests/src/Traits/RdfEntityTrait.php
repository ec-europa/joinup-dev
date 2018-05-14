<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Helper methods for using collections in tests.
 */
trait RdfEntityTrait {

  /**
   * Returns the RDF entity with the given name and type.
   *
   * If multiple RDF entities have the same name the first one will be returned.
   *
   * @param string $label
   *   The RDF entity label.
   * @param string $type
   *   Optional RDF entity type.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The RDF entity.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an RDF entity with the given name and type does not exist.
   */
  protected static function getRdfEntityByLabel($label, $type = NULL) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('label', $label)
      ->range(0, 1);
    if (!empty($type)) {
      $query->condition('rid', $type);
    }
    $result = $query->execute();

    if (empty($result)) {
      $message = $type ? "The $type entity with the label '$label' was not found." : "The RDF entity with the label '$label' was not found.";
      throw new \InvalidArgumentException($message);
    }

    return Rdf::load(reset($result));
  }

  /**
   * Returns the RDF entity with the given name and type.
   *
   * If multiple RDF entities have the same name the first one will be returned.
   *
   * This method resets the static cache before loading the entity and should be
   * used when an entity is altered through e.g. a hook update.
   *
   * @param string $title
   *   The RDF entity title.
   * @param string $type
   *   The RDF entity type.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The RDF entity.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an RDF entity with the given name and type does not exist.
   */
  protected function getRdfEntityByLabelUnchanged($title, $type) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', $type)
      ->condition('label', $title)
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The $type entity with the name '$title' was not found.");
    }

    return \Drupal::entityTypeManager()
      ->getStorage('rdf_entity')
      ->loadUnchanged(reset($result));
  }

  /**
   * Checks the number of available RDF entities filtered by bundle.
   *
   * @param int $number
   *   The expected number of RDF entities.
   * @param string $type
   *   The RDF type.
   *
   * @throws \Exception
   *   Thrown when the number of RDF entities does not
   *   match the expectation.
   */
  protected function assertRdfEntityCount($number, $type) {
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', $type)
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of $type entities. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Parses human readable fields for RDF entities.
   *
   * This is a convenient wrapper around parseEntityFields() that handles the
   * type casting.
   *
   * @param array $fields
   *   An array of human readable field values.
   *
   * @return array
   *   An array of field data as expected by the field storage handler.
   *
   * @see \Drupal\DrupalExtension\Context\RawDrupalContext::parseEntityFields()
   */
  public function parseRdfEntityFields(array $fields) {
    $entity = (object) $fields;
    parent::parseEntityFields('rdf_entity', $entity);
    return (array) $entity;
  }

  /**
   * Creates and saves an rdf entity of a specific bundle.
   *
   * @param string $bundle
   *   The rdf entity bundle.
   * @param array $values
   *   An array of field values.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The newly created entity.
   */
  protected static function createRdfEntity($bundle, array $values) {
    // Convert timestamp fields from human-readable to timestamp.
    // @todo Replace this with a Behat hook.
    foreach (['changed', 'created'] as $field) {
      if (isset($values[$field])) {
        $date = new DrupalDateTime($values[$field]);
        $values[$field] = $date->getTimestamp();
      }
    }

    $values['rid'] = $bundle;
    $entity = Rdf::create($values);
    $entity->save();

    return $entity;
  }

}

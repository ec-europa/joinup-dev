<?php

namespace Drupal\rdf_entity\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts the escaped URI's in the path into valid URI's.
 *
 * The outbound escaping is handled in:
 *
 * @see \Drupal\rdf_entity\Entity\Rdf::urlRouteParameters.
 */
class RdfEntityConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], 'entity:') === 0) {
      $entity_type_id = substr($definition['type'], strlen('entity:'));
      if (strpos($definition['type'], '{') !== FALSE) {
        $entity_type_slug = substr($entity_type_id, 1, -1);
        return $name != $entity_type_slug && in_array($entity_type_slug, $route->compile()->getVariables(), TRUE);
      }
      // This converter only applies rdf entities.
      if ($entity_type_id == 'rdf_entity') {
        return $this->entityManager->hasDefinition($entity_type_id);
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // Here the escaped uri is transformed into a valid uri.
    // @see \Drupal\rdf_entity\Entity\Rdf::urlRouteParameters
    $value = str_replace('\\', '/', $value);
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    if ($storage = $this->entityManager->getStorage($entity_type_id)) {
      $entity = $storage->load($value);
      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        $entity = $this->entityManager->getTranslationFromContext($entity, NULL, array('operation' => 'entity_upcast'));
      }
      return $entity;
    }
  }

}

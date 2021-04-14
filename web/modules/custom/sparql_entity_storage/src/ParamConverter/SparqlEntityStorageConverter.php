<?php

namespace Drupal\sparql_entity_storage\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Drupal\sparql_entity_storage\Event\ActiveGraphEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Drupal\sparql_entity_storage\UriEncoder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts the escaped URI's in the path into valid URI's.
 *
 * @see \Drupal\sparql_entity_storage\RouteProcessor\SparqlEntityStorageRouteProcessor
 * @see \Drupal\sparql_entity_storage\UrlEncoder
 */
class SparqlEntityStorageConverter extends EntityConverter {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new param converter service instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($entity_type_manager, $entity_repository);
    $this->eventDispatcher = $event_dispatcher;
  }

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
      // This converter only applies SPARQL entities.
      $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
      if ($entity_storage instanceof SparqlEntityStorage) {
        return $this->entityTypeManager->hasDefinition($entity_type_id);
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // Here the escaped URI is transformed into a valid URI.
    if (!SparqlArg::isValidResource($value)) {
      $value = UriEncoder::decodeUrl($value);
    }
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $event = new ActiveGraphEvent($name, $value, $entity_type_id, $definition, $defaults);
    // Determine the graph by dispatching an event.
    $event = $this->eventDispatcher->dispatch(SparqlEntityStorageEvents::GRAPH_ENTITY_CONVERT, $event);
    $entity = $storage->load($value, $event->getGraphs());
    // If the entity type is translatable, ensure we return the proper
    // translation object for the current context.
    if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
      $entity = $this->entityRepository->getTranslationFromContext($entity, NULL, ['operation' => 'entity_upcast']);
    }
    return $entity;
  }

}

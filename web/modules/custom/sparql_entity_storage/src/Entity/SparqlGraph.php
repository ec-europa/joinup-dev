<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Drupal\sparql_entity_storage\SparqlGraphInterface;

/**
 * Defines the SPARQL graph config entity.
 *
 * Used to store basic information about each SPARQL graph.
 *
 * @ConfigEntityType(
 *   id = "sparql_graph",
 *   label = @Translation("SPARQL graph"),
 *   config_prefix = "graph",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "weight",
 *     "name",
 *     "description",
 *     "entity_types",
 *   },
 *   handlers = {
 *     "access" = "Drupal\sparql_entity_storage\SparqlGraphAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\sparql_entity_storage\Form\SparqlGraphForm",
 *       "edit" = "Drupal\sparql_entity_storage\Form\SparqlGraphForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\sparql_entity_storage\SparqlGraphListBuilder",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/sparql/graph/manage/{sparql_graph}",
 *     "delete-form" = "/admin/config/sparql/graph/manage/{sparql_graph}/delete",
 *     "collection" = "/admin/config/sparql/graph",
 *     "enable" = "/admin/config/sparql/graph/manage/{sparql_graph}/enable",
 *     "disable" = "/admin/config/sparql/graph/manage/{sparql_graph}/disable",
 *   },
 *   admin_permission = "administer site configuration",
 * )
 */
class SparqlGraph extends ConfigEntityBase implements SparqlGraphInterface {

  /**
   * The unique ID of this SPARQL graph.
   *
   * @var string
   */
  protected $id;

  /**
   * The weight value is used to define the order in the list of graphs.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The label of the SPARQL graph.
   *
   * @var string
   */
  protected $name;

  /**
   * The description of the SPARQL graph.
   *
   * @var string
   */
  protected $description;

  /**
   * Entity type IDs where this graph applies.
   *
   * NULL means it applies to all entity types.
   *
   * @var string[]|null
   */
  protected $entity_types = NULL;

  /**
   * {@inheritdoc}
   */
  public function setName(string $name): SparqlGraphInterface {
    $this->name = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight): SparqlGraphInterface {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $description): SparqlGraphInterface {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeIds(): ?array {
    return $this->entity_types ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeIds(?array $entity_type_ids): SparqlGraphInterface {
    if (empty($entity_type_ids)) {
      $this->entity_types = NULL;
    }
    else {
      foreach ($entity_type_ids as $entity_type_id) {
        if (!$this->entityTypeManager()->getDefinition($entity_type_id, FALSE)) {
          throw new \InvalidArgumentException("Invalid entity type: '$entity_type_id'.");
        }
        $storage = $this->entityTypeManager()->getStorage($entity_type_id);
        if (!$storage instanceof SparqlEntityStorage) {
          throw new \InvalidArgumentException("Entity type '$entity_type_id' doesn't have a SPARQL storage.");
        }
      }
      $this->entity_types = $entity_type_ids;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->isUninstalling() && ($this->id() === static::DEFAULT)) {
      throw new \RuntimeException("The '" . static::DEFAULT . "' graph cannot be deleted.");
    }
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if ($this->entity_types === []) {
      // Normalize 'entity_types' empty array to NULL.
      $this->entity_types = NULL;
    }

    if ($this->id() === static::DEFAULT) {
      if (!$this->status()) {
        throw new \RuntimeException("The '" . static::DEFAULT . "' graph cannot be disabled.");
      }
      if ($this->getEntityTypeIds()) {
        throw new \RuntimeException("The '" . static::DEFAULT . "' graph cannot be limited to certain entity types.");
      }
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    // Wipe out the static cache of the SPARQL graph handler.
    \Drupal::service('sparql.graph_handler')->clearCache();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    \Drupal::service('sparql.graph_handler')->clearCache();
  }

}

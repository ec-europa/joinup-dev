<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for 'sparql_graph' entities.
 */
interface SparqlGraphInterface extends ConfigEntityInterface {

  /**
   * Default graph.
   *
   * @var string
   */
  const DEFAULT = 'default';

  /**
   * Sets the SPARQL graph weight.
   *
   * The weight value is used to define the order in the list of graphs.
   *
   * @param int $weight
   *   The weight as integer.
   *
   * @return $this
   */
  public function setWeight(int $weight): self;

  /**
   * Gets the weight of this SPARQL graph.
   *
   * The weight value is used to define the order in the list of graphs.
   *
   * @return int
   *   The weight of this SPARQL graph.
   */
  public function getWeight(): int;

  /**
   * Set the graph name.
   *
   * @param string $name
   *   The graph name.
   *
   * @return $this
   */
  public function setName(string $name): self;

  /**
   * Set the graph description.
   *
   * @param string $description
   *   The graph description.
   *
   * @return $this
   */
  public function setDescription(string $description): self;

  /**
   * Gets the graph description.
   *
   * @return string
   *   The graph description.
   */
  public function getDescription(): ?string;

  /**
   * Gets the entity types supporting this graph.
   *
   * @return string[]|null
   *   A list of entity type IDs or NULL if this graph is available to all
   *   entity types.
   */
  public function getEntityTypeIds(): ?array;

  /**
   * Sets the entity type IDs to whom this graph is made available.
   *
   * @param string[]|null $entity_type_ids
   *   A list of entity type IDs or NULL to expose this graph to all types.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   If there are non-eligible entity types in the list. Eligible entity type
   *   IDs are those each fulfilling all the following conditions:
   *   - An entity type exists for that ID,
   *   - The entity type is a content entity type,
   *   - The entity type storage is an instance of SparqlEntityStorage.
   *
   * @see \Drupal\sparql_entity_storage\SparqlEntityStorage
   */
  public function setEntityTypeIds(?array $entity_type_ids): self;

}

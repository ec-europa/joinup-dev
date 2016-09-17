<?php

namespace Drupal\rdf_entity;


use Drupal\Core\Entity\EntityManagerInterface;

class RdfGraphHandler {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *    The entity type manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $this->getModuleHandlerService();
  }

  /**
   * Get the defined graph types for this entity type.
   *
   * A default graph is provided here already because there has to exist at
   * least one available graph for the entities to be saved in.
   *
   * @param string $entity_type_id
   *    The entity type machine name.
   * @return array
   *    A structured array of graph definitions containing a title and a
   *    description. The array keys are the machine names of the graphs.
   */
  public function getGraphDefinitions($entity_type_id) {
    $graphs_definition = [];
    $graphs_definition['default'] = [
      'title' => $this->t('Default'),
      'description' => $this->t('The default graph used to store entities of this type.'),
    ];
    // @todo Consider turning this into an event. Advantages?

    $this->moduleHandler->alter('rdf_graph_definition', $entity_type_id, $graphs_definition);
    return $graphs_definition;
  }

  /**
   * Returns the module handler service object.
   *
   * @todo: Check how we can inject this.
   */
  protected function getModuleHandlerService() {
    return \Drupal::moduleHandler();
  }
}
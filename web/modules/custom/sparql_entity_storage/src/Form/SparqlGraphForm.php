<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sparql_entity_storage\Entity\SparqlGraph;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Drupal\sparql_entity_storage\SparqlGraphInterface;

/**
 * Provides a form class for SPARQL graphs.
 */
class SparqlGraphForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\sparql_entity_storage\SparqlGraphInterface $graph */
    $graph = $this->getEntity();

    $form['name'] = [
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $graph->label(),
      '#description' => $this->t('The human-readable name of this graph.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $graph->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$graph->isNew(),
      '#machine_name' => [
        'exists' => [SparqlGraph::class, 'load'],
        'source' => ['name'],
      ],
      '#description' => $this->t('A unique machine-readable name for this graph. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['weight'] = [
      '#type' => 'value',
      '#value' => $graph->getWeight(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $graph->getDescription(),
      '#description' => $this->t('The description of this graph.'),
    ];

    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
        if ($storage instanceof SparqlEntityStorage) {
          $entity_types[$entity_type_id] = $entity_type->getLabel();
        }
      }
    }

    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types using this graph'),
      '#description' => $this->t('If none is selected, this graph is made available to all entity types that are using SPARQL storage.'),
      '#options' => $entity_types,
      '#disabled' => $graph->id() === SparqlGraphInterface::DEFAULT,
      '#access' => (bool) $entity_types,
      '#default_value' => (array) $graph->getEntityTypeIds(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state): EntityInterface {
    // Normalize the entity types array.
    $entity_types = $form_state->getValue('entity_types');
    $form_state->setValue('entity_types', array_values(array_filter($entity_types)));
    return parent::buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $form_state->setRedirect('entity.sparql_graph.collection');
    $this->messenger()->addStatus($this->t("Graph %name (%id) has been saved.", [
      '%name' => $this->getEntity()->label(),
      '%id' => $this->getEntity()->id(),
    ]));
    return parent::save($form, $form_state);
  }

}

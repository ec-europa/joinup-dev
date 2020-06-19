<?php

declare(strict_types = 1);

namespace Drupal\solution\Form;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to select the destination collection when moving solutions.
 */
class ChangeCollectionForm extends FormBase {

  /**
   * The private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The selection plugin manager service.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionPluginManager;

  /**
   * The Joinup logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $private_temp_store_factory
   *   The private tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_plugin_manager
   *   The selection plugin manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   */
  public function __construct(PrivateTempStoreFactory $private_temp_store_factory, EntityTypeManagerInterface $entity_type_manager, SelectionPluginManagerInterface $selection_plugin_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->tempStore = $private_temp_store_factory->get('change_collection');
    $this->entityTypeManager = $entity_type_manager;
    $this->selectionPluginManager = $selection_plugin_manager;
    $this->logger = $logger_factory->get('joinup');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $source_collection = $this->getRouteMatch()->getParameter('rdf_entity');

    $form['source_collection'] = [
      '#type' => 'value',
      '#value' => $source_collection,
    ];

    $form['solutions'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('The following solutions from %source collection will be moved to a new collection:', [
        '%source' => $source_collection->label(),
      ]),
      '#items' => array_map(function (RdfInterface $solution): string {
        return $solution->label();
      }, $this->getSolutions()),
      '#empty' => $this->t('No solution was selected.'),
    ];

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase $selection */
    $selection = $this->selectionPluginManager->createInstance('default:rdf_entity', [
      'target_type' => 'rdf_entity',
      'target_bundles' => ['collection'],
    ]);
    $selection_settings = $selection->getConfiguration() + [
      'match_operator' => 'CONTAINS',
    ];

    $form['destination_collection'] = [
      '#title' => $this->t('Select the destination collection'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'rdf_entity',
      '#selection_handler' => 'default:rdf_entity',
      '#selection_settings' => $selection_settings,
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Move solutions'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('source_collection')->id() === $form_state->getValue('destination_collection')) {
      $form_state->setErrorByName('destination_collection', $this->t('The destination collection cannot be the same as the source collection.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\rdf_entity\RdfInterface $source_collection */
    $source_collection = $form_state->getValue('source_collection');
    $destination_collection_id = $form_state->getValue('destination_collection');

    if (!$destination_collection = $this->getRdfEntityStorage()->load($destination_collection_id)) {
      throw new \RuntimeException("Cannot load RDF entity with ID $destination_collection_id");
    }

    foreach ($this->getSolutions() as $solution) {
      $messenger_arguments = [
        '%solution' => $solution->toLink()->toString(),
        '%destination_collection' => $destination_collection->toLink()->toString(),
      ];
      $logger_arguments = [
        '@solution' => $solution->label(),
        '@destination_collection' => $destination_collection->label(),
        '@source_collection' => $source_collection->label(),
      ];

      try {
        // Prevent notification dispatching.
        // @see joinup_notification_dispatch_notification()
        $solution->skip_notification = TRUE;
        $solution->set('collection', $destination_collection_id)->save();
        $this->messenger()->addStatus($this->t('Solution %solution has been moved to %destination_collection.', $messenger_arguments));
        $this->logger->info("Solution '@solution' moved from '@source_collection' to '@destination_collection' collection.", $logger_arguments);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t("Error while trying to change the collection for %solution solution.", $messenger_arguments));
        $this->logger->error("Error while trying to move solution '@solution' from '@source_collection' to '@destination_collection' collection.", $logger_arguments);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'change_collection';
  }

  /**
   * Returns a list of entities to be moved to the new collection.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of entities to be moved to the new collection.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when an entity with a non-existing storage is passed.
   */
  protected function getSolutions(): array {
    $solution_ids = $this->tempStore->get('solutions');
    return $solution_ids ? $this->getRdfEntityStorage()->loadMultiple($solution_ids) : [];
  }

  /**
   * Returns the RDF entity storage.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   *   The RDF entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when an entity with a non-existing storage is passed.
   */
  protected function getRdfEntityStorage(): SparqlEntityStorageInterface {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $rdf_entity_storage */
    $rdf_entity_storage = $this->entityTypeManager->getStorage('rdf_entity');
    return $rdf_entity_storage;
  }

}

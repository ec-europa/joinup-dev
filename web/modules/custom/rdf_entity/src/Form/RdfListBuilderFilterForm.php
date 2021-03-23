<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to select which graph to load from in the entity listing page.
 */
class RdfListBuilderFilterForm extends FormBase {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new form instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info, EntityTypeManagerInterface $entity_type_manager) {
    $this->bundleInfo = $bundle_info;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['inline'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];

    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('rdf_entity');
    $graphs = array_map(function (array $definition) {
      return $definition['title'];
    }, $storage->getGraphDefinitions());
    if (count($graphs) > 1) {
      $form['inline']['graph'] = [
        '#type' => 'select',
        '#title' => $this->t('Graph'),
        '#options' => $graphs,
        '#default_value' => $this->getRequest()->get('graph'),
        '#empty_value' => NULL,
        '#empty_option' => $this->t('- Any -'),
      ];
    }

    $bundles = array_map(function (array $info) {
      return $info['label'];
    }, $this->bundleInfo->getBundleInfo('rdf_entity'));
    asort($bundles);
    $form['inline']['rid'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $bundles,
      '#default_value' => $this->getRequest()->get('rid'),
      '#empty_value' => NULL,
      '#empty_option' => $this->t('- Any -'),
    ];
    $form['inline']['submit'] = [
      '#value' => $this->t('Filter'),
      '#type' => 'submit',
    ];
    $form['#method'] = 'get';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graph_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Required by interface, but never called due to GET method.
  }

}

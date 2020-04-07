<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Constructs a form to redirect to the JLA comparison page with a new licence.
 */
class AddLicenceToComparisonForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an AddLicenceToComparisonForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_licence_to_comparison_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $licences = $form_state->getBuildInfo()['args'][0]['licences'] ?? [];
    $form['licence_search'] = [
      '#type' => 'select',
      '#title' => $this->t('Add licence'),
      '#title_display' => 'invisible',
      '#options' => $this->getLicenceOptions($licences),
      '#empty_value' => '',
      '#empty_option' => $this->t('- Add licence -'),
    ];
    return $form;
  }

  /**
   * Returns list of available licences to add to the compare table.
   *
   * @param \Drupal\rdf_entity\RdfInterface[] $licences
   *   An ordered list of Joinup licence entities keyed by their SPDX ID.
   *
   * @return array
   *   A list of licence labels indexed by their SPDX ID.
   */
  public function getLicenceOptions(array $licences = []): array {
    $rdf_storage = $this->entityTypeManager->getStorage('rdf_entity');

    $query = $rdf_storage->getQuery()->condition('rid', 'licence');
    if (!empty($licences)) {
      // Do not include licences already in the comparison page if any.
      $existing_ids = empty($licences) ? NULL : array_map(function (RdfInterface $licence): string {
        return $licence->id();
      }, $licences);
      $query->condition('id', $existing_ids, 'NOT IN');
    }

    // In any case, do not show licences that are not linked to an SPDX licence.
    $query->exists('field_licence_spdx_licence');
    $query->sort('label', 'ASC');
    $options = [];
    $ids = $query->execute();
    foreach ($rdf_storage->loadMultiple($ids) as $licence) {
      $spdx_licence = $licence->get('field_licence_spdx_licence')->entity;
      $options[$spdx_licence->get('field_spdx_licence_id')->value] = $licence->label() . ' (' . $spdx_licence->label() . ')';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}

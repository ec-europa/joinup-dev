<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
      '#attributes' => [
        'class' => ['auto_submit'],
      ],
      '#attached' => [
        'library' => [
          'joinup_licence/search_auto_submit',
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#attributes' => [
        'class' => ['licence-search-submit'],
      ],
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('licence_search');
    $licences = isset($form_state->getBuildInfo()['args'][0]['licences'])
      ? array_keys($form_state->getBuildInfo()['args'][0]['licences'])
      : [];
    $licences[] = $value;
    array_unique($licences);
    $licences_parameter = implode(';', $licences + [$value]);

    // Url::fromRoute encodes the parameters passed as a filter. In order to
    // show beautified URIs on the address bar, construct the URL from the route
    // so that the URI is created through the API, decode the generated string
    // and pass it as a Url::fromUri which does not encode the path since it is
    // an absolute URL.
    // The difference is that, instead of getting
    // http://test.com/licence/compare/Licence1%3BLicence2%3BLicence3
    // we get the normal
    // http://test.com/licence/compare/Licence1;Licence2;Licence3.
    $uri_string = Url::fromRoute('joinup_licence.comparer', ['licences' => $licences_parameter])
      ->setAbsolute()
      ->toString();
    $uri_string = urldecode($uri_string);
    $form_state->setRedirectUrl(Url::fromUri($uri_string));
  }

}

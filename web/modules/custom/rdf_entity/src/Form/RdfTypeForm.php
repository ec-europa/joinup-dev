<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for vocabulary edit forms.
 */
class RdfTypeForm extends BundleEntityFormBase {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $rdfTypeStorage;

  /**
   * Constructs a new rdf type form.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $rdf_type_storage
   *   The rdf type storage.
   */
  public function __construct(ConfigEntityStorageInterface $rdf_type_storage) {
    $this->rdfTypeStorage = $rdf_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('rdf_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rdf_entity\RdfEntityTypeInterface $rdf_type */
    $rdf_type = $this->entity;
    if ($rdf_type->isNew()) {
      $form['#title'] = $this->t('Add rdf type');
    }
    else {
      $form['#title'] = $this->t('Edit rdf type');
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $rdf_type->label(),
      '#maxlength' => 255,
      '#required' => TRUE,

    ];
    $form['rid'] = [
      '#type' => 'machine_name',
      '#default_value' => $rdf_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['name'],
      ],
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $rdf_type->get('description'),
    ];
    $form = parent::form($form, $form_state);
    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $rdf_type = $this->entity;

    // Prevent leading and trailing spaces in rdf_type names.
    $rdf_type->set('name', trim($rdf_type->label()));

    $status = $rdf_type->save();
    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created new rdf type %name.', ['%name' => $rdf_type->label()]));
        $this->logger('taxonomy')->notice('Created new rdf type %name.', [
          '%name' => $rdf_type->label(),
          'link' => $edit_link,
        ]);
        $form_state->setRedirectUrl($rdf_type->toUrl('overview-form'));
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('Updated rdf type %name.', ['%name' => $rdf_type->label()]));
        $this->logger('taxonomy')->notice('Updated rdf type %name.', [
          '%name' => $rdf_type->label(),
          'link' => $edit_link,
        ]);
        $form_state->setRedirectUrl($rdf_type->toUrl('collection'));
        break;
    }

    $form_state->setValue('rid', $rdf_type->id());
    $form_state->set('rid', $rdf_type->id());
    $form_state->setRedirect('entity.rdf_type.collection');
  }

  /**
   * Determines if the rdf type already exists.
   *
   * @param string $rid
   *   The rdf type ID.
   *
   * @return bool
   *   TRUE if the vocabulary exists, FALSE otherwise.
   */
  public function exists($rid) {
    $action = $this->rdfTypeStorage->load($rid);
    return !empty($action);
  }

}

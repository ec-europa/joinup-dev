<?php

/**
 * @file
 * Contains \Drupal\taxonomy\VocabularyForm.
 */

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for vocabulary edit forms.
 */
class RdfTypeForm extends BundleEntityFormBase {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface.
   */
  protected $vocabularyStorage;

  /**
   * Constructs a new vocabulary form.
   *
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(ConfigEntityStorage $vocabulary_storage) {
    $this->vocabularyStorage = $vocabulary_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('taxonomy_vocabulary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $rdf_type = $this->entity;
    if ($rdf_type->isNew()) {
      $form['#title'] = $this->t('Add vocabulary');
    }
    else {
      $form['#title'] = $this->t('Edit vocabulary');
    }

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $rdf_type->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['rid'] = array(
      '#type' => 'textfield',
      '#default_value' => $rdf_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $rdf_type->description,
    );
    $form['rdftype'] = array(
      '#type' => 'textfield',
      '#title' => t('Rdf base class name'),
      '#default_value' => $rdf_type->rdftype,
    );
    $form = parent::form($form, $form_state);
    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $vocabulary = $this->entity;

    // Prevent leading and trailing spaces in vocabulary names.
    $vocabulary->set('name', trim($vocabulary->label()));

    $status = $vocabulary->save();
    $edit_link = $this->entity->link($this->t('Edit'));
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new vocabulary %name.', array('%name' => $vocabulary->label())));
        $this->logger('taxonomy')->notice('Created new vocabulary %name.', array('%name' => $vocabulary->label(), 'link' => $edit_link));
        $form_state->setRedirectUrl($vocabulary->urlInfo('overview-form'));
        break;

      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated vocabulary %name.', array('%name' => $vocabulary->label())));
        $this->logger('taxonomy')->notice('Updated vocabulary %name.', array('%name' => $vocabulary->label(), 'link' => $edit_link));
        $form_state->setRedirectUrl($vocabulary->urlInfo('collection'));
        break;
    }

    $form_state->setValue('vid', $vocabulary->id());
    $form_state->set('vid', $vocabulary->id());
  }

  /**
   * Determines if the vocabulary already exists.
   *
   * @param string $vid
   *   The vocabulary ID.
   *
   * @return bool
   *   TRUE if the vocabulary exists, FALSE otherwise.
   */
  public function exists($vid) {
    $action = $this->vocabularyStorage->load($vid);
    return !empty($action);
  }

}

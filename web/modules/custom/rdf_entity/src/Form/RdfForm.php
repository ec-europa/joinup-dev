<?php
/**
 * @file
 * Contains Drupal\rdf_entity\Form\RdfForm.
 */

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rdf_entity\Entity\RdfEntityType;

/**
 * Form controller for the rdf_entity entity edit forms.
 *
 * @ingroup rdf_entity
 */
class RdfForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\rdf_entity\Entity\Rdf */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $type = RdfEntityType::load($entity->bundle());
    if ($type->label()) {
      // Add.
      if ($entity->isNew()) {
        $form['#title'] = $this->t('<em>Add @type</em>', array('@type' => $type->label()));
      }
      // Edit.
      else {
        $form['#title'] = $this->t('<em>Edit @type</em> @title', array('@type' => $type->label(), '@title' => $entity->label()));
      }
    }

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    $entity = $this->getEntity();
    $entity->save();
    $form_state->setRedirect(
      'entity.rdf_entity.canonical',
      ['rdf_entity' => $entity->id()]
    );
  }

}

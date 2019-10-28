<?php

declare(strict_types = 1);

namespace Drupal\solution\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_core\Form\UnshareForm as OriginalForm;
use Drupal\rdf_entity\RdfInterface;

/**
 * Form to unshare a solution from within collections.
 *
 * The methods are different from the parent class because the route is a sub
 * link of the `/rdf_entity/{rdf_entity}` route path. That means that we cannot
 * have the `{rdf_entity}` parameter named differently and also, even if the
 * rdf entity is implementing the EntityInterface, the ArgumentResolver would
 * not automatically assign it to another entity of the same type.
 */
class UnshareForm extends OriginalForm {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The entity being unshared.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL): array {
    return parent::doBuildForm($form, $form_state, $rdf_entity);
  }

  /**
   * Access callback for the unshare form.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The node entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RdfInterface $rdf_entity) {
    $this->entity = $rdf_entity;

    return AccessResult::allowedIf(!empty($this->getCollections()));
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(EntityInterface $rdf_entity) {
    return $this->t('Unshare %title from', ['%title' => $rdf_entity->label()]);
  }

}

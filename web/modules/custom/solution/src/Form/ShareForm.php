<?php

declare(strict_types = 1);

namespace Drupal\solution\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_group\Form\ShareForm as OriginalForm;
use Drupal\rdf_entity\RdfInterface;

/**
 * Form to share a solution inside collections.
 *
 * The methods are different from the parent class because the route is a sub
 * link of the `/rdf_entity/{rdf_entity}` route path. That means that we cannot
 * have the `{rdf_entity}` parameter named differently and also, even if the
 * rdf entity is implementing the EntityInterface, the ArgumentResolver would
 * not automatically assign it to another entity of the same type.
 */
class ShareForm extends OriginalForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RdfInterface $rdf_entity = NULL): array {
    return parent::doBuildForm($form, $form_state, $rdf_entity);
  }

  /**
   * Gets the title for the form route.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The entity being shared.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page/modal title.
   */
  public function getTitle(RdfInterface $rdf_entity): TranslatableMarkup {
    return parent::buildTitle($rdf_entity);
  }

}

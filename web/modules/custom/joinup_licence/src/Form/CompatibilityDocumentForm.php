<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the compatibility document entity edit forms.
 */
class CompatibilityDocumentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    $entity->save();
    $message_arguments = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus($this->t('The compatibility document %label has been updated.', $message_arguments));
  }

}

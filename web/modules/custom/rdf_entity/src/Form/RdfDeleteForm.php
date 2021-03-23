<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a content_entity_example entity.
 *
 * @ingroup content_entity_example
 */
class RdfDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $label = $this->entity->label();
    $graph = $this->entity->get('graph')->target_id;
    return $this->t('Are you sure you want to delete entity %name from the graph %graph?', [
      '%name' => $label,
      '%graph' => $graph,
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the Rdf list.
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. log() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $this->logger('rdf_entity')->notice('@type: deleted %title.', [
      '@type' => $this->entity->bundle(),
      '%title' => $this->entity->label(),
    ]);

    if (empty($form_state->getRedirect())) {
      $form_state->setRedirectUrl(Url::fromRoute('<front>'));
    }
  }

}

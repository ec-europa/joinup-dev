<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_core\Form\ShareForm as OriginalForm;
use Drupal\node\NodeInterface;

/**
 * Form to share a solution inside collections.
 *
 * The methods are different from the parent class because the route is a sub
 * link of the `/node/{node}` route path. That means that we cannot
 * have the `{node}` parameter named differently and also, even if the
 * rdf entity is implementing the EntityInterface, the ArgumentResolver would
 * not automatically assign it to another entity of the same type.
 */
class ShareForm extends OriginalForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'share_content_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\node\NodeInterface $node
   *   The entity being shared.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL): array {
    return parent::doBuildForm($form, $form_state, $node);
  }

  /**
   * Gets the title for the form route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The entity being shared.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page/modal title.
   */
  public function getTitle(NodeInterface $node): TranslatableMarkup {
    return parent::buildTitle($node);
  }

}

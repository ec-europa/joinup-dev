<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_group\Form\UnshareForm as OriginalForm;
use Drupal\node\NodeInterface;

/**
 * Form to unshare a community content from within collections.
 *
 * The methods are different from the parent class because the route is a sub
 * link of the `/node/{node}` route path. That means that we cannot
 * have the `{node}` parameter named differently and also, even if the
 * rdf entity is implementing the EntityInterface, the ArgumentResolver would
 * not automatically assign it to another entity of the same type.
 */
class UnshareForm extends OriginalForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL): array {
    return parent::doBuildForm($form, $form_state, $node);
  }

  /**
   * Access callback for the unshare form.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(NodeInterface $node) {
    $this->entity = $node;

    return AccessResult::allowedIf(!empty($this->getCollections()));
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(EntityInterface $node) {
    return $this->t('Unshare %title from', ['%title' => $node->label()]);
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_core\Form\UnshareForm as OriginalForm;
use Drupal\node\NodeInterface;

/**
 * Form to unshare a community content from within collections.
 */
class UnshareForm extends OriginalForm {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\node\NodeInterface $node
   *   The entity being unshared.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL): array {
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

<?php

namespace Drupal\joinup_community_content\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Drupal\rdf_entity\RdfEntityViewBuilder;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to share a community content inside collections.
 */
class ShareContentForm extends FormBase {

  use StringTranslationTrait;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The node being shared.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The RDF view builder.
   *
   * @var \Drupal\rdf_entity\RdfEntityViewBuilder
   */
  protected $rdfBuilder;

  /**
   * The RDF entity storage.
   *
   * @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage
   */
  protected $rdfStorage;

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(RdfEntitySparqlStorage $rdf_storage, RdfEntityViewBuilder $rdf_builder, JoinupRelationManager $relation_manager, MembershipManagerInterface $membership_manager, AccountInterface $current_user) {
    $this->rdfStorage = $rdf_storage;
    $this->rdfBuilder = $rdf_builder;
    $this->relationManager = $relation_manager;
    $this->membershipManager = $membership_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('rdf_entity'),
      $container->get('entity_type.manager')->getViewBuilder('rdf_entity'),
      $container->get('joinup_core.relations_manager'),
      $container->get('og.membership_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'share_content_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $this->node = $node;

    // Wrap all the elements with a fieldset, like the "checkboxes" element
    // does. So we can use a label for all the elements.
    // @see CompositeFormElementTrait::preRenderCompositeFormElement()
    $form['collections'] = [
      '#theme_wrappers' => ['fieldset'],
      '#title' => $this->t('Collections'),
      '#tree' => TRUE,
    ];

    $already_shared = $this->getAlreadySharedCollectionIds();
    foreach ($this->getShareableCollections() as $id => $collection) {
      $wrapper_id = Html::getId($id) . '--wrapper';
      $is_shared = in_array($id, $already_shared);

      $form['collections'][$id] = [
        '#type' => 'container',
        '#id' => $wrapper_id,
        'entity' => $this->rdfBuilder->view($collection, 'compact'),
        'checkbox' => [
          '#type' => 'checkbox',
          '#title' => $collection->label(),
          // Replicate the behaviour of the "checkboxes" element again.
          // @see \Drupal\Core\Render\Element\Checkboxes::processCheckboxes
          '#return_value' => $id,
          '#default_value' => $is_shared ? $id : NULL,
          '#ajax' => [
            'callback' => '::checkboxChange',
            'wrapper' => $wrapper_id,
          ],
          // Drop the extra "checkbox" key.
          '#parents' => ['collections', $id],
          // Transform the checkbox into a submit-enabled input.
          '#executes_submit_callback' => TRUE,
        ],
      ];

      // Add a class when the collection is already shared.
      if ($is_shared) {
        $form['collections'][$id]['#attributes']['class'][] = 'already-shared';
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#access' => !$this->isModal(),
    ];

    return $form;
  }

  /**
   * Returns the updated row for the checkbox element.
   */
  public function checkboxChange(array &$form, FormStateInterface $form_state) {
    // Fetch the checkbox that triggered the ajax, and return its wrapper.
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#array_parents'];
    array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $collections = $form_state->getValue('collections');

    // We can safely loop through these ids, as unvalid options are handled
    // already by Drupal.
    foreach ($collections as $id => $value) {
      $collection = $this->rdfStorage->load($id);
      $value !== $id ? $this->removeFromCollection($collection) : $this->shareInCollection($collection);
    }

    $form_state->setRebuild();

    // Provide an extra visual clue that the options have been saved, but only
    // when Javascript is disabled. The form will have visual clues that
    // indicate that the content is already shared for the remaining cases.
    if (!$this->isModal() && !$this->isAjaxForm()) {
      drupal_set_message($this->t('Sharing updated.'));
    }
  }

  /**
   * Access check for the form route.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The entity being shared.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed if there is at least one collection where the node can be shared.
   */
  public function access(NodeInterface $node) {
    $this->node = $node;

    return AccessResult::allowedIf(!empty($this->getShareableCollections()));
  }

  /**
   * Retrieves the collections a user is member of.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections the current user is member of.
   */
  protected function getUserCollections() {
    $groups = $this->membershipManager->getUserGroups($this->currentUser);

    if (empty($groups['rdf_entity'])) {
      return [];
    }

    $collections = array_filter($groups['rdf_entity'], function ($entity) {
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      return $entity->bundle() === 'collection';
    });

    return $collections;
  }

  /**
   * Retrieves a list of collections where the current node can be shared.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections where the current node can be shared.
   */
  protected function getShareableCollections() {
    $user_collections = $this->getUserCollections();
    $node_parent = $this->relationManager->getParent($this->node);

    // We cannot share in the parent collection.
    if ($node_parent->bundle() === 'collection' && isset($user_collections[$node_parent->id()])) {
      unset($user_collections[$node_parent->id()]);
    }

    return $user_collections;
  }

  /**
   * Gets a list of collection ids where the current node is already shared.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collection ids where the current node is already shared in.
   */
  protected function getAlreadySharedCollectionIds() {
    return array_column($this->node->get('field_shared_in')->getValue(), 'target_id');
  }

  /**
   * Shares the current node inside a collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to share the node.
   */
  protected function shareInCollection(RdfInterface $collection) {
    $current_ids = $this->getAlreadySharedCollectionIds();
    $current_ids[] = $collection->id();

    // Entity references do not ensure uniqueness.
    $current_ids = array_unique($current_ids);

    $this->node->get('field_shared_in')->setValue($current_ids);
    $this->node->save();
  }

  /**
   * Removes the current node from being shared inside a collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to remove the node.
   */
  protected function removeFromCollection(RdfInterface $collection) {
    // Flipping is needed to easily unset the value.
    $current_ids = array_flip($this->getAlreadySharedCollectionIds());
    unset($current_ids[$collection->id()]);
    $this->node->get('field_shared_in')->setValue(array_flip($current_ids));
    $this->node->save();
  }

  /**
   * Returns whether the form is displayed in a modal.
   *
   * @return bool
   *   TRUE if the form is displayed in a modal.
   *
   * @todo Remove when issue #2661046 is in.
   *
   * @see https://www.drupal.org/node/2661046
   */
  protected function isModal() {
    return $this->getRequest()->query->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal';
  }

  /**
   * Returns whether the form is being handled with an ajax request.
   *
   * @return bool
   *   TRUE if the form is being handled through an AJAX request.
   */
  protected function isAjaxForm() {
    return $this->getRequest()->query->has(FormBuilderInterface::AJAX_FORM_REQUEST);
  }

}

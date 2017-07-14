<?php

namespace Drupal\joinup_community_content\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to share a community content inside collections.
 */
class ShareContentForm extends ShareContentFormBase {

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Constructs a new ShareContentFormBase object.
   *
   * @param \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $rdf_storage
   *   The RDF entity storage.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $rdf_builder
   *   The RDF view builder.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   * @param \Drupal\joinup_core\JoinupRelationManager $relation_manager
   *   The Joinup relation manager.
   */
  public function __construct(RdfEntitySparqlStorage $rdf_storage, EntityViewBuilderInterface $rdf_builder, MembershipManagerInterface $membership_manager, AccountInterface $current_user, JoinupRelationManager $relation_manager) {
    parent::__construct($rdf_storage, $rdf_builder, $membership_manager, $current_user);

    $this->relationManager = $relation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('rdf_entity'),
      $container->get('entity_type.manager')->getViewBuilder('rdf_entity'),
      $container->get('og.membership_manager'),
      $container->get('current_user'),
      $container->get('joinup_core.relations_manager')
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
    $form = parent::buildForm($form, $form_state, $node);

    $form['share'] = [
      '#theme' => 'social_share',
      '#entity' => $this->node,
    ];

    $collections = $this->getShareableCollections();

    // Wrap all the elements with a fieldset, like the "checkboxes" element
    // does. So we can use a label for all the elements.
    // @see CompositeFormElementTrait::preRenderCompositeFormElement()
    $form['collections'] = [
      '#theme_wrappers' => ['fieldset'],
      '#title' => $this->t('Collections'),
      '#title_display' => 'invisible',
      '#tree' => TRUE,
      '#access' => !empty($collections),
    ];

    foreach ($collections as $id => $collection) {
      $form['collections'][$id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['share-box__row'],
        ],
        'entity' => $this->rdfBuilder->view($collection, 'compact'),
        'checkbox' => [
          '#type' => 'checkbox',
          '#title' => $collection->label(),
          // Replicate the behaviour of the "checkboxes" element again.
          // @see \Drupal\Core\Render\Element\Checkboxes::processCheckboxes
          '#return_value' => $id,
          '#default_value' => FALSE,
          // Drop the extra "checkbox" key.
          '#parents' => ['collections', $id],
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => empty($collections) ? $this->t('Close') : $this->t('Save'),
    ];

    if ($this->isModal() || $this->isAjaxForm()) {
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxSubmit',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Keep only the checked entries.
    $collections = array_filter($form_state->getValue('collections'));

    // We can safely loop through these ids, as unvalid options are handled
    // already by Drupal.
    foreach ($collections as $id => $value) {
      $collection = $this->rdfStorage->load($id);
      $this->shareInCollection($collection);
    }

    // Show a message if the content was shared in at least one collection.
    if (!$this->isAjaxForm() && !empty($collections)) {
      drupal_set_message('Sharing updated.');
    }

    $form_state->setRedirectUrl($this->node->toUrl());
  }

  /**
   * Ajax callback to close the modal.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response that will close the modal.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
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
  public function getTitle(NodeInterface $node) {
    if ($this->isModal() || $this->isAjaxForm()) {
      return $this->t('Share in');
    }
    else {
      return $this->t('Share %title in', ['%title' => $node->label()]);
    }
  }

  /**
   * Retrieves a list of collections where the current node can be shared.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections where the current node can be shared.
   */
  protected function getShareableCollections() {
    // If the node has no field, it's not shareable anywhere.
    if (!$this->node->hasField('field_shared_in')) {
      return [];
    }

    $user_collections = $this->getUserCollections();
    $node_parent = $this->relationManager->getParent($this->node);

    // We cannot share in the parent collection.
    if ($node_parent->bundle() === 'collection' && isset($user_collections[$node_parent->id()])) {
      unset($user_collections[$node_parent->id()]);
    }

    return array_diff_key($user_collections, array_flip($this->getAlreadySharedCollectionIds()));
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

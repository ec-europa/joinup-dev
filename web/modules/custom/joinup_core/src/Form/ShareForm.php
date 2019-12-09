<?php

namespace Drupal\joinup_core\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgRoleManagerInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to share a community content inside collections.
 */
abstract class ShareForm extends ShareFormBase {

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $relationManager;

  /**
   * Constructs a new ShareContentFormBase object.
   *
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorage $sparql_storage
   *   The RDF entity storage.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $rdf_builder
   *   The RDF view builder.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   * @param \Drupal\og\OgRoleManagerInterface $role_manager
   *   The OG role manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relation_manager
   *   The Joinup relation manager.
   */
  public function __construct(SparqlEntityStorage $sparql_storage, EntityViewBuilderInterface $rdf_builder, MembershipManagerInterface $membership_manager, OgRoleManagerInterface $role_manager, AccountInterface $current_user, MessengerInterface $messenger, JoinupRelationManagerInterface $relation_manager) {
    parent::__construct($sparql_storage, $rdf_builder, $membership_manager, $role_manager, $current_user, $messenger);
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
      $container->get('og.role_manager'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getFormId();

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being shared.
   *
   * @return array
   *   The form structure.
   */
  public function doBuildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL) {
    $this->entity = $entity;

    $form['share'] = [
      '#theme' => 'social_share',
      '#entity' => $this->entity,
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
      '#extra_suggestion' => 'light_blue',
      '#value' => empty($collections) ? $this->t('Close') : $this->t('Share'),
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
    $collection_labels = [];
    // We can safely loop through these ids, as unvalid options are handled
    // already by Drupal.
    foreach ($collections as $id => $value) {
      $collection = $this->sparqlStorage->load($id);
      $this->shareInCollection($collection);
      $collection_labels[] = $collection->label();
    }

    // Show a message if the content was shared in at least one collection.
    if (!empty($collections)) {
      $this->messenger->addStatus('Item was shared in the following collections: ' . implode(', ', $collection_labels) . '.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());
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
    $response->addCommand(new RedirectCommand((string) $this->entity->toUrl()->toString()));

    return $response;
  }

  /**
   * Gets the title for the form route.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being shared.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page/modal title.
   */
  public function buildTitle(EntityInterface $entity) {
    if ($this->isModal() || $this->isAjaxForm()) {
      return $this->t('Share in');
    }
    else {
      return $this->t('Share %title in', ['%title' => $entity->label()]);
    }
  }

  /**
   * Retrieves a list of collections where the entity can be shared in.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections where the current entity can be shared in.
   */
  protected function getShareableCollections() {
    // Being part also for the access check, do not allow the user to access
    // this page for entities without a field to store collections it is shared
    // in.
    if (!$this->entity->hasField($this->getSharedInFieldName())) {
      return [];
    }

    $user_collections = $this->getUserGroupsByPermission($this->getPermissionForAction('share'));
    if ($parent = $this->getExcludedParent()) {
      unset($user_collections[$parent->id()]);
    }

    return array_diff_key($user_collections, array_flip($this->getAlreadySharedCollectionIds()));
  }

  /**
   * Returns a list of groups that the entity cannot be shared in.
   *
   * For nodes, this is the parent group. For rdf entities, it is the affiliated
   * collections of the solution.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The affiliated or parent collection, if one exists.
   */
  protected function getExcludedParent(): ?RdfInterface {
    if ($this->entity->getEntityTypeId() === 'node') {
      return $this->relationManager->getParent($this->entity);
    }
    else {
      return $this->entity->get('collection')->isEmpty() ? NULL : $this->entity->get('collection')->first()->entity;
    }
  }

  /**
   * Shares the current entity inside a collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to share the entity in.
   */
  protected function shareInCollection(RdfInterface $collection) {
    $current_ids = $this->getAlreadySharedCollectionIds();
    $current_ids[] = $collection->id();

    // Entity references do not ensure uniqueness.
    $current_ids = array_unique($current_ids);

    $this->entity->get($this->getSharedInFieldName())->setValue($current_ids);
    $this->entity->save();
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

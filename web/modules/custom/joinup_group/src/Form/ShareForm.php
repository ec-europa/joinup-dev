<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_group\JoinupRelationManagerInterface;
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
   * The group relation info service.
   *
   * @var \Drupal\joinup_group\JoinupRelationManagerInterface
   */
  protected $relationInfo;

  /**
   * Constructs a new ShareForm.
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
   * @param \Drupal\joinup_group\JoinupRelationManagerInterface $relation_info
   *   The group relation info service.
   */
  public function __construct(SparqlEntityStorage $sparql_storage, EntityViewBuilderInterface $rdf_builder, MembershipManagerInterface $membership_manager, OgRoleManagerInterface $role_manager, AccountInterface $current_user, MessengerInterface $messenger, JoinupRelationManagerInterface $relation_info) {
    parent::__construct($sparql_storage, $rdf_builder, $membership_manager, $role_manager, $current_user, $messenger);
    $this->relationInfo = $relation_info;
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
      $container->get('joinup_group.relation_info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'share_content_form';
  }

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
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the group reference is not populated.
   */
  public function doBuildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL): array {
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Keep only the checked entries.
    $collections = array_filter($form_state->getValue('collections'));
    $collection_labels = [];
    // We can safely loop through these ids, as unvalid options are handled
    // already by Drupal.
    foreach ($collections as $id => $value) {
      $collection = $this->sparqlStorage->load($id);
      $this->shareOnCollection($collection);
      $collection_labels[] = $collection->label();
    }

    // Show a message if the content was shared on at least one collection.
    if (!empty($collections)) {
      $this->messenger->addStatus('Item was shared on the following collections: ' . implode(', ', $collection_labels) . '.');
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
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown when the canonical URL cannot be generated for the shared entity.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
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
  public function buildTitle(EntityInterface $entity): TranslatableMarkup {
    if ($this->isModal() || $this->isAjaxForm()) {
      return $this->t('Share on');
    }
    else {
      return $this->t('Share %title on', ['%title' => $entity->label()]);
    }
  }

  /**
   * Retrieves a list of collections where the entity can be shared on.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections where the current entity can be shared on.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the group reference is not populated.
   */
  protected function getShareableCollections(): array {
    // Being part also for the access check, do not allow the user to access
    // this page for entities without a field to store collections it is shared
    // in.
    if (!$this->entity->hasField($this->getSharedOnFieldName())) {
      return [];
    }

    $user_collections = $this->getUserGroupsByPermission($this->getPermissionForAction('share'));
    if ($parent = $this->getExcludedParent()) {
      unset($user_collections[$parent->id()]);
    }

    return array_diff_key($user_collections, array_flip($this->getAlreadySharedCollectionIds()));
  }

  /**
   * Returns a list of groups that the entity cannot be shared on.
   *
   * For nodes, this is the parent group. For rdf entities, it is the affiliated
   * collections of the solution.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The affiliated or parent collection, if one exists.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the group reference is not populated.
   */
  protected function getExcludedParent(): ?RdfInterface {
    if ($this->entity->getEntityTypeId() === 'node') {
      return $this->relationInfo->getParent($this->entity);
    }
    else {
      return $this->entity->get('collection')->isEmpty() ? NULL : $this->entity->get('collection')->first()->entity;
    }
  }

  /**
   * Shares the current entity inside a collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection where to share the entity on.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the current entity cannot be retrieved from the database.
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   *   Thrown when the entity storage is read only.
   */
  protected function shareOnCollection(RdfInterface $collection): void {
    $current_ids = $this->getAlreadySharedCollectionIds();
    $current_ids[] = $collection->id();

    // Entity references do not ensure uniqueness.
    $current_ids = array_unique($current_ids);

    $this->entity->get($this->getSharedOnFieldName())->setValue($current_ids);
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
  protected function isModal(): bool {
    return $this->getRequest()->query->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal';
  }

  /**
   * Returns whether the form is being handled with an ajax request.
   *
   * @return bool
   *   TRUE if the form is being handled through an AJAX request.
   */
  protected function isAjaxForm(): bool {
    return $this->getRequest()->query->has(FormBuilderInterface::AJAX_FORM_REQUEST);
  }

}

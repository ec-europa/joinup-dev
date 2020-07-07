<?php

declare(strict_types = 1);

namespace Drupal\collection\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form suggesting the user to subscribe to a collection after joining.
 *
 * This form is shown in a modal dialog after the user joins a collection.
 *
 * @see \Drupal\collection\Form\JoinCollectionForm::showSubscribeDialog()
 */
class SubscribeToCollectionForm extends FormBase {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a SubscribeToCollectionForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'subscribe_to_collection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RdfInterface $rdf_entity = NULL): array {
    // This form should only be accessible if the user is already a member of
    // the collection.
    $membership = $this->getUserNonBlockedMembership($rdf_entity);
    $form['#access'] = (bool) $membership;

    // Keep track of the collection we are subscribing to.
    $form['collection_id'] = [
      '#type' => 'value',
      '#value' => $rdf_entity->id(),
    ];

    // Check if the user is pending, so we can adapt the messages for the user.
    $is_pending = !empty($membership) && $membership->isPending();

    $intro_message = $is_pending ?
      $this->t('When your membership is approved you will be able to publish content in it.') :
      $this->t('You have joined the collection and you are now able to publish content in it.');

    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $intro_message,
    ];
    $form['proposal'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Want to receive notifications, too?'),
    ];
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You can receive weekly notifications for this collection, by selecting the subscribe button below:'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('No thanks'),
      '#extra_suggestion' => 'light_blue',
    ];

    $form['actions']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
      // This form is opened via AJAX in a modal dialog that is triggered by
      // submitting the JoinCollectionForm. By default Drupal will send the
      // AJAX requests to the route of the original form, which in this case
      // would be the JoinCollectionForm. We need to manually specify the
      // callback URL and the query argument that triggers AJAX so that this
      // form will be loaded.
      // @see https://www.drupal.org/project/drupal/issues/2934463
      '#ajax' => [
        'callback' => [static::class, 'confirmSubscription'],
        'url' => Url::fromRoute('collection.subscribe_to_collection_form', [
          'rdf_entity' => $rdf_entity->id(),
        ]),
        'options' => [
          'query' => [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $collection = $this->loadCollection($form_state->getValue('collection_id'));

    // The user should be a non-blocked member of the collection.
    $membership = $this->getUserNonBlockedMembership($collection);
    if (empty($membership)) {
      $form_state->setErrorByName('collection', $this->t('Please join the collection before subscribing to it.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $collection = $this->loadCollection($form_state->getValue('collection_id'));

    $membership = $this->getUserNonBlockedMembership($collection);
    $membership->set('subscription_bundles', array_map(function (string $bundle): array {
      return ['entity_type' => 'node', 'bundle' => $bundle];
    }, CommunityContentHelper::BUNDLES))->save();

    // Check if the user is pending, so we can adapt the messages for the user.
    $is_pending = !empty($membership) && $membership->isPending();

    $parameters = ['%collection' => $collection->getName()];
    $message = $is_pending ?
      $this->t('You have been subscribed to %collection and will receive weekly notifications once your membership is approved.', $parameters) :
      $this->t('You have been subscribed to %collection and will receive weekly notifications. To manage your notifications go to <em>My subscriptions</em> in your user menu.', $parameters);
    $this->messenger->addStatus($message);
  }

  /**
   * AJAX callback showing a form to subscribe to the collection after joining.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function confirmSubscription(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Output messages in the page.
    $messages = ['#type' => 'status_messages'];
    $response->addCommand(new PrependCommand('.section--content-top', $messages));

    if (!$form_state->getErrors()) {
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

  /**
   * Access check for the form.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RdfInterface $rdf_entity): AccessResultInterface {
    $user = $this->getUser();
    return AccessResult::allowedIf($user->isAuthenticated() && !empty($this->getUserNonBlockedMembership($rdf_entity)));
  }

  /**
   * Returns a membership of the user that is active or pending.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The group entity.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   The membership of the user or null.
   */
  protected function getUserNonBlockedMembership(RdfInterface $collection): ?OgMembershipInterface {
    return $this->membershipManager->getMembership($collection, $this->getUser()->id(), [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
    ]);
  }

  /**
   * Loads the collection with the given ID.
   *
   * @param string $collection_id
   *   The collection ID.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The collection.
   */
  protected function loadCollection(string $collection_id): RdfInterface {
    return $this->entityTypeManager->getStorage('rdf_entity')->load($collection_id);
  }

  /**
   * Returns the full user entity object for the current user.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  protected function getUser(): UserInterface {
    return $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
  }

}

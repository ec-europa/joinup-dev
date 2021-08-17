<?php

declare(strict_types = 1);

namespace Drupal\collection\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\joinup_subscription\JoinupSubscriptionsHelper;
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
 * @todo Move this into the joinup_subscription module to solve a circular
 *   dependency.
 * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6330
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
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The time keeping service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a SubscribeToCollectionForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time keeping service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager, MessengerInterface $messenger, FormBuilderInterface $form_builder, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
    $this->messenger = $messenger;
    $this->formBuilder = $form_builder;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('messenger'),
      $container->get('form_builder'),
      $container->get('datetime.time')
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
        'callback' => '::confirmSubscription',
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

    $subscription_bundles = [];
    foreach (JoinupSubscriptionsHelper::SUBSCRIPTION_BUNDLES['collection'] as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        $subscription_bundles[] = [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
        ];
      }
    }

    $membership = $this->getUserNonBlockedMembership($collection);
    $membership->set('subscription_bundles', $subscription_bundles)->save();

    // Check if the user is pending, so we can adapt the messages for the user.
    $is_pending = !empty($membership) && $membership->isPending();

    $parameters = ['%collection' => $collection->getName()];
    $message = $is_pending ?
      $this->t('You have been subscribed to %collection and will receive weekly notifications once your membership is approved.', $parameters) :
      $this->t('You have been subscribed to %collection and will receive weekly notifications. To manage your notifications go to <em>My subscriptions</em> in your user menu.', $parameters);
    $this->messenger->addStatus($message);
  }

  /**
   * AJAX callback showing a confirmation after joining, and closing the modal.
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
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RdfInterface $rdf_entity): AccessResultInterface {
    $user = $this->getUser();
    return AccessResult::allowedIf($user->isAuthenticated() && !empty($this->getUserNonBlockedMembership($rdf_entity)));
  }

  /**
   * Access check for showing the subscribe form after authenticating a user.
   *
   * When an anonymous user wants to join a collection they first need to log in
   * and are then redirected back to the collection homepage so they can opt in
   * to the notification subscription. The desired collection is tracked in a
   * cookie. At the moment the user is logged in they will immediately become a
   * member of the collection but still need to opt in to notifications.
   *
   * This validates the following before showing the dialog:
   * - The cookie keeping track of the collection to join is present.
   * - The cookie tracks this collection.
   * - The user is an unblocked member of the collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection the user has joined.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\joinup_group\EventSubscriber\JoinGroupSubscriber::onLogin()
   */
  public function accessToSubscribeDialogAfterAuthenticating(RdfInterface $rdf_entity): AccessResultInterface {
    $cookie_id = $this->getRequest()->cookies->get('join_group', '');
    $user = $this->getUser();
    return AccessResult::allowedIf(
      $cookie_id === $rdf_entity->id()
      && $user->isAuthenticated()
      && !empty($this->getUserNonBlockedMembership($rdf_entity))
    );
  }

  /**
   * AJAX callback showing the subscribe form in a modal dialog.
   *
   * This is shown in response to a cookie being present which was set when the
   * user expressed their desire to join the collection before they logged in.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection the user has joined.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function showSubscribeDialog(RdfInterface $rdf_entity): AjaxResponse {
    $title = $this->t('Welcome to %collection', ['%collection' => $rdf_entity->label()]);

    $modal_form = $this->formBuilder->getForm(SubscribeToCollectionForm::class, $rdf_entity);
    $modal_form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($title, $modal_form, ['width' => '500']));

    // This modal should only be shown once, immediately after the user logs in.
    // Deleting the cookie ensures it will not be shown again.
    setcookie('join_group', '', $this->time->getRequestTime() - 86400, '/');

    // Ensure the response is not cached, this code needs to run so the cookie
    // can be deleted.
    $response->setMaxAge(0);

    return $response;
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

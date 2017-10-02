<?php

namespace Drupal\contact_form\EventSubscriber;

use Drupal\contact_form\ContactFormEvents;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Url;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\EventSubscriber\NotificationSubscriberBase;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class responsible to send the email after a contact form.
 */
class NotificationSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ContactFormEvents::CONTACT_FORM_EVENT] = ['contactForm'];
    return $events;
  }

  /**
   * The callback method for the contact_form.notify event.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function contactForm(NotificationEvent $event) {
    if ($event->getOperation() !== 'contact') {
      return;
    }

    $category = $event->getEntity()->get('field_contact_category')->first()->value;

    // Separately handle the reporting of content.
    if ($category === 'report') {
      $this->sendReportMessage($event);
      return;
    }

    // If it is not a report category, follow the normal process.
    $user_ids = $this->getRecipientIdsByRole('moderator');
    /** @var \Drupal\message\MessageInterface $message */
    $message = $event->getEntity();
    $message->save();

    foreach ($this->entityTypeManager->getStorage('user')->loadMultiple(array_filter($user_ids)) as $user_id => $user) {
      /** @var \Drupal\user\Entity\User $user */
      $options = ['save on success' => FALSE, 'mail' => $user->getEmail()];
      $this->messageNotifier->send($message, $options);
    }
  }

  /**
   * Determines the entity object of the route matching a uri.
   *
   * @param string $uri
   *   The internal or external uri.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object or null in any other case.
   */
  protected function getEntityFromUrl($uri) {
    try {
      $url = Url::fromUri($uri);
    }
    catch (Exception $e) {
      return NULL;
    }

    if (!$url->isRouted()) {
      return NULL;
    }

    $route_parameters = $url->getRouteParameters();
    if (!isset($route_parameters['node']) && !isset($route_parameters['rdf_entity'])) {
      return NULL;
    }

    // Check what type of content we are reporting.
    $entity_type_id = NULL;
    if (isset($route_parameters['node'])) {
      $entity_type_id = 'node';
    }
    elseif (isset($route_parameters['rdf_entity'])) {
      $entity_type_id = 'rdf_entity';
    }
    if ($entity_type_id === NULL) {
      return NULL;
    }

    if ($url->getRouteName() !== "entity.{$entity_type_id}.canonical") {
      return NULL;
    }

    $entity_id = $route_parameters[$entity_type_id];
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);

    return $entity;
  }

  /**
   * Sends a report message instead of the normal contact form email.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  protected function sendReportMessage(NotificationEvent $event) {
    $message = $event->getEntity();
    $entity = $this->getEntityFromUrl($message->get('field_contact_url')->first()->uri);
    // If there is no entity found, do not send an email.
    if (empty($entity)) {
      return;
    }
    $user_data = $this->getReportUserData();
    $user_data = $this->getUsersMessages($user_data, $entity);
    $arguments = $this->generateReportArguments($message);
    $this->sendUserDataMessages($user_data, $arguments);
  }

  /**
   * {@inheritdoc}
   *
   * Skip generating the arguments during the sending process.
   */
  protected function sendUserDataMessages(array $user_data, array $arguments = []) {
    foreach ($user_data as $template_id => $user_ids) {
      $values = ['template' => $template_id, 'arguments' => $arguments];
      $message = Message::create($values);
      $message->save();

      foreach ($user_ids as $user_id) {
        /** @var \Drupal\user\Entity\User $user */
        $user = $this->entityTypeManager->getStorage('user')->load($user_id);
        if ($user->isAnonymous()) {
          continue;
        }
        $options = ['save on success' => FALSE, 'mail' => $user->getEmail()];
        $this->messageNotifier->send($message, $options);
      }
    }
  }

  /**
   * Returns the user data array from which the recipients are built.
   *
   * Only the report differs from the rest of the categories in terms of both
   * the message and the recipients.
   *
   * @return array
   *   The user data array.
   */
  protected function getReportUserData() {
    return [
      'roles' => [
        'moderator' => [
          'report_contact_form',
        ],
      ],
      'og_roles' => [
        'rdf_entity-collection-administrator' => [
          'report_contact_form',
        ],
        'rdf_entity-solution-administrator' => [
          'report_contact_form',
        ],
      ],
    ];
  }

  /**
   * Generates arguments for the report message.
   *
   * @param \Drupal\Core\Entity\EntityInterface $message
   *   The message entity from the form.
   *
   * @return array
   *   An array of arguments, keyed by the token-styled id.
   */
  protected function generateReportArguments(EntityInterface $message) {
    $arguments = [];
    $arguments['@actor:field_user_first_name'] = $message->get('field_contact_first_name')->first()->value;
    $arguments['@actor:field_user_last_name'] = $message->get('field_contact_last_name')->first()->value;
    $arguments['@actor:full_name'] = $arguments['@actor:field_user_first_name'] . ' ' . $arguments['@actor:field_user_last_name'];
    $arguments['@legal_notice:url'] = Url::fromRoute('joinup.legal_notice', [], ['absolute' => TRUE])->toString();
    $arguments['@message:subject'] = $message->get('field_contact_subject')->first()->value;
    $arguments['@message:message'] = strip_tags($message->get('field_contact_message')->first()->value);
    if (!empty($message->get('field_contact_url')->first()->uri)) {
      $reported = $this->getEntityFromUrl($message->get('field_contact_url')->first()->uri);
      $arguments['@entity:title'] = $reported->label();
      $arguments['@entity:url'] = $reported->toUrl('canonical', ['absolute' => TRUE])->toString();
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateArguments(EntityInterface $entity) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigurationName() {
    return NULL;
  }

}

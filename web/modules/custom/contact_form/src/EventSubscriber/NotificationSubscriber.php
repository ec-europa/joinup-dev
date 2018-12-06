<?php

declare(strict_types = 1);

namespace Drupal\contact_form\EventSubscriber;

use Drupal\contact_form\ContactFormEvents;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\EventSubscriber\NotificationSubscriberBase;
use Drupal\user\Entity\User;
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
      $event->setSuccess($this->sendReportMessage($event));
      return;
    }

    // If it is not a report category, follow the normal process.
    $recipient = $this->configFactory->get('contact_form.settings')->get('default_recipient');
    /** @var \Drupal\message\MessageInterface $message */
    $message = $event->getEntity();
    $result = $this->messageDelivery->sendMessageToEmailAddresses($message, [$recipient]);
    $event->setSuccess($result);
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
    catch (\Exception $e) {
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
   *
   * @return bool
   *   If the delivery succeeded.
   */
  protected function sendReportMessage(NotificationEvent $event) {
    $message = $event->getEntity();
    $entity = $this->getEntityFromUrl($message->get('field_contact_url')->first()->uri);
    // If there is no entity found, do not send an email.
    if (empty($entity)) {
      return FALSE;
    }
    $user_data = $this->getReportUserData();
    $user_data = $this->getUsersMessages($user_data, $entity);
    $arguments = $this->generateReportArguments($message);
    return $this->sendUserDataMessages($user_data, $arguments);
  }

  /**
   * {@inheritdoc}
   *
   * Skip generating the arguments during the sending process.
   */
  protected function sendUserDataMessages(array $user_data, array $arguments = []) : bool {
    $success = TRUE;
    foreach ($user_data as $template_id => $user_ids) {
      $success = $this->messageDelivery->sendMessageTemplateToMultipleUsers($template_id, $arguments, User::loadMultiple($user_ids)) && $success;
    }
    return $success;
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
  protected function generateArguments(EntityInterface $entity): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigurationName() {
    return NULL;
  }

}

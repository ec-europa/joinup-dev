<?php

declare(strict_types = 1);

namespace Drupal\contact_form\EventSubscriber;

use Drupal\contact_form\ContactFormEvents;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_notification\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to contact form submission by notifying the sender.
 */
class SenderNotificationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new \Drupal\contact\MailHandler object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   */
  public function __construct(MailManagerInterface $mail_manager) {
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // This subscriber runs after NotificationSubscriber::contactForm()
      // @see \Drupal\contact_form\EventSubscriber\NotificationSubscriber::getSubscribedEvents()
      ContactFormEvents::CONTACT_FORM_EVENT => ['notifySender', -10],
    ];
  }

  /**
   * Sends a confirmation notification to the contact form submission sender.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function notifySender(NotificationEvent $event): void {
    if (!$event->isSuccessful()) {
      // If the submission didn't succeed, we are not sending any notification
      // to the sender. Instead, he will get an error message on the screen.
      // @see contact_form_form_message_contact_form_submission_submit()
      return;
    }

    /** @var \Drupal\message\MessageInterface $message */
    $message = $event->getEntity();
    $to = $message->get('field_contact_email')->value;
    $langcode = $message->language()->getId();
    $params = ['message' => $message];
    $this->mailManager->mail('contact_form', 'contact_form_sender', $to, $langcode, $params);
  }

}

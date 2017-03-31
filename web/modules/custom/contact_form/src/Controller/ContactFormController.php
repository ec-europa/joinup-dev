<?php

namespace Drupal\contact_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\joinup_notification\NotificationSenderService;

/**
 * Class ContactFormController.
 *
 * @package Drupal\contact_form\Controller
 */
class ContactFormController extends ControllerBase {

  /**
   * The notification sender.
   *
   * @var \Drupal\joinup_notification\NotificationSenderService
   */
  protected $notificationSender;

  /**
   * Constructs a ContactFormController object.
   *
   * @param \Drupal\joinup_notification\NotificationSenderService $notification_sender
   *   The notification sender.
   */
  public function __construct(NotificationSenderService $notification_sender) {
    $this->notificationSender = $notification_sender;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_notification.notification_sender')
    );
  }

  /**
   * Shows the contact form page.
   *
   * @return array
   *   The render array containing the contact form.
   */
  public function contactPage() {
    $contact_message = $this->entityTypeManager()->getStorage('message')->create([
      'template' => 'contact_form_submission',
    ]);

    $form = $this->entityFormBuilder()->getForm($contact_message);
    return $form;
  }

}

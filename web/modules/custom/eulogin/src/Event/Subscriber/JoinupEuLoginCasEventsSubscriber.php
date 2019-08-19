<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPostValidateEvent;
use Drupal\cas\Event\CasPreValidateEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to CAS events.
 */
class JoinupEuLoginCasEventsSubscriber implements EventSubscriberInterface {

  /**
   * The module's settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserDataInterface $user_data) {
    $this->settings = $config_factory->get('joinup_eulogin.settings');
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CasHelper::EVENT_PRE_VALIDATE => 'alterValidationUrl',
      CasHelper::EVENT_POST_LOGIN => 'storeAttributes',
      CasHelper::EVENT_POST_VALIDATE => 'prepareAttributes',
    ];
  }

  /**
   * Alters the validation path and query string.
   *
   * EU Login uses a different ticket validation path comparing to the standard
   * CAS 3.0 specifications. Also it adds additional query parameters to the
   * ticket validation URL.
   *
   * @param \Drupal\cas\Event\CasPreValidateEvent $event
   *   The CAS pre-validate event object.
   */
  public function alterValidationUrl(CasPreValidateEvent $event): void {
    $parameters = [
      'assuranceLevel' => $this->settings->get('ticket_validation.assurance_level'),
      'ticketTypes' => implode(',', $this->settings->get('ticket_validation.ticket_types')),
    ];
    if ($this->settings->get('ticket_validation.user_details')) {
      $parameters['userDetails'] = 'true';
    }

    $event->setValidationPath($this->settings->get('ticket_validation.path'));
    $event->addParameters($parameters);
  }

  /**
   * Stores the EU Login attributes in user data storage.
   *
   * @param \Drupal\cas\Event\CasPostLoginEvent $event
   *   The CAS post-login event object.
   */
  public function storeAttributes(CasPostLoginEvent $event): void {
    $this->userData->set('joinup_eulogin', $event->getAccount()->id(), 'attributes', $event->getCasPropertyBag()->getAttributes());
  }

  /**
   * Stores the EU Login attributes in the CAS property bag.
   *
   * @param \Drupal\cas\Event\CasPostValidateEvent $event
   *   The CAS post-validate event object.
   */
  public function prepareAttributes(CasPostValidateEvent $event): void {
    $xml = new \SimpleXMLElement($event->getResponseData());
    foreach ($xml->xpath('//cas:authenticationSuccess/*') as $element) {
      $event->getCasPropertyBag()->setAttribute($element->getName(), $element->__toString());
    };
  }

}

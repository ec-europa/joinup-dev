<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPostValidateEvent;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreValidateEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserDataInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to CAS events.
 */
class JoinupEuLoginCasEventsSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
      // This runs just before CasAttributesSubscriber::onPreLogin().
      // @see \Drupal\cas_attributes\Subscriber\CasAttributesSubscriber::onPreLogin()
      CasHelper::EVENT_PRE_LOGIN => ['handlePotentialMailCollision', 21],
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
   * Handles the case when a use changes its mail upstream with an existing one.
   *
   * When users are changing their EU Login account email, upstream, it might
   * happen that another user is registered with the same email address on
   * Joinup. This would lead to duplicate emails on Joinup which is unacceptable
   * with respect to data integrity. Note that Drupal allows email duplicates at
   * API level but not when using the UI. However, Joinup is enforcing email
   * uniqueness across users. As this edge case is very rare and unlikely, we
   * only throw an exception.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   *   The CAS pre-login event.
   *
   * @throws \Exception
   *   When a user changes its EU Login email to a value that is already taken
   *   by other Joinup user.
   */
  public function handlePotentialMailCollision(CasPreLoginEvent $event): void {
    $account = $event->getAccount();
    $eulogin_email = $event->getCasPropertyBag()->getAttribute('email');

    // A new email has been configured upstream, on the EU Login account. If the
    // account was linked manually, there is a chance that the email stored
    // locally does not share the same case sensitivity as the upstream account.
    // In these cases, allow to proceed so that the local account can retrieve
    // the correct case sensitivity email.
    if (strtolower($account->getEmail()) !== strtolower($eulogin_email)) {
      if (user_load_by_mail($eulogin_email)) {
        $event->cancelLogin($this->t("You've recently changed your EU Login account email but that email is already used in Joinup by another user. You cannot login until, either you change your EU Login email or you <a href=':url'>contact support</a> to fix the issue.", [
          ':url' => Url::fromRoute('contact_form.contact_page')->setAbsolute()->toString(),
        ]));
      }
    }
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

<?php

declare(strict_types = 1);

namespace Drupal\joinup_cas_mock_server\EventSubscriber;

use Drupal\cas_mock_server\Event\CasMockServerEvents;
use Drupal\cas_mock_server\Event\CasMockServerResponseAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to CasMockServerEvents::RESPONSE_ALTER event.
 */
class JoinupCasMockServerSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CasMockServerEvents::RESPONSE_ALTER => 'alterResponse',
    ];
  }

  /**
   * Alters the CAS mock server XML DOM to provide an EU Logon style response.
   *
   * The EU Login server CAS response doesn't store the attributes under the
   * <cas:attributes/> node. Instead the attributes are stored one level up,
   * populating the <cas:authenticationSuccess/> node.
   *
   * @param \Drupal\cas_mock_server\Event\CasMockServerResponseAlterEvent $event
   *   The event object.
   */
  public function alterResponse(CasMockServerResponseAlterEvent $event): void {
    $dom = $event->getDom();

    $authentication_success = $dom->getElementsByTagName('cas:authenticationSuccess')->item(0);

    // Remove <cas:attributes/>.
    $attributes = $dom->getElementsByTagName('cas:attributes')->item(0);
    $authentication_success->removeChild($attributes);

    // Add the attributes one level up.
    foreach ($event->getUserData() as $key => $value) {
      $attribute = $dom->createElement("cas:$key");
      $attribute->textContent = $value;
      $authentication_success->appendChild($attribute);
    }
  }

}

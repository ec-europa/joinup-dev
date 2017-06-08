<?php

namespace Drupal\joinup_migrate\EventSubscriber;

use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber that adds legacy paths as redirects.
 */
class CreateRedirectEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::POST_ROW_SAVE => 'addRedirect'];
  }

  /**
   * Reacts after a migration row is saved.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The event object.
   */
  public function addRedirect(MigratePostRowSaveEvent $event) {
    $source = $event->getMigration()->getSourcePlugin();
    if ($source instanceof RedirectImportInterface) {
      if ($redirect_source = $source->getRedirectSource($event->getRow())) {
        if (!empty($event->getDestinationIdValues()[0])) {
          $nid = $event->getDestinationIdValues()[0];
          Redirect::create([
            'type' => 'redirect',
            'uid' => 1,
            'redirect_source' => $redirect_source,
            'redirect_redirect' => [
              'uri' => "internal:/node/$nid",
              'title' => '',
            ],
            'status_code' => 301,
          ])->save();
        }
      }
    }
  }

}

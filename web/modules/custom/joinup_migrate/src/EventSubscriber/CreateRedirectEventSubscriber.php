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
   * A list of entity storage instances.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface[]
   */
  protected $storage = [];

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
    $migration = $event->getMigration();
    $source = $migration->getSourcePlugin();
    if (!($source instanceof RedirectImportInterface)) {
      return;
    }
    /** @var \Drupal\migrate\Plugin\migrate\destination\EntityContentBase $destination */
    $destination = $migration->getDestinationPlugin();
    if ($destination->getBaseId() !== 'entity') {
      return;
    }

    if ($redirect_sources = $source->getRedirectSources($event->getRow())) {
      if (!empty($event->getDestinationIdValues()[0])) {
        $entity_id = $event->getDestinationIdValues()[0];
        $entity_type_id = $destination->getDerivativeId();
        if (!isset($this->storage[$entity_type_id])) {
          $this->storage[$entity_type_id] = \Drupal::entityTypeManager()->getStorage($entity_type_id);
        }
        $entity = $this->storage[$entity_type_id]->load($entity_id);
        $uri = 'internal:/' . $entity->toUrl()->getInternalPath();

        foreach ($redirect_sources as $redirect_source) {
          // Create the redirect.
          Redirect::create([
            'type' => 'redirect',
            'uid' => 1,
            'redirect_source' => $redirect_source,
            'redirect_redirect' => [
              'uri' => $uri,
              'title' => '',
            ],
            'status_code' => 301,
          ])->save();
        }
      }
    }
  }

}

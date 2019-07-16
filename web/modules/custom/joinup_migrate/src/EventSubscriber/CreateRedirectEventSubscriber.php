<?php

namespace Drupal\joinup_migrate\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\redirect\Entity\Redirect;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber that adds legacy paths as redirects.
 */
class CreateRedirectEventSubscriber implements EventSubscriberInterface {

  /**
   * Static cache for entity storage instances.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface[]
   */
  protected $storage = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The redirect repository.
   *
   * @var \Drupal\redirect\RedirectRepository
   */
  protected $redirectRepository;

  /**
   * Constructs a CreateRedirectEventSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\redirect\RedirectRepository $redirectRepository
   *   The redirect repository.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RedirectRepository $redirectRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->redirectRepository = $redirectRepository;
  }

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
    $source_plugin = $migration->getSourcePlugin();
    if (!($source_plugin instanceof RedirectImportInterface)) {
      return;
    }

    /** @var \Drupal\migrate\Plugin\migrate\destination\EntityContentBase $destination */
    $destination = $migration->getDestinationPlugin();
    if ($destination->getBaseId() !== 'entity') {
      return;
    }

    if ($source_paths = $source_plugin->getRedirectSources($event->getRow())) {
      if (!empty($event->getDestinationIdValues()[0])) {
        $entity_id = $event->getDestinationIdValues()[0];
        $entity_type_id = $destination->getDerivativeId();
        if (!isset($this->storage[$entity_type_id])) {
          $this->storage[$entity_type_id] = $this->entityTypeManager->getStorage($entity_type_id);
        }

        if (!$entity = $this->storage[$entity_type_id]->load($entity_id)) {
          return;
        }
        $uri = $source_plugin->getRedirectUri($entity);

        foreach ($source_paths as $source_path) {
          if (!$this->redirectRepository->findMatchingRedirect($source_path, [])) {
            // Create the redirect.
            Redirect::create([
              'type' => 'redirect',
              'uid' => 1,
              'redirect_source' => ['path' => $source_path, 'query' => NULL],
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

}

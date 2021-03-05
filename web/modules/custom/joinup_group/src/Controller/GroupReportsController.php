<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Event\GroupReportsEvent;
use Drupal\joinup_group\Event\GroupReportsEventInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Returns responses for the group reports page.
 *
 * This is a page containing various reports about a group and is accessible for
 * facilitators and moderators. Modules can add reports for inclusion in this
 * page by subscribing to the GroupReportsEvent.
 *
 * This page can be reached through the three-dots menu on the group overview.
 */
class GroupReportsController extends ControllerBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructs a GroupReportsController object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('event_dispatcher'));
  }

  /**
   * Renders the group reports page.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $rdf_entity
   *   The group for which to build the reports page.
   *
   * @return array
   *   The page as a render array.
   */
  public function reports(GroupInterface $rdf_entity): array {
    $reports = $this->getReports($rdf_entity);

    if ($reports) {
      $content = [];

      foreach ($reports as $report) {
        $content[$report->id()] = [
          'title' => $report->getTitle(),
          'description' => $report->getDescription(),
          'url' => $report->getUrl(),
        ];
      }

      $build = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ];
    }
    else {
      $build = [
        '#markup' => $this->t('No reports are currently available.'),
      ];
    }

    return $build;
  }

  /**
   * Returns the available group reports.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group for which to return reports.
   *
   * @return \Drupal\joinup_group\Event\GroupReport[]
   *   An array of group reports, keyed by group report ID.
   */
  protected function getReports(GroupInterface $group): array {
    $event = new GroupReportsEvent($group);
    $this->dispatcher->dispatch(GroupReportsEventInterface::EVENT_NAME, $event);

    return $event->getGroupReports();
  }

  /**
   * Access check for the group reports page.
   *
   * Only facilitators and moderators have the required 'access group reports'
   * permission.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $rdf_entity
   *   The group for which the access to the reports page is being determined.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(GroupInterface $rdf_entity): AccessResultInterface {
    $user = $this->currentUser();

    // Check if the user has the global permission to access group reports of
    // all groups (e.g. moderators).
    if ($user->hasPermission('access group reports')) {
      return AccessResult::allowed();
    }

    // Check if the user has permission to access the group reports page of this
    // particular group (e.g. facilitators).
    return $rdf_entity->getGroupAccess('access group reports', $user);
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Event;

use Drupal\joinup_group\Entity\GroupInterface;

/**
 * Interface for events that collects reports to show on the group reports page.
 */
interface GroupReportsEventInterface {

  /**
   * The event name.
   */
  const EVENT_NAME = 'joinup_group.group_reports';

  /**
   * Adds a group report.
   *
   * @param \Drupal\joinup_group\Event\GroupReport $report
   *   The group report to add.
   *
   * @return \Drupal\joinup_group\Event\GroupReportsEvent
   *   The event for chaining.
   */
  public function addGroupReport(GroupReport $report): GroupReportsEvent;

  /**
   * Returns the group for which group reports are being collected.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface
   *   The group entity.
   */
  public function getGroup(): GroupInterface;

  /**
   * Retrieves the group reports that have been added by the subscribers.
   *
   * @return \Drupal\joinup_group\Event\GroupReport[]
   *   An array of group reports, keyed by report ID.
   */
  public function getGroupReports(): array;

}

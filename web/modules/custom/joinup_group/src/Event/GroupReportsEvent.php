<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Event;

use Drupal\joinup_group\Entity\GroupInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that collects reports to show on the group reports page.
 */
class GroupReportsEvent extends Event implements GroupReportsEventInterface {

  /**
   * The group for which the group reports are being collected.
   *
   * @var \Drupal\joinup_group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The group reports that have been added by event subscribers.
   *
   * @var \Drupal\joinup_group\Event\GroupReport[]
   */
  protected $groupReports;

  /**
   * Constructs a GroupReportsEvent.
   *
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The group for which the group reports are being collected.
   */
  public function __construct(GroupInterface $group) {
    $this->group = $group;
  }

  /**
   * {@inheritdoc}
   */
  public function addGroupReport(GroupReport $report): self {
    $this->groupReports[$report->id()] = $report;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): GroupInterface {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupReports(): array {
    return $this->groupReports;
  }

}

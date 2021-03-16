<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Event;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Simple data class to store information about group reports.
 *
 * @see \Drupal\joinup_group\Event\GroupReportsEventInterface
 */
class GroupReport {

  /**
   * The machine name of the group report.
   *
   * @var string
   */
  protected $id;

  /**
   * The title of the group report.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $title;

  /**
   * Optional description of the group report.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|null
   */
  protected $description;

  /**
   * URL leading to the group report page.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * Constructs a GroupReport object.
   *
   * @param string $id
   *   The machine name of the group report.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The title of the group report.
   * @param \Drupal\Core\Url $url
   *   The URL of the group report.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   Optional description of the group report.
   */
  public function __construct(string $id, TranslatableMarkup $title, Url $url, ?TranslatableMarkup $description) {
    $this->id = $id;
    $this->title = $title;
    $this->url = $url;
    $this->description = $description;
  }

  /**
   * Returns the machine name of the group report.
   *
   * @return string
   *   The machine name of the group report.
   */
  public function id(): string {
    return $this->id;
  }

  /**
   * Returns the title of the group report.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the group report.
   */
  public function getTitle(): TranslatableMarkup {
    return $this->title;
  }

  /**
   * Returns the optional description of the group report.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The optional description of the group report.
   */
  public function getDescription(): ?TranslatableMarkup {
    return $this->description;
  }

  /**
   * Returns the URL of the group report.
   *
   * @return \Drupal\Core\Url
   *   The URL of the group report.
   */
  public function getUrl(): Url {
    return $this->url;
  }

}

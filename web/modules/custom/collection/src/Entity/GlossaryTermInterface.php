<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\node\NodeInterface;

/**
 * Interface for glossary terms in Joinup.
 */
interface GlossaryTermInterface extends NodeInterface {

  /**
   * Returns whether or not the glossary term has an abbreviation.
   *
   * @return bool
   *   TRUE if the glossary term has an abbreviation, FALSE otherwise.
   */
  public function hasAbbreviation(): bool;

  /**
   * Returns the abbreviation of the glossary term.
   *
   * @return string|null
   *   The abbreviation, or NULL if the term doesn't have an abbreviation.
   */
  public function getAbbreviation(): ?string;

  /**
   * Returns the summary of the glossary term.
   *
   * If a summary has not been entered by the author, then a version of the main
   * definition text will be returned that is trimmed to 300 characters.
   *
   * @return string
   *   The summary.
   */
  public function getSummary(): string;

}

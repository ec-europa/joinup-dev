<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\node\NodeInterface;

/**
 * Interface for glossary terms in Joinup.
 */
interface GlossaryTermInterface extends NodeInterface, CollectionContentInterface {

  /**
   * Returns a list o synonyms of the glossary term.
   *
   * @return string[]
   *   A list of synonyms.
   */
  public function getSynonyms(): array;

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

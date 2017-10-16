<?php

namespace Drupal\custom_page;

use Drupal\node\NodeInterface;

/**
 * Provides an interface for the custom page OG menu links updater.
 */
interface CustomPageOgMenuLinksUpdaterInterface {

  /**
   * Adds a OG menu link pointing to a custom page.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The custom page entity.
   *
   * @return $this
   */
  public function addLink(NodeInterface $custom_page);

  /**
   * Deletes all the corresponding OG menu links pointing to a custom page.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The custom page entity.
   *
   * @return $this
   */
  public function deleteLinks(NodeInterface $custom_page);

}

<?php

namespace Drupal\custom_page;

use Drupal\node\NodeInterface;

/**
 * Provides an interface for the custom page OG menu links manager.
 */
interface CustomPageOgMenuLinksManagerInterface {

  /**
   * Returns a list of child custom pages linked through OG menu links.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The parent custom page node.
   *
   * @return \Drupal\node\NodeInterface[]
   *   A list of child nodes.
   */
  public function getChildren(NodeInterface $custom_page);

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
   * Move the OG menu links of a custom page to a new group OG menu instance.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The custom page entity.
   * @param string $group_id
   *   The ID of the group where the custom page is moved.
   *
   * @return $this
   */
  public function moveLinks(NodeInterface $custom_page, $group_id);

  /**
   * Deletes all the corresponding OG menu links pointing to a custom page.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The custom page entity.
   *
   * @return $this
   */
  public function deleteLinks(NodeInterface $custom_page);

  /**
   * Gets the OG menu instance, given a custom page.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The custom page entity.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *   The OG menu instance or NULL if none can be determined.
   */
  public function getOgMenuInstanceByCustomPage(NodeInterface $custom_page);

}

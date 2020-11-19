<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Event;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event class allowing subscribers to add group menu links.
 */
class GroupMenuLinksEvent extends Event {

  /**
   * Identifier for add group menu link event.
   *
   * @var string
   */
  public const ADD_LINKS = 'joinup_group.add_group_menu_links';

  /**
   * The group to provide menu links for.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $group;

  /**
   * The menu name.
   *
   * @var string
   */
  protected $menuName;

  /**
   * The menu link content entity storage.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentStorageInterface
   */
  protected $menuLinkContentStorage;

  /**
   * Constructs a new event instance.
   *
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group to provide menu links for.
   * @param string $menu_name
   *   The menu name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function __construct(RdfInterface $group, string $menu_name, EntityTypeManagerInterface $entity_type_manager) {
    $this->group = $group;
    $this->menuName = $menu_name;
    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
  }

  /**
   * Returns the group to provide menu links for.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The group to provide menu links for.
   */
  public function getGroup(): RdfInterface {
    return $this->group;
  }

  /**
   * Returns the menu name.
   *
   * @return string
   *   The menu name.
   */
  public function getMenuName(): string {
    return $this->menuName;
  }

  /**
   * Adds a new menu link content to the group OG menu instance.
   *
   * @param array $link
   *   The menu link as an array with the following keys:
   *   - uri: Link URI,
   *   - options: Link options.
   * @param \Drupal\Component\Render\MarkupInterface $label
   *   The menu translated label.
   * @param int $weight
   *   (optional) The menu weight. Defaults to 0.
   * @param array $values
   *   (optional) Additional values to be added to the menu link content entity
   *   creation.
   *
   * @return $this
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the menu link entity could not be saved.
   */
  public function addMenuLink(array $link, MarkupInterface $label, int $weight = 0, array $values = []): self {
    $this->menuLinkContentStorage->create([
      'title' => $label,
      'menu_name' => $this->getMenuName(),
      'link' => $link,
      'weight' => $weight,
    ] + $values)->save();
    return $this;
  }

}

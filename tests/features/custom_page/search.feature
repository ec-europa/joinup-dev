@api @group-d
Feature: Custom pages enhance search results for their collections/solutions
  In order to increase the chances of my group to be found
  As a facilitator
  I want search results for keywords that match content in my custom pages to include my group

  @clearStaticCache
  Scenario Outline: Collections and solutions are found when searching for keywords in custom pages
    Given the following <group>s:
      | title           | state     |
      | Buddy system    | draft     |
      | Slab allocation | validated |

    # Initially I get no results. This is intended to warm up any caches.
    When I enter "memory" in the search bar and press enter
    Then I should see "No content found for your search."

    Given custom_page content:
      | title                          | <group>         | body                         | status      |
      | Splitting and coalescing       | Buddy system    | Memory split in blocks       | published   |
      | Reduces memory fragmentation   | Buddy system    | Binary tree                  | unpublished |
      | Memory initialization overhead | Slab allocation | Significant performance drop | unpublished |
      | Alleviates fragmentation       | Slab allocation | Pre-allocated memory         | published   |

    And the following custom page menu structure:
      | title                          | enabled |
      | Splitting and coalescing       | yes     |
      | Reduces memory fragmentation   | no      |
      | Memory initialization overhead | yes     |
      | Alleviates fragmentation       | no      |

    # When I search for a keyword that is present in all 4 custom pages, I will
    # only get a single result back. Of the 4 custom pages, two are currently
    # published, but only "Alleviates fragmentation" is from a validated group,
    # so this should be the only result.
    When I enter "memory" in the search bar and press enter
    Then the page should show only the tiles "Alleviates fragmentation"

    # The "Alleviates fragmentation" page was not enabled in the navigation menu
    # of its parent group. When it becomes enabled, its content will be included
    # in the search index for the group, so the group will appear in the search.
    When I enable "Alleviates fragmentation" in the navigation menu of the "Slab allocation" <group>
    And I enter "memory" in the search bar and press enter
    Then the page should show only the tiles "Alleviates fragmentation, Slab allocation"

    # Publish the custom page which is currently unpublished. It should now
    # appear in the search results.
    When the publication state of the "Memory initialization overhead" custom page is changed to published
    When I enter "memory" in the search bar and press enter
    Then the page should show only the tiles "Alleviates fragmentation, Memory initialization overhead, Slab allocation"

    # If I search for a unique keyword in the newly published custom page I can
    # see that its contents are also used to enrich the group.
    When I enter "overhead" in the search bar and press enter
    Then the page should show only the tiles "Memory initialization overhead, Slab allocation"

    # Remove the custom page from the navigation menu. It should no longer be
    # used to enrich the group, but it should still show up on its own because
    # it is published content.
    When I disable "Memory initialization overhead" in the navigation menu of the "Slab allocation" <group>
    And I enter "overhead" in the search bar and press enter
    Then the page should show only the tiles "Memory initialization overhead"

    # Publish the group which is in draft. When searching I should now also get
    # this group and its enabled custom page back.
    When the workflow state of the "Buddy system" group is changed to "validated"
    And I enter "memory" in the search bar and press enter
    Then the page should show only the tiles "Alleviates fragmentation, Memory initialization overhead, Slab allocation, Splitting and coalescing, Buddy system"

    # An unpublished custom page doesn't show up in any results.
    When I enter "Binary tree" in the search bar and press enter
    Then I should see "No content found for your search."

    Examples:
      | group      |
      | collection |
      | solution   |


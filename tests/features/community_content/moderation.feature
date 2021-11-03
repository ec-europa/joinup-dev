@api @group-g
Feature: Moderate community content
  In order to accept or reject content that is proposed for publication or deletion
  As a facilitator
  I need to be able to see a list of content that requires moderation

  Scenario: Content moderation overview
    Given the following collection:
      | title            | Black hole research |
      | state            | validated           |
      | content creation | members             |
      | moderation       | yes                 |
    And the following solution:
      | title | Survey For Supernovae |
      | state | validated             |
    And users:
      | Username       | E-mail                   | First name | Family name |
      | Marco Farfarer | marco.farfar@example.com | Marco      | Farfarer    |

    # Before adding content, check that the 'empty message' is displayed on the
    # content moderation overview.
    When I am logged in as a facilitator of the "Black hole research" collection
    And I go to the homepage of the "Black hole research" collection
    # Contextual links should not be shown in the group header. All contextual
    # actions are instead handled through the "Entity actions". Since both are
    # themed similarly (as a "three dot menu") it is too confusing to have both
    # visible in the same area.
    Then I should not see any contextual links in the "Header" region
    And I click "Moderate content" in the "Entity actions" region
    Then I should see the heading "Content moderation"
    And I should see the text "Nothing to moderate. Enjoy your day!"

    # Add community content of all possible types in all possible states, to
    # both the collection and solution.
    Given discussion content:
      | title                         | body                 | collection          | solution              | state        | author         |
      | The information paradox       | Info Paradox         | Black hole research |                       | needs update | Marco Farfarer |
      | Black-body spectrum radiation | Hawking radiation    | Black hole research |                       | proposed     | Marco Farfarer |
      | The holographic principle     | String theory        | Black hole research |                       | validated    | Marco Farfarer |
      | Relation with host galaxies   | Supermassive         | Black hole research |                       | archived     | Marco Farfarer |
      | Tidal disruption events       | Spaghettification    |                     | Survey For Supernovae | needs update | Marco Farfarer |
      | Cataclysmic variables         | Irregular brightness |                     | Survey For Supernovae | proposed     | Marco Farfarer |
      | Stellar flares                | Dim red dwarfs       |                     | Survey For Supernovae | validated    | Marco Farfarer |
      | Upgrading CCD cameras         | Liquid cooled        |                     | Survey For Supernovae | archived     | Marco Farfarer |
    And document content:
      | title                         | body                          | collection          | solution              | state            |
      | A multiwavelength study       | Optical and infrared          | Black hole research |                       | draft            |
      | X-Ray Transient V616          | Weak ¹²CO absorption          | Black hole research |                       | needs update     |
      | K-band spectroscopy           | Small amount of flux          | Black hole research |                       | proposed         |
      | Spectral energy distribution  | Accretion disk emission       | Black hole research |                       | validated        |
      | Stellar atmosphere model      | Carbon abundance              | Black hole research |                       | deletion request |
      | A Massive Secondary Star      | Active CNO cycle              |                     | Survey For Supernovae | draft            |
      | Created By Supernova Ejecta   | The mass remains large        |                     | Survey For Supernovae | needs update     |
      | A spotted, nonspherical star  | Ellipsoidal variations        |                     | Survey For Supernovae | proposed         |
      | J-band light curves           | Wilson-Devinney modeling      |                     | Survey For Supernovae | validated        |
      | Physics of the Neupert effect | Observed temporal correlation |                     | Survey For Supernovae | deletion request |
    And event content:
      | title                                   | body                         | collection          | solution              | state            |
      | Thick-target collisional bremsstrahlung | Chromospheric evaporation    | Black hole research |                       | draft            |
      | Conductive cooling losses               | Single-loop geometry         | Black hole research |                       | needs update     |
      | Source of SXR plasma supply and heating | Fast electrons are not       | Black hole research |                       | proposed         |
      | Stars forming in material outflow       | Colossal material winds      | Black hole research |                       | validated        |
      | X-Shooter                               | VLT spectroscopic instrument | Black hole research |                       | deletion request |
      | Infant stellar population               | Quarter of star formation    |                     | Survey For Supernovae | draft            |
      | Give rise to galactic features          | Distended bulge of stars     |                     | Survey For Supernovae | needs update     |
      | Cosmic-infrared background radiation    | Coming from all directions   |                     | Survey For Supernovae | proposed         |
      | Evolution of dust-to-metals ratio       | Dust at high redshift        |                     | Survey For Supernovae | validated        |
      | Total V-band extinction                 | Fitting the afterglow SED    |                     | Survey For Supernovae | deletion request |
    And news content:
      | title                         | body                        | collection          | solution              | state            |
      | Magnetosphere boundary        | Gas shock                   | Black hole research |                       | draft            |
      | Ambient magnetized medium     | Dust depletion level        | Black hole research |                       | needs update     |
      | Stellar wind charged particle | Follow spiral paths         | Black hole research |                       | proposed         |
      | Super-Alfvenic plasma flow    | Magnetic draping            | Black hole research |                       | validated        |
      | Massive lensing galaxy        | Sub-millimeter sky          | Black hole research |                       | deletion request |
      | Planck's dusty gems           | An Einstein Ring            |                     | Survey For Supernovae | draft            |
      | The optical morphology        | Turbulent gas fragmentation |                     | Survey For Supernovae | needs update     |
      | The Tarantula massive binary  | SB2 orbital solution        |                     | Survey For Supernovae | proposed         |
      | H-rich Wolf-Rayet star        | Polarimetric analysis       |                     | Survey For Supernovae | validated        |
      | Quasi-homogeneous evolution   | Mass-transfer was avoided   |                     | Survey For Supernovae | deletion request |

    # Check if all content that requires moderation shows up for the collection.
    When I reload the page
    Then I should see the following headings:
      | Black-body spectrum radiation           |
      | K-band spectroscopy                     |
      | Stellar atmosphere model                |
      | Source of SXR plasma supply and heating |
      | X-Shooter                               |
      | Stellar wind charged particle           |
      | Massive lensing galaxy                  |

    And I should not see the following headings:
      | The information paradox                 |
      | The holographic principle               |
      | Relation with host galaxies             |
      | A multiwavelength study                 |
      | X-Ray Transient V616                    |
      | Spectral energy distribution            |
      | Thick-target collisional bremsstrahlung |
      | Conductive cooling losses               |
      | Stars forming in material outflow       |
      | Magnetosphere boundary                  |
      | Ambient magnetized medium               |
      | Super-Alfvenic plasma flow              |
      | Tidal disruption events                 |
      | Cataclysmic variables                   |
      | Stellar flares                          |
      | Upgrading CCD cameras                   |
      | A Massive Secondary Star                |
      | Created By Supernova Ejecta             |
      | A spotted, nonspherical star            |
      | J-band light curves                     |
      | Physics of the Neupert effect           |
      | Infant stellar population               |
      | Give rise to galactic features          |
      | Cosmic-infrared background radiation    |
      | Evolution of dust-to-metals ratio       |
      | Total V-band extinction                 |
      | Planck's dusty gems                     |
      | The optical morphology                  |
      | The Tarantula massive binary            |
      | H-rich Wolf-Rayet star                  |
      | Quasi-homogeneous evolution             |

    And I should see the following lines of text:
      | Hawking radiation            |
      | Small amount of flux         |
      | Carbon abundance             |
      | Fast electrons are not       |
      | VLT spectroscopic instrument |
      | Follow spiral paths          |
      | Sub-millimeter sky           |

    And I should not see the following lines of text:
      | Info Paradox                  |
      | String theory                 |
      | Supermassive                  |
      | Optical and infrared          |
      | Weak ¹²CO absorption          |
      | Accretion disk emission       |
      | Chromospheric evaporation     |
      | Single-loop geometry          |
      | Colossal material winds       |
      | Gas shock                     |
      | Dust depletion level          |
      | Magnetic draping              |
      | Spaghettification             |
      | Irregular brightness          |
      | Dim red dwarfs                |
      | Liquid cooled                 |
      | Active CNO cycle              |
      | The mass remains large        |
      | Ellipsoidal variations        |
      | Wilson-Devinney modeling      |
      | Observed temporal correlation |
      | Quarter of star formation     |
      | Distended bulge of stars      |
      | Coming from all directions    |
      | Dust at high redshift         |
      | Fitting the afterglow SED     |
      | An Einstein Ring              |
      | Turbulent gas fragmentation   |
      | SB2 orbital solution          |
      | Polarimetric analysis         |
      | Mass-transfer was avoided     |

    # Check that the moderation state is shown.
    And the moderation preview of "Black-body spectrum radiation" should contain the text "Proposed"

    # Check that the links work.
    When I click the "View" link in the "Black-body spectrum radiation" moderation preview
    Then I should see the heading "Black-body spectrum radiation"

    When I move backward one page
    And I click the "Edit" link in the "Black-body spectrum radiation" moderation preview
    Then I should see the heading "Edit Discussion Black-body spectrum radiation"

    # Approve the content, and check that it no longer shows up in the moderation overview.
    When I press "Publish"
    Then I should see the success message "Discussion Black-body spectrum radiation has been updated."
    When I go to the homepage of the "Black hole research" collection
    And I click "Moderate content" in the "Entity actions" region
    Then I should see the heading "Content moderation"
    And I should not see the text "Black-body spectrum radiation"

    # Now repeat this for the solution.
    When I am logged in as a facilitator of the "Survey For Supernovae" solution
    And I go to the homepage of the "Survey For Supernovae" solution
    And I click "Moderate content" in the "Entity actions" region
    Then I should see the heading "Content moderation"
    Then I should see the following headings:
      | Cataclysmic variables                |
      | A spotted, nonspherical star         |
      | Physics of the Neupert effect        |
      | Cosmic-infrared background radiation |
      | Total V-band extinction              |
      | The Tarantula massive binary         |
      | Quasi-homogeneous evolution          |

    And I should not see the following headings:
      | The information paradox                 |
      | Black-body spectrum radiation           |
      | The holographic principle               |
      | Relation with host galaxies             |
      | A multiwavelength study                 |
      | X-Ray Transient V616                    |
      | K-band spectroscopy                     |
      | Spectral energy distribution            |
      | Stellar atmosphere model                |
      | Thick-target collisional bremsstrahlung |
      | Conductive cooling losses               |
      | Source of SXR plasma supply and heating |
      | Stars forming in material outflow       |
      | X-Shooter                               |
      | Magnetosphere boundary                  |
      | Ambient magnetized medium               |
      | Stellar wind charged particle           |
      | Super-Alfvenic plasma flow              |
      | Massive lensing galaxy                  |
      | Tidal disruption events                 |
      | Stellar flares                          |
      | Upgrading CCD cameras                   |
      | A Massive Secondary Star                |
      | Created By Supernova Ejecta             |
      | J-band light curves                     |
      | Infant stellar population               |
      | Give rise to galactic features          |
      | Evolution of dust-to-metals ratio       |
      | Planck's dusty gems                     |
      | The optical morphology                  |
      | H-rich Wolf-Rayet star                  |

    And I should see the following lines of text:
      | Irregular brightness          |
      | Ellipsoidal variations        |
      | Observed temporal correlation |
      | Coming from all directions    |
      | Fitting the afterglow SED     |
      | SB2 orbital solution          |
      | Mass-transfer was avoided     |

    And I should not see the following lines of text:
      | Info Paradox                 |
      | Hawking radiation            |
      | String theory                |
      | Supermassive                 |
      | Optical and infrared         |
      | Weak ¹²CO absorption         |
      | Small amount of flux         |
      | Accretion disk emission      |
      | Carbon abundance             |
      | Chromospheric evaporation    |
      | Single-loop geometry         |
      | Fast electrons are not       |
      | Colossal material winds      |
      | VLT spectroscopic instrument |
      | Gas shock                    |
      | Dust depletion level         |
      | Follow spiral paths          |
      | Magnetic draping             |
      | Sub-millimeter sky           |
      | Spaghettification            |
      | Dim red dwarfs               |
      | Liquid cooled                |
      | Active CNO cycle             |
      | The mass remains large       |
      | Wilson-Devinney modeling     |
      | Quarter of star formation    |
      | Distended bulge of stars     |
      | Dust at high redshift        |
      | An Einstein Ring             |
      | Turbulent gas fragmentation  |
      | Polarimetric analysis        |

    And the moderation preview of "Cataclysmic variables" should contain the text "Proposed"

    # Check that the links work.
    When I click the "View" link in the "Cataclysmic variables" moderation preview
    Then I should see the heading "Cataclysmic variables"

    When I move backward one page
    And I click the "Edit" link in the "Cataclysmic variables" moderation preview
    Then I should see the heading "Edit Discussion Cataclysmic variables"

    # Approve the content, and check that it no longer shows up in the moderation overview.
    When I press "Publish"
    Then I should see the success message "Discussion Cataclysmic variables has been updated."
    When I go to the homepage of the "Survey For Supernovae" solution
    And I click "Moderate content" in the "Entity actions" region
    Then I should see the heading "Content moderation"
    And I should not see the text "Cataclysmic variables"

    # Verify that when an entity receives a version to moderate while having a published version,
    # the latest version is shown in the moderation page.
    When I go to the "Cataclysmic variables" discussion
    And I click "Edit" in the "Entity actions" region
    And I fill in "Title" with "Cataclysmic conditions"
    And I fill in "Motivation" with "This is a regression issue."
    And I press "Request changes"
    And I go to the homepage of the "Survey For Supernovae" solution
    And I click "Moderate content" in the "Entity actions" region
    Then I should see the heading "Content moderation"
    And I should see the text "Cataclysmic conditions"
    And I should not see the text "Cataclysmic variables"

  @javascript
  Scenario: Filtering the content moderation overview

    Given the following collection:
      | title | Neutron stars |
      | state | validated     |

    And discussion content:
      | title                       | body                  | collection    | state    |
      | Rotation-powered pulsations | Millisecond pulsars   | Neutron stars | proposed |
      | The Recycling concept       | An epoch of accretion | Neutron stars | proposed |
    And document content:
      | title          | body                          | collection    | state            |
      | Donor star     | Spun up to millisecond period | Neutron stars | proposed         |
      | High frequency | Slow 1.2 second spin          | Neutron stars | deletion request |
      | Cluster        | Eddington luminosity          | Neutron stars | deletion request |
    And event content:
      | title      | body        | collection    | state    |
      | Accelerate | Wide binary | Neutron stars | proposed |
    And news content:
      | title                   | body                             | collection    | state            |
      | Metal-rich star cluster | Standard pulsar recycling theory | Neutron stars | deletion request |

    When I am logged in as a facilitator of the "Neutron stars" collection
    And I go to the homepage of the "Neutron stars" collection
    And I open the header local tasks menu
    And I click "Moderate content" in the "Entity actions" region
    Then I should see the heading "Content moderation"
    And the available options in the "Content of type" select should be "All (7), Discussion (2), Document (3), Event (1), News (1)"
    And the available options in the "in state" select should be "All (7), Deletion request (3), Proposed (4)"
    And I should see the following headings:
      | Rotation-powered pulsations |
      | The Recycling concept       |
      | Donor star                  |
      | High frequency              |
      | Cluster                     |
      | Accelerate                  |
      | Metal-rich star cluster     |

    When I select "Document (3)" from "Content of type"
    And I wait for AJAX to finish
    Then the available options in the "Content of type" select should be "All (7), Discussion (2), Document (3), Event (1), News (1)"
    And the option "Document (3)" should be selected
    And the available options in the "in state" select should be "All (3), Deletion request (2), Proposed (1)"
    And the option "All (3)" should be selected
    And I should see the following headings:
      | Donor star     |
      | High frequency |
      | Cluster        |
    And I should not see the following headings:
      | Rotation-powered pulsations |
      | The Recycling concept       |
      | Accelerate                  |
      | Metal-rich star cluster     |

    When I select "Proposed (1)" from "in state"
    And I wait for AJAX to finish
    Then the available options in the "Content of type" select should be "All (7), Discussion (2), Document (3), Event (1), News (1)"
    And the option "Document (3)" should be selected
    And the available options in the "in state" select should be "All (3), Deletion request (2), Proposed (1)"
    And the option "Proposed (1)" should be selected
    And I should see the following headings:
      | Donor star |
    And I should not see the following headings:
      | Rotation-powered pulsations |
      | The Recycling concept       |
      | Accelerate                  |
      | Metal-rich star cluster     |
      | High frequency              |
      | Cluster                     |

  @terms @uploadFiles:logo.png
  Scenario: The logo image can be replaced for an event or news item that's in
    the "proposed" state. See ISAICP-5818.

    Given user:
      | Username | leo |
    And the following collection:
      | title      | Black hole research |
      | state      | validated           |
      | moderation | yes                 |
    And the following collection user membership:
      | collection          | user |
      | Black hole research | leo  |

    Given I am logged in as "leo"

    # Test event.
    And I go to the homepage of the "Black hole research" collection
    When I click "Add event" in the plus button menu
    And I fill in the following:
      | Title            | Alice in Wonderland |
      | Description      | Here we go...       |
      | Virtual location | http://example.com  |

    And I select "Statistics and Analysis" from "Topic"
    And I attach the file "logo.png" to "Logo"
    And I press "Upload"
    When I press "Propose"
    Then I should see the success message "Event Alice in Wonderland has been created."

    But I click "Edit"
    And I should see the heading "Edit Event Alice in Wonderland"
    When I press "Remove"
    And I should see the heading "Edit Event Alice in Wonderland"
    And I should see the button "Upload"
    # Test news.
    And I go to the homepage of the "Black hole research" collection
    When I click "Add news" in the plus button menu
    And I fill in the following:
      | Short title | Declared the ultimate metal                                                                   |
      | Headline    | Strong request for this rare metal that is on the mouth of everybody                          |
      | Content     | Thanks to its lower density compared to thulium and lutetium its applications have increased. |

    And I select "Data gathering, data processing" from "Topic"
    And I attach the file "logo.png" to "Logo"
    And I press "Upload"
    When I press "Propose"
    Then I should see the success message "News Declared the ultimate metal has been created."

    But I click "Edit"
    And I should see the heading "Edit News Declared the ultimate metal"
    When I press "Remove"
    And I should see the heading "Edit News Declared the ultimate metal"
    And I should see the button "Upload"

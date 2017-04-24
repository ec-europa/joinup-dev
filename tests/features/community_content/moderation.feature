@api
Feature: Moderate community content
  In order to accept or reject content that is proposed for publication or deletion
  As a facilitator
  I need to be able to see a list of content that requires moderation

  Scenario: Content moderation overview
    Given the following collection:
      | title | Black hole research |
      | state | validated           |
    And the following solution:
      | title | Survey For Supernovae |
      | state | validated             |
    And discussion content:
      | title                         | body                 | collection          | solution              | state         |
      | The information paradox       | Info Paradox         | Black hole research |                       | needs update  |
      | Black-body spectrum radiation | Hawking radiation    | Black hole research |                       | proposed      |
      | The holographic principle     | String theory        | Black hole research |                       | validated     |
      | Relation with host galaxies   | Supermassive         | Black hole research |                       | archived      |
      | Tidal disruption events       | Spaghettification    |                     | Survey For Supernovae | needs update  |
      | Cataclysmic variables         | Irregular brightness |                     | Survey For Supernovae | proposed      |
      | Stellar flares                | Dim red dwarfs       |                     | Survey For Supernovae | validated     |
      | Upgrading CCD cameras         | Liquid cooled        |                     | Survey For Supernovae | archived      |
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

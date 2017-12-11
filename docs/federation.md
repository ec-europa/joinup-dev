#  Joinup federation of Linked Data
## Background
TODO
## Stakeholders
- Member state portals
- Joinup community
- Joinup platform
- Other ISA projects

## Architecture
* Architecture principles
    * EIF recommendations
    * General
        * __Common Use Solutions__
        Cross-silo solutions are preferred over duplicative silo specific applications, systems and tools.
    * Data
        * __Data Interpretation__
          Data definitions and vocabularies will be consistent throughout the organization.
        * __Data Corrections__
         Data corrections will occur in the system furthest upstream and be cascaded down.
        * __Data Quality__
          Data quality targets will be published for all master data repositories.
        * __Decoupled Data__
         Data should be maintained in a separate data layer decoupled from applications.
        * __Globally Unique Identifier__
         Business critical data objects will have a globally unique identifier.
        * __Master Data__
        All data will have a golden copy.
        ( Provenance management )
        * __Data Validation__
        Data will be validated at the point of collection.
    * Processes
        * __Zero Touch__
          Manual tasks should be managed as a workflow.
### Business architecture

* _Member state data portal_
Tight budgets within public administrations force administrators to look into lower the operational costs by collaborating around particular problems and by attracting co-maintainers.
Sharing SBBs as broad as possible will in the long run lower costs.

* _Joinup portal_
Globalization urges for more agile Europe. In order to stay relevant, the EC has a responsibility as it is in the coordinating role to facilitate the exchange of information between European public services.
By sharing SBBs developed both in the commission and in member states as broad as possible, Joinup helps building the digital single market.

* _Joinup community_

* _Other ISA Projects_

### Information system architecture
#### Data architecture
The data architecture is centered around ADMS-AP.
The Joinup portal implements the model in a 'conformist' way, so that friction between Joinup and the data standard is minimized.
This was one of the main changes in the architecture while migrating from D6 to D8.

_Joinup extensions (optional):_
Internally Joinup adds properties that are not included in the ADMS-AP data model. For example, the banner image that is shown inside collections.
These properties should be imported when present, but not managed by the federation process.

_Backwards compatibility:_
At the moment, none of the member state data portals support ADMS-APv2, while some support ADMS-APv1.
As not everyone will upgrade straight away, an intermediate upgrade path component will take care of supporting legacy systems.
ADMS-APv1  ----> Transformation ----> Joinup (ADMS-APv2)
It is of uttermost importance that a good relation is established with the repository owners, as they stay responsible for their data.


#### Application architecture
* The federation process will be implemented as a workflow. Only one federation workflow can be active at any given time.
* Automated harvesting
    1. Manual harvesting, put everything in same format in a folder per repository.
    2. Use a harvester script to automatize the downloading
* Import triples into the SINK graph
    * With provenance recording

* _ADMS-APv1 transformation_
    * Run a 'upgrade' process for all data federated from an ADMS-APv1 repository.
* ADMS-APv2 compliance checks
    1. Use SPARQL queries to validate
    2. Use TestBed to validate
    3. Report back any errors to the repository owner
    4. Abort for all non-compliant repositories
* 3-way merge
    For __each entity__ defined in the SINK graph:
    1. If an entity exists in the draft graph, or the published graph, copy it to the STAGING graph
      (If no existing entity, it might be needed to apply some defaults)
    2. Remove any unmapped properties
    3. Remove all properties defined by ADMS-APv2
        (This leaves all Joinup specific properties)
    4. Remove all triples from the STAGING graph whose predicates are present in the SINK graph
    5. Copy all triples from the SINK graph to the STAGING graph

* Joinup compatibility checks
    For __each entity__ defined in the STAGING graph:
    1. Load the object
    2. Call the ->validate() method
    3. Collect a report of constraint violations
    4. Report back any errors to the repository owner
    5. Abort for all non-compliant repositories
* Moderation
    For __each entity__ defined in the STAGING graph:
    1. Provide a view that lists all entities needing moderation
    2. Allow 'diffing'
    3. When accepted, copy entity from STAGING graph to PUBLISHED graph
       ( This can lead to data in draft being published )
    4. Foresee a mechanism to provide feedback on the solutions that don't match the eligibility criteria to the repository owners.

* Content locking
    1. Content that is managed by federation should be locked from manual editing. All content issues should be addressed through communication with the repository owner.
### Technology architecture
Not applicable
## Governance
To be defined

### Tasks (To plan)
* Validate architecture conformance to the architecture principles and the EIF.

* Document eligibility criteria and technical specifications for federation.

* System development planning: User stories

* Communications plan
    * Inform / consult stakeholders
        * ADMS-AP v2 alignment


Joinup Stats
============

This module allows entities to store usage statistics in a related meta entity.
Since statistics data changes often it is preferable to store this in a meta
entity. This is more lightweight (i.e. will be quicker to write to the database)
and it prevents the update timestamp / revisions of the original entity from
being affected by the writing of the statistics.

The statistics are sourced from an external Matomo data store and are synced
back regularly to the local database so that this information can be shown to
the users. This is using a cached computed field which is updated using a cron
job. This allows us to retrieve the data in batches. Doing individual queries
directly to the Matomo instance on page load would cause too much load on the
Matomo server. Ref. `RefreshCachedFieldsEventSubscriber`.


Currently implemented use cases
-------------------------------

### Download counts

Allows to track the number of times an asset has been downloaded. Implemented
for asset distributions.

- Entity interface: `DownloadCountAwareInterface`.
- Meta entity type: `download_count`.

### Visit counts

Allows to track the number of times a page has been visited. Implemented for
community content.

- Entity interface: `VisitCountAwareInterface`.
- Meta entity type: `visit_count`.


Usage
-----

- Implement one of the provided `StatisticsAwareInterface` sub-interfaces for
  the bundle that needs to store statistics.
- Add the entity bundle that needs to store statistics to the list of targets
  in the configuration of the meta entity.

  For example, the `meta_entity.type.visit_count` config contains the following
  to indicate that community content is being tracked and that this data is
  exposed on the `visit_count` computed field:
  ```
  mapping:
    node:
      discussion: visit_count
      document: visit_count
      event: visit_count
      news: visit_count
  ```

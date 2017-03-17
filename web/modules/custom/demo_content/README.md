# Demo content module
This module provides demo entities to the joinup project.
**To be removed after release 1**

## Export entities
The module uses the [default_content](http://drupal.org/project/default_content)
module to export/import the entities.
To export an entity after creation, run the following:
`drush dce <entity_type> <id>`.
The json export must be placed in the
`web/modules/custom/demo_content/content/<entity_type>/<bundle>` folder.

## Import entities with images
Default content module needs the 'File entity' module in order to export
images but is not used in demo_content.

In order to include images to the entity, follow the steps below:
* Provide an image to the `web/modules/custom/demo_content/fixtures/files`
folder.
* Associate the image to the entity in the demo_content.settings.yml file
of the demo_content module located in
`web/modules/custom/demo_content/config/install` directory. The structure
of the entry is
```
<entity_id>:
  <field 1 machine_name>: <file 1 name>
  <field 2 machine_name>: <file 2 name>
```
This array must be placed in the `field_mappings` entry.

# Rdf federated fields

This simple module provides a service that validates that a field
belongs to an ontology.

This can be used for entities that have mapped rdf properties 
using the `rdf_entity_mapping` entity type.

It provides some third party settings to that entity type that stores
a graph and a property predicates array. The predicates array are
predicates that define that a property exists within the domain of a
class.

The module needs that the ontology definition is stored in a graph
and this graph is stored in the third party settings of the
corresponding `rdf_entity_mapping` entity of the entity type in question.
The rdf property that defines relationships for example, is `<http://www.w3.org/2000/01/rdf-schema#domain>`.

## Usage
```php
$entity_type_id = 'entity_test';
$bundle = 'bunlde';
$field_name = 'field_text';
$field_column = 'value';
 
$validator = \Drupal::service('rdf_ff.schema_field_validator');
$is_defined = $validator->isDefinedInSchema($entity_type_id, $bundle, $field_name, $field_column);
```

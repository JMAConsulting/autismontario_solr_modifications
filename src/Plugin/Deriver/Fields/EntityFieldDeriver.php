<?php

namespace Drupal\autismontario_solr_modifications\Plugin\Deriver\Fields;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\graphql\Utility\StringHelper;
use Drupal\graphql_core\Plugin\Deriver\EntityFieldDeriverBase;

class EntityFieldDeriver extends EntityFieldDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function getDerivativeDefinitionsFromFieldDefinition(FieldDefinitionInterface $fieldDefinition, array $basePluginDefinition) {
    $itemDefinition = $fieldDefinition->getItemDefinition();
    if (!($itemDefinition instanceof ComplexDataDefinitionInterface) || !$propertyDefinitions = $itemDefinition->getPropertyDefinitions()) {
      return [];
    }

    $tags = array_merge($fieldDefinition->getCacheTags(), ['entity_field_info']);
    $maxAge = $fieldDefinition->getCacheMaxAge();
    $contexts = $fieldDefinition->getCacheContexts();

    $entityTypeId = $fieldDefinition->getTargetEntityTypeId();
    $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
    $supportsBundles = $entityType->hasKey('bundle');
    $fieldName = $fieldDefinition->getName();
    $fieldBundle = $fieldDefinition->getTargetBundle() ?: '';

    $derivative = [
      'parents' => [StringHelper::camelCase($entityTypeId, $supportsBundles ? $fieldBundle : '')],
      'name' => StringHelper::propCase($fieldName . 'Jma'),
      'description' => $fieldDefinition->getDescription(),
      'field' => $fieldName,
      'schema_cache_tags' => $tags,
      'schema_cache_contexts' => $contexts,
      'schema_cache_max_age' => $maxAge,
    ] + $basePluginDefinition;

    if (count($propertyDefinitions) === 1) {
      $propertyDefinition = reset($propertyDefinitions);
      $derivative['type'] = $propertyDefinition->getDataType();
      $derivative['property'] = key($propertyDefinitions);
    }
    else {
      $derivative['type'] = StringHelper::camelCase('field', $entityTypeId, $supportsBundles ? $fieldBundle : '', $fieldName);
    }

    // Fields are usually multi-value. Simplify them for the schema if they are
    // configured for cardinality 1 (only works for configured fields).
    if (!(($storageDefinition = $fieldDefinition->getFieldStorageDefinition()) && !$storageDefinition->isMultiple()) || in_array($fieldName, ['custom_898', 'custom_897', 'custom_899'])) {
      $derivative['type'] = StringHelper::listType($derivative['type']);
    }
    if (in_array($fieldName, ['custom_898', 'custom_897', 'custom_899'])) {
      switch ($fieldName) {
        case 'custom_897':
          $derivative['optionGroupId'] = 232;
          break;

        case 'custom_898':
          $derivative['optionGroupId'] = 233;
          break;

        case 'custom_899':
          $derivative['optionGroupId'] = 105;
          break;

      }
    }
    return ["$entityTypeId-$fieldName-$fieldBundle-jma" => $derivative];
  }
}

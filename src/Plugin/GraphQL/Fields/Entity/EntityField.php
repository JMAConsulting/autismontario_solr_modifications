<?php

namespace Drupal\autismontario_solr_modifications\Plugin\GraphQL\Fields\Entity;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\graphql\GraphQL\Cache\CacheableValue;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql_core\Plugin\GraphQL\Fields\EntityFieldBase;
use GraphQL\Type\Definition\ResolveInfo;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\WrappingType;

/**
 * @GraphQLField(
 *   id = "entity_field_jma",
 *   secure = true,
 *   weight = -2,
 *   deriver = "Drupal\autismontario_solr_modifications\Plugin\Deriver\Fields\EntityFieldDeriver",
 * )
 */
class EntityField extends EntityFieldBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof FieldableEntityInterface) {
      $definition = $this->getPluginDefinition();
      $name = $definition['field'];

      if ($value->hasField($name)) {
        /** @var \Drupal\Core\Field\FieldItemListInterface $items */
        $items = $value->get($name);
        $access = $items->access('view', NULL, TRUE);

        if ($access->isAllowed()) {
          foreach ($items as $item) {
            $output = !empty($definition['property']) ? $this->resolveItem($item, $args, $context, $info) : $item;

            yield new CacheableValue($output, [$access]);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveItem($item, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($item instanceof FieldItemInterface) {
      $definition = $this->getPluginDefinition();
      $property = $definition['property'];
      $result = $item->get($property)->getValue();
      if (!empty($definition['optionGroupId'])) {
        if (!$result instanceof MarkupInterface) {
          $result = \civicrm_api3('OptionValue', 'getsingle', ['option_group_id' => $definition['optionGroupId'], 'value' => $result])['label'];
        }
      }
      $result = $result instanceof MarkupInterface ? $result->__toString() : $result;

      $type = $info->returnType;
      $type = $type instanceof WrappingType ? $type->getWrappedType(TRUE) : $type;
      if ($type instanceof ScalarType) {
        $result = is_null($result) ? NULL : $type->serialize($result);
      }

      if ($result instanceof ContentEntityInterface && $result->isTranslatable() && $language = $context->getContext('language', $info)) {
        if ($result->hasTranslation($language)) {
          $result = $result->getTranslation($language);
        }
      }

      return $result;
    }
  }

}

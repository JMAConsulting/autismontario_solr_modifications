<?php

namespace Drupal\autism_solr_mods\Entity\Query\CiviCRM;

use Drupal\civicrm_entity\CiviCrmApi;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * The CiviCRM entity query class.
 * The only reason the three files in this folder exist, is to only index
 * Civicrm Contacts that have the subtype: Providers.
 * The actual change is in the Query.php file.
 */
class Query extends QueryBase implements QueryInterface {

  protected $civicrmApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, array $namespaces, CiviCrmApi $civicrm_api) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->civicrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $params = [];
    foreach ($this->condition->conditions() as $condition) {
      // If there's anything requiring a custom field, set condition which cannot
      // be completed.
      // @todo Introduced when supporting field config. Find something better.
      // @see \Drupal\field_ui\Form\FieldStorageConfigEditForm::validateCardinality()
      if (substr($condition['field'], 0, 6) === 'field_') {
        $params['id'] = '-1';
        break;
      }
      $operator = $condition['operator'] ?: '=';
      if ($operator == 'CONTAINS') {
        $params[$condition['field']] = ['LIKE' => '%' . $condition['value'] . '%'];
      }
      elseif ($operator != '=') {
        $params[$condition['field']] = [$operator => $condition['value']];
      }
      else {
        $params[$condition['field']] = $condition['value'];
      }
    }


    // This chunk is the only reason the files in this directory exist.
    if ($this->entityTypeId == 'civicrm_contact') {
      $params['contact_sub_type'] = ['LIKE' => '%' . "Service" . '%'];
      //$params['group'] = [
      //  'IN' => [
      //    '0' => 'Providers'
      //  ]
      //];
      $params[SERVICE_PROVIDER_STATUS] = ['=' => 'Approved'];
    }
    if ($this->entityTypeId == 'civicrm_event') {
      $params['is_template'] = ['=' => 0];
      $params['is_active'] = ['=' => 1];
      $params['is_public'] = ['=' => 1];
    }

    // The rest is just extending/overriding the Civicrm Entity classes/functions.

    $this->initializePager();
    if ($this->range) {
      $params['options'] = [
        'limit' => $this->range['length'],
        'offset' => $this->range['start'],
      ];
    }

    if ($this->count) {
      return $this->civicrmApi->getCount($this->entityType->get('civicrm_entity'), $params);
    }
    else {
      $result = $this->civicrmApi->get($this->entityType->get('civicrm_entity'), $params);
      return array_keys($result);
    }
  }

}

<?php

namespace Drupal\autism_solr_mods\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;


/**
 * Excludes most Civicrm Contacts.
 *
 * @SearchApiProcessor(
 *   id = "search_api_contact_group_processor",
 *   label = @Translation("CiviCRM Contact Group Processor"),
 *   description = @Translation("Exclude all but one CiviCRM Contact group from
 *   being indexed."),
 *   stages = {
 *     "alter_items" = 1,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SearchApiContactGroupProcessor extends ProcessorPluginBase {


  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    // This function prevents indexing Civi Contacts that are not in the Providers group.

    // We want to build a list of contact ids, derived from the current batch.
    $contact_id_list = [];

    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $solr_item_id => $item) {
      // First lets loop through the batch of items.

      // We only want to act upon civicrm_contacts.
      if (strpos($solr_item_id, 'civicrm_contact') != FALSE) {
        // The $item_id takes the format: entity:civicrm_contact/2222:und .
        // So we str_replace and explode the string to get 2222.
        $contact_id = str_replace('entity:civicrm_contact/', '', $solr_item_id);
        $contact_id = explode(":", $contact_id);
        $contact_id = $contact_id[0];
        // Add to the list.
        $contact_id_list[$contact_id] = $solr_item_id;
      }
    }

    if (count($contact_id_list) > 0) {


      // Load Civi service so we can use its query lib.
      $civicrm = \Drupal::service('civicrm');
      $civicrm->initialize();
      $tableName = civicrm_api3('CustomGroup', 'getsingle', ['id' => 69])['table_name'];
      $column = civicrm_api3('CustomField', 'getsingle', ['id' => 872])['column_name'];
      $query = 'SELECT c.id FROM civicrm_contact c INNER JOIN ' . $tableName . ' s ON s.entity_id = c.id WHERE c.contact_sub_type LIKE "%Service%" AND s.' . $column . ' = "Current Listing" AND c.id IN (';
      // Building the list of contact_ids for the "IN" clause of the query.
      foreach ($contact_id_list as $contact_id => $solr_item_id) {
        $query .= $contact_id . ",";
      }
      // This will result in the query being like: c.id IN (1,2,3,4,
      // Strip the last "," off.
      $query = rtrim($query, ',');
      // Close it uuup.
      $query .= ');';

      $provider_contacts = \CRM_Core_DAO::executeQuery($query);

      // Contact_id_list has all the contact IDs.
      // We'll remove all the Provider contacts from this list.
      // Then we'll have a list of Contacts we don't want to index.
      while ($provider_contacts->fetch()) {
        unset($contact_id_list[$provider_contacts->id]);
      }

      // Now $contact_id_list only has contacts to remove.
      foreach ($contact_id_list as $contact_id => $solr_item_id) {
        unset($items[$solr_item_id]);
      }
    }
  }
}

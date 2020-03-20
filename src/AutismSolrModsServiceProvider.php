<?php
/**
 * @file
 * Contains Drupal\autism_solr_mods\AutismContextServiceProvider
 */

namespace Drupal\autism_solr_mods;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the CiviCRM Query service.
 */
class AutismSolrModsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // menu active trail class
    $definition = $container->getDefinition('entity.query.civicrm_entity');
    $definition->setClass('Drupal\autism_solr_mods\Entity\Query\CiviCRM\QueryFactory');
  }
}
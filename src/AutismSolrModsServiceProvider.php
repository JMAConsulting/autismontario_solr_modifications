<?php
/**
 * @file
 * Contains Drupal\autismontario_solr_modifications\AutismContextServiceProvider
 */

namespace Drupal\autismontario_solr_modifications;

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
    $definition->setClass('Drupal\autismontario_solr_modifications\Entity\Query\CiviCRM\QueryFactory');
  }
}

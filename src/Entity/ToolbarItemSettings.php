<?php

namespace Drupal\toolbar_manager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Content Type settings entity.
 *
 * @ConfigEntityType(
 *   id = "toolbar_item_settings",
 *   label = @Translation("Toolbar Item Settings"),
 *   handlers = {},
 *   config_prefix = "toolbar_item_settings",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "enabled",
 *     "weight"
 *   },
 *   links = {}
 * )
 */
class ToolbarItemSettings extends ConfigEntityBase {

  /**
   * Content Type settings id. It will be content type bundle name.
   *
   * @var string
   */
  public $id;

  /**
   * Field storing whether item is enabled or not.
   */
  public $enabled;

  /**
   * Field storing whether item wight.
   */
  public $weight;

  public static function loadOrCreate($id) {
    $entity =  \Drupal::entityTypeManager()
      ->getStorage('toolbar_item_settings')
      ->load($id);
    if (!$entity) {
      $entity = \Drupal::entityTypeManager()->getStorage('toolbar_item_settings')
       ->create(['id' => $id]);
    }
    return $entity;
  }

  public static function loadMultipleSorted(array $ids = NULL) {
    $entities =  \Drupal::entityTypeManager()
      ->getStorage('toolbar_item_settings')
      ->loadMultiple($ids);

    uasort($entities, function($a, $b) {return strcmp($a->weight, $b->weight);});
    
    return $entities;
  }

}

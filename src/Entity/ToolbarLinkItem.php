<?php

namespace Drupal\toolbar_manager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Defines the Content Type settings entity.
 *
 * @ConfigEntityType(
 *   id = "toolbar_link_item",
 *   label = @Translation("Toolbar Link Item"),
 *   handlers = {
 *     "list_builder" = "Drupal\toolbar_manager\Controller\ToolbarLinkItemListBuilder",
 *     "form" = {
 *       "add" = "Drupal\toolbar_manager\Form\ToolbarLinkItemForm",
 *       "edit" = "Drupal\toolbar_manager\Form\ToolbarLinkItemForm",
 *       "delete" = "Drupal\toolbar_manager\Form\ToolbarLinkItemDeleteForm",
 *     }
 *   },
 *   config_prefix = "toolbar_link_item",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "text",
 *     "url",
 *     "enabled",
 *     "weight",
 *     "attributes"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/user-interface/toolbar-manager/link-item/{toolbar_link_item}",
 *     "delete-form" = "/admin/config/user-interface/toolbar-manager/link-item/{toolbar_link_item}/delete",
 *   }
 * )
 */
class ToolbarLinkItem extends ConfigEntityBase {

  /**
   * Content Type settings id. It will be content type bundle name.
   *
   * @var string
   */
  public $id;

  /**
   * Field storing link text.
   */
  public $text;

  /**
   * Field storing link url.
   */
  public $url;

  /**
   * Field storing whether item is enabled or not.
   */
  public $enabled;

  /**
   * Field storing whether item wight.
   */
  public $weight;

  /**
   * Field storing custom label.
   */
  public $attributes;

  public static function loadOrCreate($id) {
    $entity =  \Drupal::entityTypeManager()
      ->getStorage('toolbar_link_item')
      ->load($id);
    if (!$entity) {
      $entity = \Drupal::entityTypeManager()->getStorage('toolbar_link_item')
       ->create(['id' => $id]);
    }
    return $entity;
  }

  public static function loadMultipleSorted(array $ids = NULL) {
    $entities =  \Drupal::entityTypeManager()
      ->getStorage('toolbar_link_item')
      ->loadMultiple($ids);

    uasort($entities, function($a, $b) {return strcmp($a->weight, $b->weight);});

    return $entities;
  }

  /**
   * From \Drupal\link\Plugin\Field\FieldWidget\LinkWidget
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    //$form_state->setValueForElement($element, $uri);

    // If getUserEnteredStringAsUri() mapped the entered value to a 'internal:'
    // URI , ensure the raw value begins with '/', '?' or '#'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (parse_url($uri, PHP_URL_SCHEME) === 'internal' && !in_array($element['#value'][0], ['/', '?', '#'], TRUE) && substr($element['#value'], 0, 7) !== '<front>') {
      $form_state->setError($element, t('Manually entered paths should start with one of the following characters: / ? #'));
      return;
    }
  }

  /**
   * From \Drupal\link\Plugin\Field\FieldWidget\LinkWidget
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.
   *
   * @see static::getUriAsDisplayableString()
   */
  public static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = trim($string);

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //    https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

}

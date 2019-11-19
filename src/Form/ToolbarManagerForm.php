<?php

namespace Drupal\toolbar_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\toolbar_manager\Entity\ToolbarItemSettings;

class ToolbarManagerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['toolbar_manager.toolbar_items'];
  }

  public function getFormId() {
    return 'toolbar_manager_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $toolbar_items_settings = ToolbarItemSettings::loadMultipleSorted();

    $module_handler = \Drupal::moduleHandler();
    $toolbar_items = $module_handler->invokeAll('toolbar');
    $module_handler->alter('toolbar', $toolbar_items);

    foreach ($toolbar_items as $id => &$toolbar_item) {
      if (isset($toolbar_items_settings[$id])) {
        $toolbar_item['#weight'] = $toolbar_items_settings[$id]->weight;
      }
      if (!isset($toolbar_item['#weight'])) {
        $toolbar_item['#weight'] = 0;
      }
    }

    uasort($toolbar_items, ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    $renderer = \Drupal::service('renderer');

    $group_class = 'group-order-weight';

    $items = [];
    foreach ($toolbar_items as $key => $toolbar_item) {
      $enabled = isset($toolbar_items_settings[$key]) ? $toolbar_items_settings[$key]->enabled : TRUE;
      $items[] = [
        'id' => $key,
        'tab' => strip_tags($renderer->render($toolbar_item['tab'])),
        'enabled' => $enabled,
        'weight' => $toolbar_item['#weight'],
      ];
    }

    // Build table.
    $form['items'] = [
      '#type' => 'table',
      '#caption' => $this->t('Please clear the cache after making changes.'),
      '#header' => [
        $this->t('Tab'),
        $this->t('ID'),
        $this->t('Enabled'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No items.'),
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $group_class,
          'hidden' => TRUE,
        ]
      ]
    ];

    // Build rows.
    foreach ($items as $key => $value) {
      $form['items'][$key]['#attributes']['class'][] = 'draggable';
      $form['items'][$key]['#weight'] = $value['weight'];

      // Label col.
      $form['items'][$key]['tab'] = [
        '#plain_text' => $value['tab'],
      ];

      // ID col.
      $form['items'][$key]['id'] = [
        '#type' => 'item',
        '#value' => $value['id'],
        '#plain_text' => $value['id'],
      ];

      $form['items'][$key]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @id toolbar item', ['@id' => $value['id']]),
        '#title_display' => 'invisible',
        '#default_value' => $value['enabled'],
      ];

      // Weight col.
      $form['items'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $value['tab']]),
        '#title_display' => 'invisible',
        '#default_value' => $value['weight'],
        '#attributes' => ['class' => [$group_class]],
      ];
    }

    // Form action buttons.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $items = $form_state->getValue('items');

    foreach ($items as $item) {
      $item_settings = ToolbarItemSettings::loadOrCreate($item['id']);
      $item_settings->enabled = (boolean) $item['enabled'];
      $item_settings->weight = $item['weight'];
      $item_settings->save();
    }

    parent::submitForm($form, $form_state);
  }

}
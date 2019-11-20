<?php

namespace Drupal\toolbar_manager\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\link\LinkItemInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Form handler for the Example add and edit forms.
 */
class ToolbarLinkItemForm extends EntityForm {

  /**
   * Constructs an ExampleForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $link_item = $this->entity;

    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#maxlength' => 50,
      '#default_value' => $link_item->text,
      '#description' => $this->t(""),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $link_item->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$link_item->isNew(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
        'source' => ['text'],
      ],
      '#description' => t('A unique machine-readable name for this item. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    if ($link_item->isNew()) {
      $form['id']['#field_prefix'] = 'toolbar_link_';
    }

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#maxlength' => 255,
      '#default_value' => $link_item->url,
      '#description' => $this->t(""),
      '#required' => TRUE,
      '#element_validate' => [['Drupal\toolbar_manager\Entity', 'validateUriElement']],
      //'#link_type' => LinkItemInterface::LINK_INTERNAL,
    ];

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $link_item = $this->entity;

    if ($link_item->isNew()) {
      $link_item->id = 'toolbar_link_' . $link_item->id;
    }

    $status = $link_item->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label Example.', [
        '%label' => $link_item->text,
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label Example was not saved.', [
        '%label' => $link_item->text,
      ]), MessengerInterface::TYPE_ERROR);
    }

    $form_state->setRedirect('entity.toolbar_link_item.collection');
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('toolbar_link_item')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}

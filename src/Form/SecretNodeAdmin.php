<?php

namespace Drupal\secret_node\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class SecretNodeAdmin extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'secret_node.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'secret_node_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * Returns array of nodes in nid => title format.
   * @returns array
   */
  protected function getNodesByType($type) {
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.type', $type);
    $query->orderBy('n.title');
    $nodes = $query->execute()->fetchAllKeyed();
    return $nodes;
  }

  /**
   * Returns the form item that needs to be refreshed
   * @returns array
   */
  public function updateNodeTypeOptions(array &$form, FormStateInterface $form_state) {
    if ($selectedValue = $form_state->getValue('node_type')) {
      // get the selected node type
      $selectedType = $form['node_type']['#options'][$selectedValue];
      $form['node_nid']['#options'] = $this->getNodesByType($selectedValue);
      // without this may run into illegal choice errors
      $form['node_nid']['#validated'] = TRUE;
    }
    return $form['node_nid'];
}

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(static::SETTINGS);

    $type_options = node_type_get_names();
    if ( !$config->get('secret_node_node_type') ) {
      $type_options = ['' => '-- select --'] + $type_options;
    }

    $form['admin_markup'] = [
      '#type' => 'markup',
      '#markup' => 'Use this form to set the Secret Node.  Once this has been set you may <a href="/secret-node">visit the form</a>.',
    ];

    $form['node_type'] = [
      '#title' => $this->t('Node Type'),
      '#type' => 'select',
      '#options' => $type_options,
      '#default_value' => $config->get('secret_node_node_type'),
      '#ajax' => [
        'callback' => '::updateNodeTypeOptions',
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'node-nid-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Please Wait...'),
        ],
      ]

    ];

    $form['node_nid'] = [
      '#type' => 'select',
      '#options' => $this->getNodesByType($config->get('secret_node_node_type')),
      '#title' => $this->t('Node ID'),
      '#default_value' => $config->get('secret_node_nid'),
      '#prefix' => '<div id="node-nid-wrapper">',
      '#suffix' => '</div>',
      // without this may run into illegal choice errors
      '#validated' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration and update it with the form values
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('secret_node_node_type', $form_state->getValue('node_type'))
      ->set('secret_node_nid', $form_state->getValue('node_nid'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

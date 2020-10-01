<?php

/**
 * @file
 * Contains \Drupal\secret_node\Form\SecretNode.
 */
namespace Drupal\secret_node\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;



class SecretNode extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'secret_node';
  }

  /**
   * Returns array of nodes in nid => title format.
   * @returns array
   */
  protected function getNodes() {
    // get the type from the module's configuration
    $type = \Drupal::config('secret_node.settings')->get('secret_node_node_type');
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.type', $type);
    $query->orderBy('n.title');
    $nodes = $query->execute()->fetchAllKeyed();
    return $nodes;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // get the secret nid from the module config
    $nid = \Drupal::config('secret_node.settings')->get('secret_node_nid');

    // only show the form if the nid has been set by admin
    if ($nid) {

      $form['form_markup'] = array(
        '#type' => 'markup',
        '#markup' => 'Find the secret node!  It\'s in there somewhere.  If at first you don\'t succeed...<br /><br />',
      );

      $form['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Your Name:'),
        '#required' => TRUE,
        '#size' => 40,
      );

      $form['node'] = array(
        '#type' => 'select',
        '#title' => t('What is the Secret Node?'),
        '#options' => $this->getNodes(),
        '#required' => TRUE,
      );

      $form['phone'] = array(
        '#type' => 'textfield',
        '#title' => t('Enter your Phone Number'),
        '#required' => TRUE,
        '#description' => '<strong>Congratulations!</strong> You found the secret node!  Please enter your phone number below and we will call you regarding your prize!!!',
        // states will only show this field if the secret node is selected
        '#states' => [
          'visible' => [
            ':input[name="node"]' => ['value' => $nid],
          ],
        ],
      );

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
        // the submit button is only visible if the secret node has been selected
        '#states' => [
          'visible' => [
            ':input[name="node"]' => ['value' => $nid],
          ],
        ],
      );

    } // if ($nid) {

    else {

      // admin needs to configure this module
      $markup = 'This form first needs to be set up by the site admin';
      $form['needs_setup'] = array(
        '#type' => 'markup',
        '#markup' => $markup,
      );

    } // else {

    return $form;

  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // return a message, promising a prize
    $this->messenger()->addStatus($this->t('Thank you @name!  We will call you soon at @phone and make arrangements to deliver your prize!', ['@name' => $form_state->getValue('name'), '@phone' => $form_state->getValue('phone')]));
  }

}

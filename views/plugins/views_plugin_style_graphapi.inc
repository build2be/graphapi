<?php

/**
 * @file
 * Implements views_plugin_style for graphapi
 */

/**
 * Implements views_plugin_style
 */
class views_plugin_style_graphapi extends views_plugin_style {
  private $graph_fields = array(
    'from' => array(
      'id' => array(
        'title' => 'unique ID',
        'description' => 'Used as a unique identifier within the graph',
        'required' => true,
      ),
      'label' => array(
        'title' => 'Label to display',
      ),
      'uri' => array(
        'title' => '(tbd) Link to more info',
        'description' => 'Used for popup or link to the detailed information',
      ),
    ),
    'to' => array(
      'id' => array(
        'title' => 'unique ID',
        'description' => 'Used as a unique identifier within the graph',
        'required' => true,
      ),
      'label' => array(
        'title' => 'Label to display',
      ),
      'uri' => array(
        'title' => '(tdb) Link to more info',
        'description' => 'Used for popup or link to the detailed information',
      ),
    ),
  );

  /**
   * Implementation of views_plugin_style::option_definition
   */
  function option_definition() {
    $options = parent::option_definition();
    $options['engine'] = array('default' => 'graphapi');
    foreach ($this->graph_fields as $id => $data) {
      $options[$id] = array('default' => NULL);
    }
    return $options;
  }

  /**
   * Provide a form for setting options.
   */
  function options_form(&$form, &$form_state) {
    // TODO: next line gives grouping option
    // parent::options_form($form, $form_state);

    $options = $this->options;
    $handlers = $this->display->handler->get_handlers('field');
    if (empty($handlers) || count($handlers) < 2) {
      $form['error_markup'] = array(
        '#markup' => '<div class="error messages">' . t('You need at least two field before you can configure your graph settings') . '</div>',
      );
      return;
    }
    $form['engine'] = array(
      '#type' => 'radios',
      '#title' => t('Render type'),
      '#description' => t('The render engine to use.'),
      '#options' => graphapi_views_formats(),
      '#default_value' => $options['engine'],
    );

    $field_names = $this->display->handler->get_field_labels();
    $fields = $this->display->handler->get_option('fields');
    array_unshift($field_names, "- none -");
    $form['from'] = array(
      '#type' => 'fieldset',
      '#title' => 'From settings',
      '#collapsible' => TRUE,
      '#collapsed' => !empty($options['from']['id']),
    );
    foreach ($this->graph_fields['from'] as $id => $info) {
      $form['from'][$id] = array(
        '#type' => 'select',
        '#required' => isset($info['required']) && $info['required'],
        '#title' => t($info['title']),
        '#description' => isset($info['description']) ? $info['description'] : '',
        '#options' => $field_names,
        '#default_value' => $options['from'][$id],
      );
    }
    $form['to'] = array(
      '#type' => 'fieldset',
      '#title' => 'To settings',
      '#collapsible' => TRUE,
      '#collapsed' => !empty($options['to']['id']),
    );
    foreach ($this->graph_fields['to'] as $id => $info) {
      $form['to'][$id] = array(
        '#type' => 'select',
        '#required' => isset($info['required']) && $info['required'],
        '#title' => t($info['title']),
        '#options' => $field_names,
        '#default_value' => $options['to'][$id],
      );
    }
    return $form;
  }

  /**
   * Implementation of views_style_plugin::theme_functions(). Returns an array of theme functions to use.
   * for the current style plugin
   * @return array
   */
  function theme_functions() {
    $options = $this->options + $this->option_definition();
    $hook = 'views_graphapi_style_' . $options['engine'];
    return views_theme_functions($hook, $this->view, $this->display);
  }

  /**
   * Implements views_style_plugin::additional_theme_functions(). Returns empty array.
   * @return array
   */
  function additional_theme_functions() {
    return array();
  }

  /**
   * Implementation of view_style_plugin::render()
   */
  function render() {
    $view = $this->view;
    $options = $this->options;
    $field = $view->field;
    $rows = array();
    return theme($this->theme_functions(), array('view' => $view, 'options' => $options, 'rows' => $rows));
  }

}

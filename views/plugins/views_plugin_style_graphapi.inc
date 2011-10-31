<?php

/**
 * @file
 * Implements views_plugin_style for graphapi
 */

/**
 * Implements views_plugin_style
 */
class views_plugin_style_graphapi extends views_plugin_style {

  /**
   * Implementation of views_plugin_style::option_definition
   */
  function option_definition() {
    $options = parent::option_definition();
    $options['engine'] = array('default' => 'graphapi', 'translatable' => FALSE);
    return $options;
  }

  /**
   * Provide a form for setting options.
   */
  function options_form(&$form, &$form_state) {
    $form['schema'] = array(
      '#type' => 'radios',
      '#title' => t('Render type'),
      '#description' => t('The render engine to use.'),
      '#options' => graphapi_views_formats(),
      '#default_value' => $this->options['engine'],
    );
  }

  /**
   * Implementation of views_style_plugin::theme_functions(). Returns an array of theme functions to use.
   * for the current style plugin
   * @return array
   */
  function theme_functions() {
    $options = $this->options;
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

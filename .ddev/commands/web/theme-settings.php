<?php

/**
 * @file
 * Theme settings form for books theme.
 */

declare(strict_types=1);

use Drupal\Core\Form\FormState;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function books_form_system_theme_settings_alter(array &$form, FormState $form_state): void {
  $themeSettings = \Drupal::service('Drupal\Core\Extension\ThemeSettingsProvider');
  $stringTranslation = \Drupal::translation();

  $form['books'] = [
    '#type' => 'details',
    '#title' => $stringTranslation->translate('Theme Colors'),
    '#open' => TRUE,
  ];

  $form['books']['primary'] = [
    '#type' => 'textfield',
    '#title' => $stringTranslation->translate('Primary Color - Default'),
    '#default_value' => $themeSettings->getSetting('primary'),
  ];
  $form['books']['secondary'] = [
    '#type' => 'textfield',
    '#title' => $stringTranslation->translate('Secondary Color - Default'),
    '#default_value' => $themeSettings->getSetting('secondary'),
  ];
  $form['books']['accent'] = [
    '#type' => 'textfield',
    '#title' => $stringTranslation->translate('Accent Color - Default'),
    '#default_value' => $themeSettings->getSetting('accent'),
  ];
  $form['books']['info'] = [
    '#type' => 'textfield',
    '#title' => $stringTranslation->translate('Info Color'),
    '#default_value' => $themeSettings->getSetting('info'),
  ];
  $form['books']['warning'] = [
    '#type' => 'textfield',
    '#title' => $stringTranslation->translate('Warning Color'),
    '#default_value' => $themeSettings->getSetting('warning'),
  ];
  $form['books']['error'] = [
    '#type' => 'textfield',
    '#title' => $stringTranslation->translate('Error Color'),
    '#default_value' => $themeSettings->getSetting('error'),
  ];
  $form['books']['success'] = [
    '#type' => 'textfield',
    '#title' => $stringTranslation->translate('Success Color'),
    '#default_value' => $themeSettings->getSetting('success'),
  ];
}

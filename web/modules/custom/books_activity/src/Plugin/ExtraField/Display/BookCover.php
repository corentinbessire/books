<?php

namespace Drupal\books_activity\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Example Extra field Display.
 *
 * @ExtraFieldDisplay(
 *   id = "book_cover",
 *   label = @Translation("Book Cover"),
 *   description = @Translation("Display the cover of the book linked with the activity."),
 *   bundles = {
 *     "node.activity",
 *   }
 * )
 */
class BookCover extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  private $mediaViewBuilder;

  /**
   * Constructs a ExtraFieldDisplayFormattedBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->mediaViewBuilder = $entityTypeManager->getViewBuilder('media');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $book = $entity->get('field_book')->entity;
    $cover = $book->get('field_cover')->entity;
    return $this->mediaViewBuilder->view($cover, 'activity');
  }

}

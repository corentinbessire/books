<?php

namespace Drupal\books_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\books_graphql\Wrappers\QueryConnection;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Data Producer to get a list of Taxonomy Terms.
 *
 * @DataProducer(
 *   id = "query_terms",
 *   name = @Translation("Load product category terms"),
 *   description = @Translation("Loads a list of product category terms."),
 *   produces = @ContextDefinition("ProductCategoryConnection",
 *     label = @Translation("Product Category connection")
 *   ),
 *   consumes = {
 *     "offset" = @ContextDefinition("integer",
 *       label = @Translation("Offset"),
 *       required = FALSE
 *     ),
 *     "limit" = @ContextDefinition("integer",
 *       label = @Translation("Limit"),
 *       required = FALSE
 *     ),
 *     "vocabulary" = @ContextDefinition("string",
 *       label = @Translation("Query Filters"),
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class QueryTerms extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  const MAX_LIMIT = 100;

  /**
   * Drupal Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Products constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal Entity Type Manager service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Create and return EntityQuery to get a List of Terms Entities.
   *
   * @param int $offset
   *   Element to offset the query by.
   * @param int $limit
   *   Total number of item to query.
   * @param string|null $vocabulary
   *   The vocabulary of term to query.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Metadata.
   *
   * @return \Drupal\books_graphql\Wrappers\QueryConnection
   *   Query Connection.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(int $offset, int $limit, ?string $vocabulary, RefinableCacheableDependencyInterface $metadata): QueryConnection {
    if ($limit > static::MAX_LIMIT) {
      throw new UserError(sprintf('Exceeded maximum query limit: %s.', static::MAX_LIMIT));
    }

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $entityType = $storage->getEntityType();
    $query = $storage->getQuery()
      ->currentRevision()
      ->accessCheck();
    if ($vocabulary) {
      $query->condition($entityType->getKey('bundle'), $vocabulary);
    }
    $query->range($offset, $limit);

    $metadata->addCacheTags($entityType->getListCacheTags());
    $metadata->addCacheContexts($entityType->getListCacheContexts());

    return new QueryConnection($query);
  }

}

<?php

namespace Drupal\books_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\books_graphql\Wrappers\QueryConnection;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Data Producer to get a list of Node entities.
 *
 * @DataProducer(
 *   id = "query_nodes",
 *   name = @Translation("Load Nodes"),
 *   description = @Translation("Loads a list of nodes."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Node connection")
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
 *     "bundles" = @ContextDefinition("string",
 *       label = @Translation("Entity bundle(s)"),
 *       multiple = TRUE,
 *       required = FALSE
 *     )
 *   }
 * )
 */
class QueryNodes extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  const MAX_LIMIT = 100;

  /**
   * Drupal Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
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
   */
  public function __construct(
    array $configuration,
                               $pluginId,
                               $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Create and return EntityQuery to get a List of Product node Entities.
   *
   * @param int $offset
   *   Element to offset the query by.
   * @param int $limit
   *   Total number of item to query.
   * @param array|null $bundles
   *   Bundles of the Node to Query.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Metadata.
   *
   * @return \Drupal\books_graphql\Wrappers\QueryConnection
   *   Query Connection.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(int $offset, int $limit, ?array $bundles, RefinableCacheableDependencyInterface $metadata): QueryConnection {
    if ($limit > static::MAX_LIMIT) {
      throw new UserError(sprintf('Exceeded maximum query limit: %s.', static::MAX_LIMIT));
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $entityType = $storage->getEntityType();
    $query = $storage->getQuery()
      ->currentRevision()
      ->accessCheck();

    if (isset($bundles)) {
      $query->condition($entityType->getKey('bundle'), $bundles, 'IN');
    }

    $query->range($offset, $limit);

    $metadata->addCacheTags($entityType->getListCacheTags());
    $metadata->addCacheContexts($entityType->getListCacheContexts());

    return new QueryConnection($query);
  }

}

<?php

namespace Drupal\books_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\books_graphql\Wrappers\QueryConnection;

/**
 * Extends Books Schema for Book Node.
 *
 * @SchemaExtension(
 *   id = "node_book",
 *   name = "Node - Book",
 *   description = "Extends the Books Schema to return Book nodes entities.",
 *   schema = "books_schema"
 * )
 */
class NodeBookSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $this->addQueryFields($registry, $builder);
    $this->addPageFields($registry, $builder);
    $this->addPageConnectionFields($registry, $builder);

  }

  /**
   * Add Query Resolver for Book and books query.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The Resolver Registry Interface.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The ResolverBuilder.
   */
  protected function addQueryFields(ResolverRegistryInterface &$registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Query', 'book',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['book']))
        ->map('id', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'books',
      $builder->produce('query_nodes')
        ->map('offset', $builder->fromArgument('offset'))
        ->map('limit', $builder->fromArgument('limit'))
        ->map('bundles', $builder->fromValue(['book']))
    );

  }

  /**
   * Add Field Resolver for Book node entities.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The Resolver Registry Interface.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The ResolverBuilder.
   */
  private function addPageFields(ResolverRegistryInterface &$registry, ResolverBuilder $builder) {

    $registry->addFieldResolver('Book', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Book', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Book', 'type',
      $builder->produce('entity_bundle')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Book', 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Book', 'updated',
      $builder->produce('entity_changed')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Book', 'published',
      $builder->produce('entity_published')
        ->map('entity', $builder->fromParent())
    );

  }

  /**
   * Add Field Resolver for Book node entities list.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The Resolver Registry Interface.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The ResolverBuilder.   *.
   */
  private function addPageConnectionFields(ResolverRegistryInterface &$registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('BookConnection', 'total',
      $builder->callback(function (QueryConnection $connection) {
        return $connection->total();
      })
    );
    $registry->addFieldResolver('BookConnection', 'items',
      $builder->callback(function (QueryConnection $connection) {
        return $connection->items();
      })
    );
  }

}

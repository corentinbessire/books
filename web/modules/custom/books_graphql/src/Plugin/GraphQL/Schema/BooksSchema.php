<?php

namespace Drupal\books_graphql\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\taxonomy\TermInterface;
use GraphQL\Error\Error;

/**
 * Create basic Schema for books GraphQL server.
 *
 * @Schema(
 *   id = "books_schema",
 *   name = "Books schema"
 * )
 */
class BooksSchema extends SdlSchemaPluginBase
{

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry()
  {
    $builder = new ResolverBuilder();
    $registry = new ResolverRegistry();

    $this->addTypeResolver($registry);
    $this->addNodeFields($registry, $builder);
    $this->addTermFields($registry, $builder);
    $this->addMediaFields($registry, $builder);
    $this->addRouteField($registry, $builder);

    return $registry;
  }

  /**
   * Try to resolve the Type of schema to return based on the entity's bundle.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The Resolver Registry Interface.
   *
   * @return string
   *   The corresponding schema.
   */
  protected function addTypeResolver(ResolverRegistryInterface &$registry)
  {
    $registry->addTypeResolver('NodeInterface', function ($value) {
      if ($value instanceof NodeInterface) {
        switch ($value->bundle()) {

          case 'book':
            return 'Book';

          default:
            return 'Node';
        }
      } else {
        throw new Error('Could not resolve content type . ');
      }
    });

    $registry->addTypeResolver('TermInterface', function ($value) {
      if ($value instanceof TermInterface) {
        return 'Term';
      } else {
        throw new Error('Could not resolve Vocabulary . ');
      }
    });

    $registry->addTypeResolver('MediaInterface', function ($value) {
      if ($value instanceof MediaInterface) {
        switch ($value->bundle()) {
          case 'image':
            return 'Image';
          default:
            return 'Media';
        }
      } else {
        throw new Error('Could not resolve Media Type. ');
      }
    });
  }

  /**
   * Add Standard Field resolver for a Node Entity.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   *   The ResolverBuilder.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The ResolverBuilder.
   */
  protected function addNodeFields(ResolverRegistry &$registry, ResolverBuilder $builder)
  {

    $registry->addFieldResolver('Node', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Node', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Node', 'type',
      $builder->produce('entity_bundle')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Node', 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Node', 'updated',
      $builder->produce('entity_changed')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Node', 'published',
      $builder->produce('entity_published')
        ->map('entity', $builder->fromParent())
    );

  }

  /**
   * Add Standard Field resolver for a Term Entity.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   *   The ResolverBuilder.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The ResolverBuilder.
   */
  protected function addTermFields(ResolverRegistry &$registry, ResolverBuilder $builder)
  {
    $registry->addFieldResolver('Term', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Term', 'vocabulary',
      $builder->produce('entity_bundle')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Term', 'name',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

  }

  /**
   * Add Standard Field resolver for a Media Entity.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   *   The ResolverBuilder.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The ResolverBuilder.
   */
  protected function addMediaFields(ResolverRegistry &$registry, ResolverBuilder $builder)
  {
    $registry->addFieldResolver('Media', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Media', 'type',
      $builder->produce('entity_bundle')
        ->map('entity', $builder->fromParent())
    );

  }

  /**
   * Add Field Resolver for Route query
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   *   The ResolverBuilder.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The ResolverBuilder.
   */
  protected function addRouteField(ResolverRegistry &$registry, ResolverBuilder $builder)
  {
    $registry->addFieldResolver('Query', 'route', $builder->compose(
      $builder->produce('route_load')
        ->map('path', $builder->fromArgument('path')),

      $builder->produce('route_entity')
        ->map('url', $builder->fromParent())
    ));
  }


}

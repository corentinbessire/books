<?php

namespace Drupal\books_graphql\Wrappers;

use Drupal\Core\Entity\Query\QueryInterface;
use GraphQL\Deferred;

/**
 * Helper class that wraps entity queries.
 */
class QueryConnection {

  /**
   * Query to wrapp.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * QueryConnection constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   Query to wrap.
   */
  public function __construct(QueryInterface $query) {
    $this->query = $query;
  }

  /**
   * Calculate the total number of item of the Query.
   *
   * @return int
   *   Total number of items returned by the full query.
   */
  public function total(): int {
    $query = clone $this->query;
    $query->range(NULL, NULL)->count();
    return $query->execute();
  }

  /**
   * Return the result of The query formatted as GraphQL Type.
   *
   * @return array|\GraphQL\Deferred
   *   Formatted Result of the given Query.
   */
  public function items() {
    $result = $this->query->execute();
    if (empty($result)) {
      return [];
    }

    $buffer = \Drupal::service('graphql.buffer.entity');
    $callback = $buffer->add($this->query->getEntityTypeId(), array_values($result));
    return new Deferred(function () use ($callback) {
      return $callback();
    });
  }

}

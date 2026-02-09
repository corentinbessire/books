<?php

namespace Drupal\Tests\books_activity\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Kernel tests for books_activity_node_presave() hook.
 *
 * @group books_activity
 */
class ActivityPresaveTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'field',
    'text',
    'user',
    'books_activity',
    'books_book_managment',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'node', 'field']);

    // Create content types.
    NodeType::create(['type' => 'book', 'name' => 'Book'])->save();
    NodeType::create(['type' => 'activity', 'name' => 'Activity'])->save();
  }

  /**
   * Tests presave hook sets title from book reference.
   */
  public function testPresaveSetsActivityTitle(): void {
    // Create a book node.
    $book = Node::create([
      'type' => 'book',
      'title' => 'Moby Dick',
    ]);
    $book->save();

    // Create an activity referencing the book.
    $activity = Node::create([
      'type' => 'activity',
      'title' => 'Placeholder',
      'field_book' => ['target_id' => $book->id()],
    ]);

    // Trigger presave (which is called during save).
    $activity->save();

    // The hook should have changed the title.
    $this->assertEquals('Moby Dick', $activity->getTitle());
  }

  /**
   * Tests presave hook does not affect non-activity nodes.
   */
  public function testPresaveIgnoresNonActivityNodes(): void {
    $book = Node::create([
      'type' => 'book',
      'title' => 'Original Title',
    ]);
    $book->save();

    // Title should remain unchanged.
    $this->assertEquals('Original Title', $book->getTitle());
  }

}

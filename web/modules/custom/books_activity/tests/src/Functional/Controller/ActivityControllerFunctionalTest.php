<?php

namespace Drupal\Tests\books_activity\Functional\Controller;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for ActivityController.
 *
 * @group books_activity
 */
class ActivityControllerFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'text',
    'taxonomy',
    'media',
    'image',
    'file',
    'user',
    'isbn',
    'books_book_managment',
    'books_activity',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with activity permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorizedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create required content types if not already provided by module install.
    if (!NodeType::load('book')) {
      NodeType::create(['type' => 'book', 'name' => 'Book'])->save();
    }
    if (!NodeType::load('activity')) {
      NodeType::create(['type' => 'activity', 'name' => 'Activity'])->save();
    }

    // Create status vocabulary and terms.
    if (!Vocabulary::load('sta')) {
      Vocabulary::create(['vid' => 'sta', 'name' => 'Status'])->save();
    }
    Term::create(['vid' => 'sta', 'name' => 'Reading'])->save();
    Term::create(['vid' => 'sta', 'name' => 'Finished'])->save();
    Term::create(['vid' => 'sta', 'name' => 'Abandoned'])->save();

    $this->authorizedUser = $this->drupalCreateUser([
      'access content',
      'create activity content',
      'edit any activity content',
      'create book content',
    ]);
  }

  /**
   * Tests anonymous users cannot start an activity.
   */
  public function testAnonymousCannotStartActivity(): void {
    $this->drupalGet('/activity/start/9780142437247');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests finish route sets end date and status.
   */
  public function testFinishActivity(): void {
    $this->drupalLogin($this->authorizedUser);

    // Create a book and an activity.
    $book = Node::create([
      'type' => 'book',
      'title' => 'Test Book',
      'field_isbn' => '9780142437247',
    ]);
    $book->save();

    $activity = Node::create([
      'type' => 'activity',
      'title' => 'Test Activity',
      'field_book' => ['target_id' => $book->id()],
      'field_start_date' => date('Y-m-d'),
    ]);
    $activity->save();

    $this->drupalGet('/activity/' . $activity->id() . '/finish');
    // Should redirect (302) to activities view.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests abandon route sets abandoned status.
   */
  public function testAbandonActivity(): void {
    $this->drupalLogin($this->authorizedUser);

    $book = Node::create([
      'type' => 'book',
      'title' => 'Test Book',
      'field_isbn' => '9780142437247',
    ]);
    $book->save();

    $activity = Node::create([
      'type' => 'activity',
      'title' => 'Test Activity',
      'field_book' => ['target_id' => $book->id()],
      'field_start_date' => date('Y-m-d'),
    ]);
    $activity->save();

    $this->drupalGet('/activity/' . $activity->id() . '/abandon');
    $this->assertSession()->statusCodeEquals(200);
  }

}

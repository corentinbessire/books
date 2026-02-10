<?php

namespace Drupal\Tests\books_book_managment\Functional\Form;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for AddBookForm.
 *
 * @group books_book_managment
 */
class AddBookFormFunctionalTest extends BrowserTestBase {

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
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the 'book' content type (required for 'create book content'
    // permission and the /add-book route).
    if (!NodeType::load('book')) {
      NodeType::create(['type' => 'book', 'name' => 'Book'])->save();
    }
  }

  /**
   * Tests anonymous users are denied access to add-book form.
   */
  public function testAnonymousAccessDenied(): void {
    $this->drupalGet('/add-book');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests authenticated users can access add-book form.
   */
  public function testAuthenticatedAccess(): void {
    $user = $this->drupalCreateUser(['access content', 'create book content']);
    $this->drupalLogin($user);

    $this->drupalGet('/add-book');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('isbn');
    $this->assertSession()->buttonExists('Add book');
  }

  /**
   * Tests form submission with invalid ISBN shows error.
   */
  public function testInvalidIsbnShowsError(): void {
    $user = $this->drupalCreateUser(['access content', 'create book content']);
    $this->drupalLogin($user);

    $this->drupalGet('/add-book');
    $this->submitForm(['isbn' => 'invalid-isbn'], 'Add book');

    $this->assertSession()->pageTextContains('This is not a valid ISBN number.');
  }

}

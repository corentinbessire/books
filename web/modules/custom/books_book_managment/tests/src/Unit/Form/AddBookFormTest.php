<?php

namespace Drupal\Tests\books_book_managment\Unit\Form;

use Drupal\books_book_managment\Form\AddBookForm;
use Drupal\books_book_managment\Services\BooksUtilsService;
use Drupal\books_book_managment\Services\CoverDownloadService;
use Drupal\books_book_managment\Services\GoogleBooksService;
use Drupal\books_book_managment\Services\OpenLibraryService;
use Drupal\isbn\IsbnToolsServiceInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for AddBookForm.
 *
 * @group books_book_managment
 * @coversDefaultClass \Drupal\books_book_managment\Form\AddBookForm
 */
class AddBookFormTest extends UnitTestCase {

  /**
   * The form under test.
   *
   * @var \Drupal\books_book_managment\Form\AddBookForm
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $isbnService = $this->createMock(IsbnToolsServiceInterface::class);
    $openLibrary = $this->createMock(OpenLibraryService::class);
    $googleBooks = $this->createMock(GoogleBooksService::class);
    $coverDownload = $this->createMock(CoverDownloadService::class);
    $booksUtils = $this->createMock(BooksUtilsService::class);

    $this->form = new AddBookForm(
      $isbnService,
      $openLibrary,
      $googleBooks,
      $coverDownload,
      $booksUtils
    );
  }

  /**
   * Tests mergeBookData() gives priority to first array.
   *
   * @covers ::mergeBookData
   */
  public function testMergeBookDataPriority(): void {
    $method = new \ReflectionMethod(AddBookForm::class, 'mergeBookData');

    $google = [
      'title' => 'Google Title',
      'field_pages' => 200,
      'field_publisher' => 'Google Publisher',
    ];
    $openLibrary = [
      'title' => 'OpenLibrary Title',
      'field_pages' => 210,
      'field_authors' => ['Author A'],
    ];

    $result = $method->invoke($this->form, $google, $openLibrary);

    // Google values take priority.
    $this->assertEquals('Google Title', $result['title']);
    $this->assertEquals(200, $result['field_pages']);
    $this->assertEquals('Google Publisher', $result['field_publisher']);
    // OpenLibrary fills missing fields.
    $this->assertEquals(['Author A'], $result['field_authors']);
  }

  /**
   * Tests mergeBookData() with empty first array.
   *
   * @covers ::mergeBookData
   */
  public function testMergeBookDataEmptyFirst(): void {
    $method = new \ReflectionMethod(AddBookForm::class, 'mergeBookData');

    $openLibrary = [
      'title' => 'OL Title',
      'field_isbn' => '9780000000000',
    ];

    $result = $method->invoke($this->form, [], $openLibrary);

    $this->assertEquals('OL Title', $result['title']);
    $this->assertEquals('9780000000000', $result['field_isbn']);
  }

  /**
   * Tests mergeBookData() with both arrays empty.
   *
   * @covers ::mergeBookData
   */
  public function testMergeBookDataBothEmpty(): void {
    $method = new \ReflectionMethod(AddBookForm::class, 'mergeBookData');

    $result = $method->invoke($this->form, [], []);
    $this->assertEmpty($result);
  }

  /**
   * Tests getFormId() returns correct ID.
   *
   * @covers ::getFormId
   */
  public function testGetFormId(): void {
    $this->assertEquals('add_book_form', $this->form->getFormId());
  }

}

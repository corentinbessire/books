<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Custom Service to handle various Book related actions.
 */
class BooksUtilsService {

  /**
   * Constructs a BooksUtilsService object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected LoggerChannelFactoryInterface $loggerChannelFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Save Given Data into Book node entity.
   *
   * @param string $isbn
   *   ISBN-13 of the book to save data in.
   * @param array $data
   *   array of fieldId and FieldValue to save.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Book node entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveBookData(string $isbn, array $data): EntityInterface {
    $book = $this->getBook($isbn);
    if (isset($data['title'])) {
      $book->setTitle($data['title']);
    }
    else {
      $book->setTitle($isbn);
    }
    if ($data['field_pages']) {
      $book->set('field_pages', $data['field_pages']);
    }
    if ($data['field_isbn']) {
      $book->set('field_isbn', $data['field_isbn']);
    }
    if ($data['field_release']) {
      $book->set('field_release', $data['field_release']);
    }
    if ($data['field_excerpt']) {
      $book->set('field_excerpt', $data['field_excerpt']);
    }
    if ($data['field_cover']) {
      $book->set('field_cover', $data['field_cover']);
    }

    if ($data['field_publisher']) {
      $publisher = $this->getTermByName($data['field_publisher'], 'publisher');
      $book->set('field_publisher', $publisher);
    }
    if ($data['field_authors']) {
      $authors = [];
      foreach ($data['field_authors'] as $author) {
        $authors[]['target_id'] = $this->getTermByName($author, 'author')->id();
      }
      $book->set('field_authors', $authors);
    }

    $book->save();
    return $book;
  }

  /**
   * Return existing Node Book with given ISBN or create new entity.
   *
   * @param string $isbn
   *   ISBN-13 Value.
   * @param bool $create
   *   Create the Book Node if not existing.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Book node Entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBook(string $isbn, bool $create = TRUE): ?EntityInterface {
    $books = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['field_isbn' => $isbn]);
    if (!empty($books)) {
      return end($books);
    }
    else {
      if ($create) {
        return $this->entityTypeManager->getStorage('node')
          ->create(['type' => 'book']);
      }
      else {
        return NULL;
      }
    }
  }

  /**
   * Upsert Term given Name and VID.
   *
   * @param string $termName
   *   The Term Name to Upsert.
   * @param string $vid
   *   The Vocabulary id of the Term to Upsert.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Upserted Entity term.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getTermByName(string $termName, string $vid): ?EntityInterface {
    if (!$termName) {
      return NULL;
    }
    $result = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', $vid)
      ->condition('name', $termName)
      ->accessCheck()
      ->execute();
    if (empty($result)) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->create([
        'vid' => $vid,
      ]);
      $term->set('name', $termName);
      $term->save();
    }
    else {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')
        ->load(reset($result));
    }
    return $term;
  }

  /**
   * Get an array of NIDs of Book nodes without Cover.
   *
   * @return array
   *   Array of Nids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBooksMissingCover(): array {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'book')
      ->notExists('field_cover')
      ->accessCheck()
      ->execute();
    return $nids;
  }

}

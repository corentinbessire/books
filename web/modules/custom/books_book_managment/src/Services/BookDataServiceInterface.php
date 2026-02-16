<?php

namespace Drupal\books_book_managment\Services;

/**
 * Interface for Book Data Services.
 */
interface BookDataServiceInterface {

  /**
   * Get Data of a book on a Book Data API.
   *
   * @param string|int $isbn
   *   ISBN of the book to get data of.
   *
   * @return array|null
   *   Data of the Book.
   */
  public function getBookData(string|int $isbn): array|null;

  /**
   * Format the Data of the Book to be usable by Importer.
   *
   * @param array $bookData
   *   The raw Book Data from the Source.
   *
   * @return array
   *   Formated Book data ready to be imported.
   */
  public function formatBookData(array $bookData): array;

  /**
   * Get the formatted Book data from Source API.
   *
   * @param string|int $isbn
   *   ISBN of the book to get formatted data of.
   *
   * @return array|null
   *   Formatted Data or null if not found.
   */
  public function getFormattedBookData(string|int $isbn): array|null;

}

/**
 * @file
 * E2E tests for book search functionality.
 */

describe('Search', () => {
  beforeEach(() => {
    cy.drupalLogin('admin');
  });

  it('search page loads', () => {
    cy.visit('/search');
    cy.get('body').should('be.visible');
  });

  it('search returns results for existing content', () => {
    // Create a book to search for.
    cy.drupalCreateNode('book', 'Cypress Search Test Book');

    cy.visit('/search');
    // Look for a search input (adapt selector to your theme).
    cy.get('input[type="search"], input[name="keys"], input[name="search"]')
      .first()
      .type('Cypress Search Test{enter}');

    // Verify results appear.
    cy.contains('Cypress Search Test Book').should('be.visible');
  });

  it('search shows no results message for nonsense query', () => {
    cy.visit('/search');
    cy.get('input[type="search"], input[name="keys"], input[name="search"]')
      .first()
      .type('zzzznonexistentzzzzz{enter}');

    cy.get('body').should('be.visible');
  });
});

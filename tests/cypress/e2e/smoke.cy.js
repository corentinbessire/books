/**
 * @file
 * Smoke tests — basic site health checks.
 */

describe('Smoke Tests', () => {
  it('homepage loads successfully', () => {
    cy.visit('/');
    cy.get('body').should('be.visible');
  });

  it('anonymous user sees login link', () => {
    cy.visit('/');
    cy.get('a[href*="/user/login"]').should('exist');
  });

  it('authenticated user can access dashboard', () => {
    cy.drupalLogin('admin');
    cy.visit('/');
    cy.get('body').should('be.visible');
    // Admin toolbar or user menu should be visible.
    cy.get('a[href*="/user/logout"]').should('exist');
  });

  it('404 page returns proper status', () => {
    cy.request({ url: '/non-existent-page-12345', failOnStatusCode: false }).then(
      (response) => {
        expect(response.status).to.eq(404);
      },
    );
  });

  it('anonymous user is denied access to admin', () => {
    cy.request({ url: '/admin', failOnStatusCode: false }).then((response) => {
      expect(response.status).to.be.oneOf([403, 302]);
    });
  });
});

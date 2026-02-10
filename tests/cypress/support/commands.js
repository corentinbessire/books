/**
 * @file
 * Custom Cypress commands for Drupal testing.
 */

/**
 * Log in to Drupal using a one-time login link via Drush.
 *
 * Usage: cy.drupalLogin('admin')
 *
 * @param {string} username - The Drupal username to log in as.
 */
Cypress.Commands.add('drupalLogin', (username) => {
  cy.exec(`ddev drush uli --name=${username} --uri=https://books.ddev.site`).then((result) => {
    const loginUrl = result.stdout.trim();
    cy.visit(loginUrl);
  });
});

/**
 * Log out of Drupal.
 */
Cypress.Commands.add('drupalLogout', () => {
  cy.visit('/user/logout');
});

/**
 * Create a node via Drush.
 *
 * @param {string} type - The content type machine name.
 * @param {string} title - The node title.
 */
Cypress.Commands.add('drupalCreateNode', (type, title) => {
  cy.exec(
    `ddev drush eval "\\Drupal::entityTypeManager()->getStorage('node')->create(['type' => '${type}', 'title' => '${title}'])->save();"`,
  );
});

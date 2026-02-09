/**
 * @file
 * Cypress E2E support file.
 *
 * Loaded before every spec file. Import custom commands here.
 */

import './commands';

// Prevent Cypress from failing on uncaught exceptions from the app.
Cypress.on('uncaught:exception', () => false);

module.exports = {
  extends: ['stylelint-config-standard'],
  ignoreFiles: ['build/**/*.css', 'node_modules/**/*.css'],
  rules: {
    'at-rule-no-unknown': [
      true,
      {
        ignoreAtRules: [
          'tailwind',
          'apply',
          'variants',
          'responsive',
          'screen',
          'config',
          'theme',
          'custom-media',
          'custom-selector',
        ],
      },
    ],
    'at-rule-empty-line-before': null,
    'declaration-empty-line-before': null,
    'rule-empty-line-before': null,
    'no-descending-specificity': null,
    'import-notation': null,
    'selector-class-pattern': null,
    'function-url-quotes': null,
    'media-feature-range-notation': null,
    'color-function-notation': null,
    'alpha-value-notation': null,
    'hue-degree-notation': null,
    'property-no-vendor-prefix': null,
  },
};

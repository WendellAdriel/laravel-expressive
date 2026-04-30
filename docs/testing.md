# Testing

Run the full Expressive validation suite with Composer:

```bash
composer test
```

During development, you can run individual checks:

```bash
composer analyse
composer lint:check
composer test:types
composer test:unit
```

Use the bundled workbench when expressive needs to be exercised inside a real Laravel application:

```bash
composer build
composer serve
```

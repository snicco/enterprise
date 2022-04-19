## Test conventions for packages in this monorepo:

### Component

All tests go inside a `unit` directory if they can be tested **without** booting WordPress.
All other tests go inside a `wpunit` directory if they must be tested with booting WordPress.

The directory structure inside `unit/wpunit` should mirror the directory structure in `src`.

Only the **public API** exposed by components must be tested. Everything else is an implementation detail.

### Bundle

The same rules as for components apply.

In addition, bundles may have a `integration` suite. Anything using `WebTestCase` belongs in here.
Same for wp-cli tests. `integration` suites are assumed to use  `WPLoader`.

### Plugins

Plugins are tested on multiple layers according to DDD best-practices.

- `unit`: entities, domain services, value objects, etc. (**no WordPress**)
- `integration`: outgoing and incoming adapter tests (**with WordPress**)
- `use-case/application`: The most important tests that test the application from the Application Service layer. (**no WordPress**)
- `e2e`: End-to-end tests against a real selenium server. (**with WordPress**)
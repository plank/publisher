# Laravel Publisher Documentation

Publisher is a Laravel package that provides a complete content publishing workflow, allowing you to maintain both published and draft versions of your content simultaneously. Editors can work on changes without affecting the live published version until changes are explicitly published.

## Documentation

### Guides

- [Installation](guides/installation.md) - Get up and running with Publisher
- [Core Concepts](guides/core-concepts.md) - Understand the fundamentals of the publishing workflow

### Traits

- [IsPublishable](traits/is-publishable.md) - The main trait for making models publishable
- [HasPublishablePivotAttributes](traits/has-publishable-pivot-attributes.md) - Draft support for custom pivot model attributes
- [InteractsWithPublishableContent](traits/interacts-with-publishable-content.md) - Override relationship methods with publishable versions

### Features

- [Draft Management](features/draft-management.md) - How draft attributes are stored and synced
- [Publishing Workflow](features/publishing-workflow.md) - Publishing, unpublishing, and reverting content
- [Publishable Relationships](features/publishable-relationships.md) - BelongsToMany and MorphToMany with draft support
- [Dependent Models](features/dependent-models.md) - Cascading publish state to child models
- [Events](features/events.md) - Lifecycle events for the publishing workflow
- [Querying](features/querying.md) - Query scopes and draft-aware WHERE clauses
- [Middleware](features/middleware.md) - Controlling draft content visibility
- [URL Rewriting](features/url-rewriting.md) - Preserving draft visibility across navigation
- [Authorization](features/authorization.md) - Gates and permissions

### Advanced

- [Custom Workflow States](advanced/custom-workflow-states.md) - Extending beyond published/draft
- [Schema Conflicts](advanced/schema-conflicts.md) - Handling column renames and drops
- [Custom Pivot Models](advanced/custom-pivot-models.md) - Advanced pivot table configurations
- [Admin Panel Integration](advanced/admin-panel-integration.md) - Nova, Filament, and Backpack guides
- [Plank Ecosystem](advanced/plank-ecosystem.md) - Related packages and dependencies

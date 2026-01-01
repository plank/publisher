# Admin Panel Integration

This guide covers integrating Publisher with popular Laravel admin panels: Nova, Filament, and Backpack.

## General Concepts

All admin panels need:

1. **Draft visibility enabled** - Admin should see draft content
2. **Status field** - Allow changing workflow state
3. **Authorization** - Respect publish/unpublish gates
4. **Preview mode** - Optional link to preview draft changes

## Laravel Nova

### Configuration

Add Nova paths to draft paths:

```php
// config/publisher.php
'draft_paths' => [
    'nova-api*',
    'nova-vendor*',
],
```

### Resource Fields

```php
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Boolean;
use Plank\Publisher\Enums\Status;

class Post extends Resource
{
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Title'),

            Textarea::make('Content'),

            Select::make('Status')
                ->options([
                    Status::DRAFT->value => 'Draft',
                    Status::PUBLISHED->value => 'Published',
                ])
                ->displayUsingLabels()
                ->filterable(),

            Boolean::make('Has Been Published', 'has_been_published')
                ->readonly()
                ->hideFromIndex(),

            // Preview link
            Text::make('Preview', function () {
                if ($this->isNotPublished()) {
                    $url = route('posts.show', [
                        'post' => $this->id,
                        config('publisher.urls.previewKey') => true,
                    ]);
                    return "<a href='{$url}' target='_blank'>Preview Draft</a>";
                }
                return 'Published';
            })->asHtml()->onlyOnDetail(),
        ];
    }
}
```

### Nova Actions

```php
use Laravel\Nova\Actions\Action;

class PublishPost extends Action
{
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            if (Gate::allows('publish', $model)) {
                $model->update(['status' => Status::PUBLISHED]);
            }
        }

        return Action::message('Posts published!');
    }
}

class UnpublishPost extends Action
{
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            if (Gate::allows('unpublish', $model)) {
                $model->update(['status' => Status::DRAFT]);
            }
        }

        return Action::message('Posts unpublished!');
    }
}

class RevertPost extends Action
{
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            if ($model->hasEverBeenPublished()) {
                $model->revert();
            }
        }

        return Action::message('Posts reverted!');
    }
}
```

### Nova Policy

```php
class PostPolicy
{
    public function publish(User $user, Post $post): bool
    {
        return $user->can('publish-posts');
    }

    public function unpublish(User $user, Post $post): bool
    {
        return $user->can('unpublish-posts');
    }
}
```

## Filament

### Configuration

Add Filament paths to draft paths:

```php
// config/publisher.php
'draft_paths' => [
    'admin*',      // Or your Filament path
    'filament*',
],
```

### Resource Form

```php
use Filament\Forms;
use Filament\Resources\Resource;
use Plank\Publisher\Enums\Status;

class PostResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required(),

                Forms\Components\Textarea::make('content'),

                Forms\Components\Select::make('status')
                    ->options([
                        Status::DRAFT->value => 'Draft',
                        Status::PUBLISHED->value => 'Published',
                    ])
                    ->default(Status::DRAFT->value)
                    ->required(),

                Forms\Components\Toggle::make('has_been_published')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
```

### Resource Table

```php
use Filament\Tables;

class PostResource extends Resource
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => Status::DRAFT->value,
                        'success' => Status::PUBLISHED->value,
                    ]),

                Tables\Columns\IconColumn::make('has_been_published')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Status::DRAFT->value => 'Draft',
                        Status::PUBLISHED->value => 'Published',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('publish')
                    ->action(fn (Post $record) => $record->update(['status' => Status::PUBLISHED]))
                    ->visible(fn (Post $record) => $record->isNotPublished())
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('unpublish')
                    ->action(fn (Post $record) => $record->update(['status' => Status::DRAFT]))
                    ->visible(fn (Post $record) => $record->isPublished())
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('revert')
                    ->action(fn (Post $record) => $record->revert())
                    ->visible(fn (Post $record) => $record->hasEverBeenPublished() && $record->isNotPublished())
                    ->requiresConfirmation()
                    ->color('danger'),

                Tables\Actions\Action::make('preview')
                    ->url(fn (Post $record) => route('posts.show', [
                        'post' => $record,
                        config('publisher.urls.previewKey') => true,
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn (Post $record) => $record->isNotPublished()),
            ]);
    }
}
```

### Filament Bulk Actions

```php
->bulkActions([
    Tables\Actions\BulkAction::make('publish')
        ->action(function (Collection $records) {
            $records->each(fn ($record) => $record->update(['status' => Status::PUBLISHED]));
        })
        ->requiresConfirmation()
        ->deselectRecordsAfterCompletion(),

    Tables\Actions\BulkAction::make('unpublish')
        ->action(function (Collection $records) {
            $records->each(fn ($record) => $record->update(['status' => Status::DRAFT]));
        })
        ->requiresConfirmation()
        ->deselectRecordsAfterCompletion(),
])
```

## Laravel Backpack

### Configuration

Add Backpack paths:

```php
// config/publisher.php
'draft_paths' => [
    'admin*',
],
```

### CRUD Controller

```php
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Plank\Publisher\Enums\Status;

class PostCrudController extends CrudController
{
    public function setup()
    {
        CRUD::setModel(Post::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/post');
        CRUD::setEntityNameStrings('post', 'posts');
    }

    protected function setupListOperation()
    {
        CRUD::column('title');
        CRUD::column('status');
        CRUD::column('has_been_published')->type('boolean');

        // Add publish button
        CRUD::addButtonFromView('line', 'publish', 'publish', 'end');
    }

    protected function setupCreateOperation()
    {
        CRUD::field('title');
        CRUD::field('content');
        CRUD::field('status')
            ->type('select_from_array')
            ->options([
                Status::DRAFT->value => 'Draft',
                Status::PUBLISHED->value => 'Published',
            ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function publish($id)
    {
        $post = Post::findOrFail($id);

        if (Gate::allows('publish', $post)) {
            $post->update(['status' => Status::PUBLISHED]);
        }

        return redirect()->back();
    }

    public function unpublish($id)
    {
        $post = Post::findOrFail($id);

        if (Gate::allows('unpublish', $post)) {
            $post->update(['status' => Status::DRAFT]);
        }

        return redirect()->back();
    }
}
```

### Backpack Button View

```blade
{{-- resources/views/vendor/backpack/crud/buttons/publish.blade.php --}}
@if ($entry->isNotPublished())
    <a href="{{ url($crud->route.'/'.$entry->getKey().'/publish') }}"
       class="btn btn-sm btn-success"
       onclick="return confirm('Publish this {{ $crud->entity_name }}?')">
        <i class="la la-check"></i> Publish
    </a>
@else
    <a href="{{ url($crud->route.'/'.$entry->getKey().'/unpublish') }}"
       class="btn btn-sm btn-warning"
       onclick="return confirm('Unpublish this {{ $crud->entity_name }}?')">
        <i class="la la-times"></i> Unpublish
    </a>
@endif
```

## Comparison Table

| Feature | Nova | Filament | Backpack |
|---------|------|----------|----------|
| Status Field | Select | Select | select_from_array |
| Quick Actions | Actions | Table Actions | Custom Buttons |
| Bulk Actions | Batch Actions | Bulk Actions | Bulk Buttons |
| Authorization | Policies | Policies | Gates/Policies |
| Preview Link | Custom Field | Action URL | Custom Button |

## Common Patterns

### Show Draft Indicator

Display when viewing draft vs published:

```php
// Badge showing current view mode
if (Publisher::draftContentAllowed()) {
    // Show "Viewing Draft" badge
}
```

### Diff View

Show changes between published and draft:

```php
$post = Post::find($id);

$published = Publisher::withoutDraftContent(fn () => Post::find($id)->toArray());
$draft = Publisher::withDraftContent(fn () => Post::find($id)->toArray());

$changes = array_diff_assoc($draft, $published);
```

### Scheduled Publishing

Add scheduling to your admin:

```php
Forms\Components\DateTimePicker::make('published_at')
    ->label('Schedule Publication')
    ->visible(fn ($record) => $record?->isNotPublished()),
```

## Related Documentation

- [Middleware](../features/middleware.md) - Draft path configuration
- [Authorization](../features/authorization.md) - Gates for admin
- [URL Rewriting](../features/url-rewriting.md) - Preview links

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.5
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== .ai/controller rules ===

<?php
/**
 * 🧠 AI GUIDELINE: Versioned Laravel API Controller Structure
 * ============================================================
 * This controller is versioned (v1) and follows API best practices:
 * - Request validation
 * - DB transactions for multi-model operations
 * - Try/catch error handling
 * - Standardized JSON responses
 * - Activity logging via LoggableTrait
 * - Clean "not found" handling
 * - Filters & pagination in index()
 *
 * 📂 Location: App\Http\Controllers\Api\V1
 * 📌 Naming: {ModelName}Controller.php
 * 🧭 Routes example:
 *    Route::prefix('v1')->group(function () {
 *        Route::apiResource('models', ModelController::class);
 *        Route::post('models/bulk-destroy', [ModelController::class, 'bulkDestroy']);
 *    });
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Traits\LoggableTrait;
use App\Models\ModelName; // 📝 Replace with your actual model

class ModelController extends Controller
{
    use LoggableTrait;

    /**
     * 📜 index() - List resources with optional filters and pagination.
     */
    public function index(Request $request)
    {
        $query = ModelName::with(['createdBy', 'updatedBy']);

        // 🔍 Filters
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('startDate')) {
            $query->whereDate('created_at', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->whereDate('created_at', '<=', $request->query('endDate'));
        }

        // 📊 Pagination
        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'message' => 'Data retrieved successfully',
                'data' => $data,
                'version' => 'v1',
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Data retrieved successfully',
            'data' => $data,
            'version' => 'v1',
        ]);
    }

    /**
     * 🆕 store() - Create a new resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:model_names,name',
            'status' => 'sometimes|string|in:active,inactive',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        try {
            DB::beginTransaction();

            $model = ModelName::create($validated);

            $this->logActivity('model_created', "Model '{$model->name}' created.", [
                'model_id' => $model->id,
            ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Resource created successfully',
                    'data' => $model,
                    'version' => 'v1',
                ],
                201,
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'version' => 'v1',
                ],
                422,
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500,
            );
        }
    }

    /**
     * 👁️ show() - Display a single resource.
     */
    public function show($id)
    {
        $model = ModelName::find($id);

        if (!$model) {
            return response()->json(
                [
                    'message' => 'The requested resource was not found',
                    'error' => 'not_found',
                    'code' => 404,
                    'version' => 'v1',
                ],
                404,
            );
        }

        return response()->json([
            'message' => 'Resource retrieved successfully',
            'data' => $model,
            'version' => 'v1',
        ]);
    }

    /**
     * ✍️ update() - Update an existing resource.
     */
    public function update(Request $request, $id)
    {
        try {
            $model = ModelName::find($id);

            if (!$model) {
                return response()->json(
                    [
                        'message' => 'The requested resource was not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404,
                );
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:model_names,name,' . $model->id,
                'status' => 'sometimes|string|in:active,inactive',
            ]);

            $validated['updated_by'] = Auth::id();

            DB::beginTransaction();
            $model->update($validated);

            $this->logActivity('model_updated', "Model '{$model->name}' updated.", [
                'model_id' => $model->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Resource updated successfully',
                'data' => $model,
                'version' => 'v1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'version' => 'v1',
                ],
                422,
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500,
            );
        }
    }

    /**
     * 🗑️ destroy() - Delete a resource.
     */
    public function destroy($id)
    {
        try {
            $model = ModelName::find($id);

            if (!$model) {
                return response()->json(
                    [
                        'message' => 'The requested resource was not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404,
                );
            }

            DB::beginTransaction();
            $model->delete();

            $this->logActivity('model_deleted', "Model '{$model->name}' deleted.", [
                'model_id' => $model->id,
            ]);
            DB::commit();

            return response()->json([
                'message' => 'Resource deleted successfully',
                'version' => 'v1',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500,
            );
        }
    }

    /**
     * 🧹 bulkDestroy() - Delete multiple resources at once.
     */
    public function bulkDestroy(Request $request)
    {
        $items = $request->input('itemsToDelete');

        if (!is_array($items) || empty($items)) {
            return response()->json(
                [
                    'message' => 'Invalid or empty data',
                    'error' => 'bad_request',
                    'code' => 400,
                    'version' => 'v1',
                ],
                400,
            );
        }

        $ids = array_column($items, 'id');

        try {
            DB::beginTransaction();

            $models = ModelName::whereIn('id', $ids)->get();

            if ($models->isEmpty()) {
                return response()->json(
                    [
                        'message' => 'No matching resources found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404,
                );
            }

            $deletedNames = $models->pluck('name')->toArray();
            ModelName::whereIn('id', $ids)->delete();

            $this->logActivity('models_bulk_deleted', 'Deleted: ' . implode(', ', $deletedNames), [
                'ids' => $ids,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Bulk delete successful',
                'version' => 'v1',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500,
            );
        }
    }
}


=== .ai/email rules ===

<?php
/**
 * 🧠 AI GUIDELINE: Laravel Email Notifications (TailwindCSS)
 * ==================================================
 * Purpose:
 * - Standardized email sending for Laravel applications.
 * - Use TailwindCSS classes for styling.
 * - Error handling via try/catch with logging.
 * - Organize templates by entity/action.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Traits\LoggableTrait;
use Exception;

class EmailGuidelineController extends Controller
{
    use LoggableTrait;

    /**
     * Send email notification to user (Account Created)
     *
     * @param \App\Models\User $user
     * @param string|null $plainPassword
     */
    public function sendUserAccountCreatedEmail($user, $plainPassword = null)
    {
        if (!empty($user->email)) {
            try {
                Mail::send(
                    'emails.users.user-account-created',
                    [
                        'user' => $user,
                        'plainPassword' => $plainPassword,
                    ],
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Your Account Has Been Created');
                    },
                );
            } catch (Exception $e) {
                $this->logActivity('email_sending_failed', "Failed to send email to '{$user->email}'. Error: {$e->getMessage()}", ['user_id' => $user->id]);
            }
        }
    }
}
?>

<!-- resources/views/emails/users/user-account-created.blade.php -->


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans text-gray-800">

    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md overflow-hidden mt-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-center py-6">
            <h1 class="text-2xl font-bold">🏠 House of Plastics</h1>
            <p class="text-sm mt-1">Admin Dashboard Portal</p>
        </div>

        <!-- Main Content -->
        <div class="p-6">
            <h2 class="text-xl font-semibold text-center mb-4">Welcome, Valued User!</h2>
            <p class="mb-4 text-gray-700">
                Your admin account has been successfully created. You now have access to the dashboard to manage
                operations efficiently.
            </p>

            <!-- Account Details -->
            <div class="bg-gray-50 border-l-4 border-indigo-500 rounded p-4 mb-4">
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Full Name:</span>
                    <span class="font-mono text-gray-600">Valued User</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Email:</span>
                    <span class="font-mono text-gray-600">user@example.com</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Temporary Password:</span>
                    <span class="font-mono text-gray-600">temporary123</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Account Status:</span>
                    <span
                        class="px-2 py-1 bg-green-100 text-green-800 rounded">Active</span>
                </div>
            </div>

            <!-- Security Warning -->
            <div class="bg-yellow-100 border-l-4 border-yellow-400 p-4 mb-4 text-yellow-800">
                <strong>🛡️ Important:</strong> Please change your password immediately after first login. Do not share
                your credentials.
            </div>

            <!-- Dashboard Button -->
            <div class="text-center mb-4">
                <a href="#"
                    class="inline-block bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold py-2 px-6 rounded hover:opacity-90 transition">
                    🚀 Access Dashboard
                </a>
            </div>

            <!-- Features -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2">🎯 What You Can Do:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Manage inventory and product catalogs</li>
                    <li>Track orders and customer interactions</li>
                    <li>Generate reports and analytics</li>
                    <li>Coordinate with team members</li>
                    <li>Access real-time insights</li>
                    <li>Configure system settings</li>
                </ul>
            </div>

            <p class="text-gray-700 mt-4">If you encounter any issues, please contact our support team.</p>
        </div>

        <!-- Footer -->
        <div class="bg-gray-800 text-white text-center py-4 text-sm">
            © 2025 House of Plastics. Do not reply to this email.
        </div>
    </div>

</body>

</html>


=== .ai/homenest rules ===

<?php
/**
 * 🧠 AI GUIDELINE — HOMENEST API 🏡🛍️
 * ==================================================
 * Project: Homenest (Single-vendor e-commerce API)
 * Purpose: Define API behavior and best practices for backend development.
 *
 * 📂 Location:
 * .ai/guidelines/homenest.blade.php
 *
 * ✅ Scope:
 * - API-only, no frontend
 * - Guest & authenticated users
 * - Email notifications for guest users
 * - Single vendor, household items
 */
?>

## 🌐 API Structure
- Version all endpoints: `/api/v1/...`.
- Return **consistent JSON responses**:

**Success Example:**
```json
{
"message": "Resource retrieved successfully",
"data": {...},
"code": 200
}


=== .ai/routes rules ===

<?php
/**
 * 🧭 AI GUIDELINE: Laravel API Routes Structure
 * ============================================
 * This file defines the base structure and conventions for defining
 * API routes for RESTful controllers in Laravel.
 *
 * ✅ Every resource should:
 * - Use Route::resource() for standard CRUD endpoints
 * - Have a separate bulk destroy route (POST)
 * - Use proper naming conventions for endpoints
 * - Be grouped under API version prefix (e.g., v1)
 * - Support middleware (e.g., auth:sanctum)
 *
 * 📂 File:
 * routes/api.php
 *
 * 📌 Naming Convention:
 * - Resource route: `Route::resource('model-names', ModelNameController::class);`
 * - Bulk delete route: `Route::post('bulk-destroy-model-names', [ModelNameController::class, 'bulkDestroy']);`
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentVisitController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\ExampleController; // 📝 Example

Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        /**
         * 🧍 Agent Visits
         * ------------------------------------------------
         * GET    /agent-visits            → index
         * POST   /agent-visits            → store
         * GET    /agent-visits/{id}       → show
         * PUT    /agent-visits/{id}       → update
         * DELETE /agent-visits/{id}       → destroy
         * POST   /bulk-destroy-agent-visits → bulkDestroy
         */
        Route::resource('agent-visits', AgentVisitController::class);
        Route::post('bulk-destroy-agent-visits', [AgentVisitController::class, 'bulkDestroy']);

        /**
         * 📡 Channels
         * ------------------------------------------------
         * GET    /channels
         * POST   /channels
         * GET    /channels/{id}
         * PUT    /channels/{id}
         * DELETE /channels/{id}
         * POST   /bulk-destroy-channels
         */
        Route::resource('channels', ChannelController::class);
        Route::post('bulk-destroy-channels', [ChannelController::class, 'bulkDestroy']);

        /**
         * 📝 Example Resource (Template)
         * ------------------------------------------------
         * GET    /examples
         * POST   /examples
         * GET    /examples/{id}
         * PUT    /examples/{id}
         * DELETE /examples/{id}
         * POST   /bulk-destroy-examples
         */
        Route::resource('examples', ExampleController::class);
        Route::post('bulk-destroy-examples', [ExampleController::class, 'bulkDestroy']);
    });
</laravel-boost-guidelines>

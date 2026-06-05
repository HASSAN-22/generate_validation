
-----

# Generate validation 📝

A Laravel package to automatically generate validation rules from models.


## ✨ Features

- **Generate Rules Automatically**: Creates comprehensive validation rules for your models based on their database schema.
- **Separate Store & Update Rules**: Generates distinct rule sets for `store` (create) and `update` operations, handling nuances like `required` vs. `nullable` fields.
- **Ignore Specific Columns**: Skip certain columns when generating validation rules by passing them as an argument to the Artisan command.
- **Override with Custom Rules**: Define your own validation rules for specific models and columns in the configuration file, overriding the default auto-generated ones.


## 🚀 Installation

You can install the package via Composer:

```bash
composer require hasan-22/generate_validation
```

## 📖 Usage

### 1\. Generating a Form Request

* ####  First, create the migration.
* ####  Then, create the model.
* ####  Finally, run the Artisan command.


For example, we want to generate validation for the `Post` model.

First, create the migration and model using this command:

```bash
php artisan make:model Post -m
```
### This is our `Post` migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->unique();
            $table->text('content');
            $table->string('image');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};


```

### This is our `Post` model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    //
}

```

### And now run this command for generate the validations:

**Options explained:**
- `--namespace` : Set a custom namespace for the generated request class. Default is `App\Http\Requests`.
- `--name` : Set a custom name for the request class. Default is `{ModelName}Request`.
- `--model-path` : Set a custom path for the model class. Default is `App\Models\{ModelName}`.
- `--ignore` : Ignore specific columns when generating validation rules.
- `--type` : Specify which rules to generate (`store`, `update`, or `all`). Default is `all`.
- `--rules-only` : Output the generated rules to the console without saving to a file.
```

```bash 

# Generate all validations for the Post model
php artisan make:validation Post 

# To ignore some columns (e.g., title and content):
php artisan make:validation Post --ignore=title,content

# Outputs the generated validation rules without saving them to a file:
php artisan make:validation Post --rules-only

# Generates validation rules only for the specified type (store, update, or all). Default is all:
php artisan make:validation Post --type store

# Specify a custom namespace for the generated request class:
php artisan make:validation Post --namespace=App\Http\Requests\Admin

# Specify a custom name for the generated request class:
php artisan make:validation Post --name=CustomPostRequest

# Specify a custom model path if your model is not in the default location:
php artisan make:validation Post --model-path=App\Models\Admin\Post

# Combine options for advanced usage:
php artisan make:validation Post --ignore=title,content --type=update --namespace=App\Http\Requests\Admin --name=AdminPostRequest --model-path=App\Models\Admin\Post

# Display only the update rules for a model in a custom namespace, without saving to a file:
php artisan make:validation Post --type=update --rules-only --namespace=App\Http\Requests\Admin

```

**Note:** Note: The `id` column is always excluded from generated validation rules. 

**Note:** This will create a new file at `app/Http/Requests/PostRequest.php` containing the generated rules.

### This is the `PostRequest.php` file

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class PostRequest
 *
 * This Form Request class is used to validate incoming requests for the
 * Post model. It separates validation rules for "store" (create)
 * and "update" operations, ensuring data integrity.
 *
 * @package App\Http\Requests
 */
class PostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * In most cases, this should return true if the user is authenticated.
     * You can add custom authorization logic here if needed.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * This method dynamically returns a different set of rules based on the
     * HTTP method (POST for store, PUT/PATCH for update).
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->isMethod('POST') ? $this->forStore() : $this->forUpdate();
    }

    /**
     * Get the validation rules for a "store" (create) request.
     *
     * These rules are applied when creating a new Post resource.
     *
     * @return array
     */
    private function forStore(): array
    {
        return [
            // Generated rules for a new resource will be injected here.
            'user_id' => ['required', 'integer', 'min:0', 'max:18446744073709551615', 'exists:users,id'],
            'title' => ['unique:posts,title', 'required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:65535'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'status' => ['required', 'in:draft,published,archived']
        ];
    }

    /**
     * Get the validation rules for an "update" request.
     *
     * These rules are applied when updating an existing Post resource.
     *
     * @return array
     */
    public function forUpdate(): array
    {
        return [
            // Generated rules for updating a resource will be injected here.
            'user_id' => ['required', 'integer', 'min:0', 'max:18446744073709551615', 'exists:users,id'],
            'title' => ['unique:posts,title,id', 'required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'status' => ['required', 'in:draft,published,archived']
        ];
    }
}

```

**Note:** Please review the created validation once to ensure that nothing is missing or excessive, and fix it if necessary.

### 2\. Using the Form Request

You can now use this Form Request in your controller methods to automatically validate incoming requests.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Post;

class PostController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  PostRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PostRequest $request)
    {
        Post::create($request->all());

        return response()->json(['message' => 'Post created successfully!']);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  PostRequest  $request
     * @param  Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(PostRequest $request, Post $post)
    {
        $post->update($request->all());

        return response()->json(['message' => 'Post updated successfully!']);
    }
}
```
## ⚙️ Configuration (Optional)


### 🎯 Custom Rules via Config

In addition to automatically generating rules based on your model's database schema, you can define **custom validation rules** for specific models and columns in the configuration file.  

This allows you to override the auto-generated rules and fully control the validation logic.

### 1. Publish the Config File

If you haven't already published the config file, run:

```bash
php artisan vendor:publish --tag=generate_validation
```

This will create a file at: `config/generate_validation.php`

### 2. Define Your Custom Rules
```php
return [
    'custom_rules' => [
        // Model => [ column => rules... ]
        'Post' => [
            'title' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'min:50'],
        ],
        'User' => [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ],
    ],
];

```
### 3. How It Works

If a column has a custom rule defined in the config, the package will use that instead of generating the rule automatically.

If a column is not defined in custom_rules, the default auto-generation logic will be applied.

This gives you the flexibility to handle special cases without losing the convenience of automatic generation.

___


### 🖼 Blob & File Rules

You can customize the default rules for specific strategies. For example, to change the default `max` size for images, you can use the static methods of the BlobRuleGenerator class. 

In your `ServiceProvider`'s boot method:

```php
use GenerateValidation\Strategies\BlobRuleGenerator;

// Set the maximum file size (in kilobytes)
BlobRuleGenerator::setMaxSize(5120); // 5MB

// This adds to the list of possible names used for images.
// or example, if you have a column that stores an image and its name is `my_thumbnail`, you need to add this name so it can be recognized. 
BlobRuleGenerator::addImageName('my_thumbnail');

// Add additional MIME types to the existing list
BlobRuleGenerator::setMimes(['webp', 'avif'], 'merge');

// Or replace the MIME types entirely
BlobRuleGenerator::setMimes(['webp', 'avif'], 'replace');
```

### 📅 Custom Date Format for Date, Datetime, and Timestamp

You can now customize the date format used for validation rules in the following strategies:
- `DateRuleGenerator`
- `DatetimeRuleGenerator`
- `TimestampRuleGenerator`

This allows you to set your preferred date format for each type, which will be used in the generated `date_format` validation rule.

In your `ServiceProvider`'s boot method:

**Usage Example:**

```php
use GenerateValidation\Strategies\DateRuleGenerator;
use GenerateValidation\Strategies\DatetimeRuleGenerator;
use GenerateValidation\Strategies\TimestampRuleGenerator;

// Set custom date format for Date fields
DateRuleGenerator::setDateFormat('d/m/Y'); // date_format:d/m/Y

// Set custom date format for Timestamp fields
TimestampRuleGenerator::setDateFormat('Y-m-d'); // date_format:Y-m-d

// Set custom date format for Datetime fields
DatetimeRuleGenerator::setDateFormat('d-m-Y H:i:s'); // date_format:d-m-Y H:i:s

// set custom time format for time fields
DatetimeRuleGenerator::setTimeFormat('H:i:s'); // date_format:H:i:s

// set custom year format for year fields
DatetimeRuleGenerator::setYearFormat('2000', '2030'); // between:2000,2030
```

This feature gives you full control over how date and time values are validated, making it easy to match your application's requirements or localization needs.


## 💖 Support the Project

If you like this project and want to support its development, you can donate here: [Donate via NOWPayments](https://nowpayments.io/donation/hassan)

## 🤝 Contributing

We welcome contributions\! If you find a bug or have a suggestion for a new feature, please open an issue or submit a pull request on GitHub.

## 📄 License

`generate_validation` is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.

-----

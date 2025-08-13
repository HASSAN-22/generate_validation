
-----

# generate\_validation 📝

A Laravel package to automatically generate validation rules from models.

## ✨ Features

  - **Automatic Rule Generation**: Automatically creates comprehensive validation rules for your models based on their database schema.
  - **Store & Update Separation**: Generates separate rule sets for `store` (create) and `update` operations, handling nuances like `required` vs. `nullable` fields.
  - **Customizable**: The generated Form Request classes are fully editable, allowing you to easily add or modify rules as needed.
  - **Extensible**: The underlying rule generation logic uses a strategy pattern, making it easy to add support for new column types or custom validation rules.

## 🚀 Installation

You can install the package via Composer:

```bash
composer require hasan-22/generate_validation
```

## 📖 Usage

### 1\. Generating a Form Request

To generate a new Form Request class for your model, use the `make:validation` Artisan command.

```bash
php artisan make:validation Post
```

This will create a new file at `app/Http/Requests/PostRequest.php` containing the generated rules.

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
        $data = $request->validated();

        Post::create($data);

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
        $data = $request->validated();
        
        $post->update($data);

        return response()->json(['message' => 'Post updated successfully!']);
    }
}
```

## ⚙️ Configuration (Optional)

You can customize the default rules for specific strategies. For example, to change the default `max` size for images, you can use the static methods on the `BlobRuleGenerator` class.

```php
// In a service provider or bootstrap file
use ModelValidator\Strategies\BlobRuleGenerator;

BlobRuleGenerator::setMaxSize(5120); // 5MB
BlobRuleGenerator::setMimes(['webp', 'avif'], 'merge');
// Or for replace the mimes
BlobRuleGenerator::setMimes(['webp', 'avif'], 'replace');
```

## 🤝 Contributing

We welcome contributions\! If you find a bug or have a suggestion for a new feature, please open an issue or submit a pull request on GitHub.

## 📄 License

`generate_validation` is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.

-----

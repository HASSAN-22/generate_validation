
-----

# generate\_validation 📝

A Laravel package to automatically generate validation rules from models.

## ✨ Features

  - **Automatic Rule Generation**: Automatically creates comprehensive validation rules for your models based on their database schema.
  - **Store & Update Separation**: Generates separate rule sets for `store` (create) and `update` operations, handling nuances like `required` vs. `nullable` fields.

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

Please review the created validation once to ensure that nothing is missing or excessive, and fix it if necessary.

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
        $data = $request->validated(); // This line is not necessary

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
        $data = $request->validated(); // This line is not necessary
        
        $post->update($data);

        return response()->json(['message' => 'Post updated successfully!']);
    }
}
```

## ⚙️ Configuration (Optional)

You can customize the default rules for specific strategies. For example, to change the default `max` size for images, you can use the static methods of the BlobRuleGenerator class.

In your `ServiceProvider`'s boot method:

```php
use GenerateValidation\Strategies\BlobRuleGenerator;

// Set the maximum file size (in kilobytes)
BlobRuleGenerator::setMaxSize(5120); // 5MB

// This adds to the list of possible names used for images.
// or example, if you have a column that stores an image and its name is `my_image`, you need to add this name so it can be recognized. 
BlobRuleGenerator::addImageName('my_image');

// Add additional MIME types to the existing list
BlobRuleGenerator::setMimes(['webp', 'avif'], 'merge');

// Or replace the MIME types entirely
BlobRuleGenerator::setMimes(['webp', 'avif'], 'replace');
```
## 💖 Support the Project

If you like this project and want to support its development, you can donate here: [Donate via NOWPayments](https://nowpayments.io/donation/hassan)

## 🤝 Contributing

We welcome contributions\! If you find a bug or have a suggestion for a new feature, please open an issue or submit a pull request on GitHub.

## 📄 License

`generate_validation` is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.

-----

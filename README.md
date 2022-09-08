# Laravel Number Pool Trait for Model ascending unique increments

Create a shared Number Pool for each of business number ranges to use native MySQL / MariaDB ``FOR UPDATE`` atomic locks if you run on a MySQL Master/Master Replication or on galera cluster.

If you're running on replication you're primary auto increments are probably not reliable for unique ascending numbers. 
With this Eloquent trait your able to generate ascending unique numbers while using InnoDB's ``FOR UPDATE`` atomic lock.

**Example Invoice Table:**

| id | type    | number |
|----|---------|--------|
| 1  | invoice | 1000   |
| 3  | invoice | 1001   |
| 6  | invoice | 1002   |

## Installation
This package can be installed through composer:

``` shell
composer require linushstge/laravel.trait.number-pool
```

After installation, you have to create a new migration with ``artisan:make migration`` for your number pools.

``` shell
php artisan make:migration CreateNumberPool
```

Example:

``` php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_pool', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->bigInteger('number');
            $table->string('description')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_pool');
    }
};
```

## Usage

Add the ```NumberPool``` trait to one of your existing model and implement 
the abstract methods ``numberPoolKey`` and ``numberPoolAttribute`` to set up your pool and local
model attribute where you wish to save your ascending unique increment.

``` php
<?php

namespace App\Models\Account;

use linushstge\NumberPool\Traits\NumberPool;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use NumberPool;

    public function numberPoolKey(): string
    {
        return 'invoice.number_internal';
    }

    public function numberPoolAttribute(): string
    {
        return 'number_internal';
    }
}
```

## Custom increment step size

If you wish to specify the step size you can implement the public method ```numberPoolStepSize``` 
to dynamically adjust the step size for any new generated increments. You also can use ``rand`` to implement
random steps between your numbers.

``` php
public function numberPoolStepSize(): int
{
    return rand(10, 50);
}
```
Please ensure a positive ```integer``` for your step size. Otherwise, the ``NumberPoolException`` will be thrown.

---

## Usage of static Eloquent event hooks

As you may already know, laravel supports anonymous static booted events to hook inside a creating or created event.
You can use them to build custom logic with your newly unique number pool integer. 

For example:

``` php
<?php

class Invoice
{
    // [..]

    protected static function booted()
    {
        static::creating(function ($invoice) {
        
            // your unique incremented number from your number pool is already available before the 
            // transaction has been committed.
            
            $uniqueNumber = $invoice->number;
        });
    }
}
```

---
## License
The MIT License (MIT). Refer to the [License](https://github.com/linushstge/laravel.trait.number-pool/blob/main/LICENSE) for more information.
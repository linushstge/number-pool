# Eloquent Trait for unique Model ascending numbers

Create a shared Number Pool for each of business number ranges to use native 
MySQL / MariaDB ``FOR UPDATE`` atomic locks if you run on a MySQL Master/Master 
Replication or on galera cluster or if you have multiple message queue workers 
which are consuming the same jobs.

If you're running on replication you're primary auto increments are probably not 
reliable for unique ascending numbers. With this Eloquent trait your able to generate 
ascending unique numbers while using InnoDB's native ``FOR UPDATE`` row lock.

**Example Invoice Table:**

| id | type    | number |
|----|---------|--------|
| 1  | invoice | 1000   |
| 3  | invoice | 1001   |
| 6  | invoice | 1002   |

With Master-Master Replication or Galera cluster you'r primary auto increment is not reliable for any 
ascending numbers.

## Installation
This package can be installed through composer:

``` shell
composer require linushstge/number-pool
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
Number Pools stores a string identifier key and the **last** used number of any pool your application is using.
With the key you are able to use the sane number pools in multiple eloquent models.

## Usage

Create a new number_pool to set up your initial base ``number`` for your first pool.

``` php

$numberPool = new NumberPool([
   'key' => 'invoice.number',
   'number => 999,
   'description' => 'Pool for generating unique ascending invoice numbers'
]);
$numberPool->save();
```

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
        return 'invoice.number';
    }

    public function numberPoolAttribute(): string
    {
        return 'number';
    }
}
```

On each Model creating event this trait will perform a native InnoDB ```FOR UPDATE``` lock inside a
dedicated transaction to ensure uniqueness for the new generated number.

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
        
            // your unique incremented number from your number pool is already 
            //available before the transaction has been committed.
            
            $uniqueNumber = $invoice->number;
        });
    }
}
```

---
## FAQ

### Is this package is compatible with Laravel Horizon?
Yes, you can use horizon, your own supervisor process monitor or native systemd services. 
By InnoDB's technology the native ROW READ LOCK is guaranteed.

### Do I need InnoDB engine for all tables?
No, this package only requires InnoDB for your ```number_pool``` table.

### Do I need redis?
No, redis is not required, but preferred for your message queue, especially if your consuming
the same jobs on multiple workers.

---
## License
The MIT License (MIT). Refer to the [License](https://github.com/linushstge/laravel.trait.number-pool/blob/main/LICENSE) for more information.
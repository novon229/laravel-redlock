# Laravel RedLock

A Laravel-friendly implementation of the Redis [Redlock algorithm for distributed lock management](https://redis.io/topics/distlock).

### Acknowledgements

This library was originally built by LibiChai based on the RedLock algorithm developed by antirez. The library was reworked by the team at That's Us, Inc. This project has been forked and maintained by Dealer Inspire Inc.

### Installation

1. `composer require dealerinspire/laravel-redlock`
2. The `RedLockServiceProvider` should be Auto-discovered by Laravel. If not, add `DealerInspire\RedLock\RedLockServiceProvider::class,` to the `providers` array in `config/app.php`
3. Enjoy!

### Compatibility

| Laravel Version | RedLock Version |
| --------------- | --------------- |
| 5.x             | `^4.0`          |
| 6.x             | `^6.0`          |
| 7.x             | `^7.0`          |
| 8.x             | `^8.0`          |
| 9.x             | `^9.0`          |

### It's Simple!

Set a lock on any scalar. If the `lock()` method returns false, you did not acquire the lock.

Store results of the `lock()` method. Use this value to release the lock with `unlock()`.

### Example

This example sets a lock on the key "1" with a 3 second expiration time.

If it acquired the lock, it does some work and releases the lock.

```php
use DealerInspire\RedLock\Facades\RedLock;

$product_id = 1;

$lock_object = RedLock::lock($product_id, 3000);

if ($lock_object) {
    $order->submit($product_id);

    RedLock::unlock($lock_token);
    // or
    $lock_object->unlock();
}
```

### Refresh

Use `refreshLock()` to reacquire and extend the time of your lock.

```php
use DealerInspire\RedLock\Facades\RedLock;

$product_ids = [1, 2, 3, 5, 7];

$lock_object = RedLock::lock('order-submitter', 3000);

while ($product_ids && $lock_object) {
    $order->submit(array_shift($product_ids));

    $lock_object = RedLock::refreshLock($lock_object);
}

RedLock::unlock($lock_object);
// or
$lock_object->unlock();
```

### Even Easier with Closures

Use `runLocked()` for nicer syntax. The method returns the results of the closure, or else false if the lock could not be acquired.

```php
use DealerInspire\RedLock\Facades\RedLock;

$product_id = 1;

$result = RedLock::runLocked($product_id, 3000, function () use ($order, $product_id) {
    $order->submit($product_id);
    return true;
});

echo $result ? 'Worked!' : 'Lock not acquired.';
```

### Refresh with Closures

You can easily refresh your tokens when using closures. The first parameter to your closure is `$refresh`. Simply call it when you want to refresh. If the lock cannot be refreshed, `$refresh()` will break out of the closure.

```php
use DealerInspire\RedLock\Facades\RedLock;

$product_ids = [1, 2, 3, 5, 7];

$result = RedLock::runLocked($product_id, 3000, function ($refresh) use ($order, $product_ids) {
    foreach ($product_ids as $product_id) {
        $refresh();
        $order->submit($product_id);
    }
    return true;
});

echo $result ? 'Worked!' : 'Lock lost or never acquired.';
```

### Lock Queue Jobs Easily

If you're running jobs on a Laravel queue, you may want to avoid queuing up the same job more than once at a time.

The `DealerInspire\RedLock\Traits\QueueWithoutOverlap` trait provides this functionality with very few changes to your job. Usually only two changes are necessary.

1. `use DealerInspire\RedLock\Traits\QueueWithoutOverlap` as a trait
2. Change the `handle()` method to `handleSync()`

```php
use DealerInspire\RedLock\Traits\QueueWithoutOverlap;

class OrderProductJob
{
    use QueueWithoutOverlap;

    public function __construct($order, $product_id)
    {
        $this->order = $order;
        $this->product_id = $product_id;
    }

    public function handleSync()
    {
        $this->order->submit($this->product_id);
    }
}
```

Sometimes it's also necessary to specify a `getLockKey()` method. This method must return the string that needs to be locked.

This is typically unnecessary because the lock key can be generated automatically. But if the job's data is not easy to stringify, you must define the `getLockKey()` method.

This trait also provides a refresh method called `refreshLock()`. If `refreshLock()` is unable to refresh the lock, an exception is thrown and the job fails.

Finally, you can change the lock time-to-live from the default 300 seconds to another
value using the `$lock_time` property.

```php
use DealerInspire\RedLock\Traits\QueueWithoutOverlap;

class OrderProductsJob
{
    use QueueWithoutOverlap;

    protected $lock_time = 600; // 10 minutes in seconds

    public function __construct($order, array $product_ids)
    {
        $this->order = $order;
        $this->product_ids = $product_ids;
    }

    // We need to define getLockKey() because $product_ids is an array and the
    // automatic key generator can't deal with arrays.
    protected function getLockKey()
    {
        $product_ids = implode(',', $this->product_ids);
        return "OrderProductsJob:{$this->order->id}:{$product_ids}";
    }

    public function handleSync()
    {
        foreach ($this->product_ids as $product_id) {
            $this->refreshLock();
            $this->order->submit($product_id);
        }
    }
}
```

### Contribution

If you find a bug or want to contribute to the code or documentation, you can help by submitting an [issue](https://github.com/dealerinspire/laravel-redlock/issues) or a [pull request](https://github.com/dealerinspire/laravel-redlock/pulls).

## Development

Development is done through simple use of the official PHP and Composer images.

# Getting Started

1. Pull down the repo.
2. Install dependencies with `bin/composer install`
3. That's it!

# Run tests

Tests can be ran with `bin/php vendor/bin/phpunit`.

### License

[MIT](http://opensource.org/licenses/MIT)

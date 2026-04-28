<?php

declare(strict_types=1);

namespace Eliseekn\LaravelMetrics\Tests\Models;

use Eliseekn\LaravelMetrics\HasMetrics;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasMetrics;

    protected $table = 'orders';

    protected $fillable = [
        'status',
        'amount',
        'created_at',
        'updated_at',
    ];
}

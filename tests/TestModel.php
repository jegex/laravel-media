<?php

namespace Jegex\Media\Tests;

use Illuminate\Database\Eloquent\Model;
use Jegex\Media\MediaCollections\Models\Concerns\HasMedia;

class TestModel extends Model
{
    use HasMedia;

    protected $table = 'test_models';

    protected $guarded = [];
}

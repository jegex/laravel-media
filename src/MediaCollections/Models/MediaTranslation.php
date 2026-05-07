<?php

namespace Jegex\Media\MediaCollections\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaTranslation extends Model
{
    protected $table = 'media_translations';

    protected $guarded = [];

    public $timestamps = false;

    public function media(): BelongsTo
    {
        return $this->belongsTo(config('media.media_model'));
    }
}

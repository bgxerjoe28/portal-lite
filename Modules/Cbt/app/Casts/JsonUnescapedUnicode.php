<?php

namespace Modules\Cbt\Casts;

// Re-export the shared app-level cast so existing imports in Cbt models still resolve.
class JsonUnescapedUnicode extends \App\Casts\JsonUnescapedUnicode
{
}

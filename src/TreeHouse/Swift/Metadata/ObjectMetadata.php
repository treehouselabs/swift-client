<?php

namespace TreeHouse\Swift\Metadata;

class ObjectMetadata extends Metadata
{
    public function getPrefix()
    {
        return 'X-Object-Meta-';
    }
}

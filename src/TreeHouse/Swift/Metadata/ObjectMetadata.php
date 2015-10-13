<?php

namespace TreeHouse\Swift\Metadata;

class ObjectMetadata extends Metadata
{
    /**
     * @inheritdoc
     */
    public function getPrefix()
    {
        return 'X-Object-Meta-';
    }
}

<?php

namespace TreeHouse\Swift\Metadata;

class ContainerMetadata extends Metadata
{
    /**
     * @inheritdoc
     */
    public function getPrefix()
    {
        return 'X-Container-Meta-';
    }
}

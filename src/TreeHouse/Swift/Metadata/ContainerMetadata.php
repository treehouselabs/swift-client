<?php

namespace TreeHouse\Swift\Metadata;

class ContainerMetadata extends Metadata
{
    public function getPrefix()
    {
        return 'X-Container-Meta-';
    }
}

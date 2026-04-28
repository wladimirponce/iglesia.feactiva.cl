<?php

declare(strict_types=1);

final class OntologyRelation
{
    public function __construct(
        public readonly string $fromObject,
        public readonly string $relation,
        public readonly string $toObject
    ) {
    }

    public function toArray(): array
    {
        return [
            'from' => $this->fromObject,
            'relation' => $this->relation,
            'to' => $this->toObject,
        ];
    }
}

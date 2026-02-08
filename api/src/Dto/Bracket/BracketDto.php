<?php

declare(strict_types=1);

namespace App\Dto\Bracket;

class BracketDto
{
    /** @var StageDto[] */
    public array $stages = [];

    public function addStage(StageDto $stage): void
    {
        $this->stages[] = $stage;
    }

    /**
     * @return StageDto[]
     */
    public function getStages(): array
    {
        return $this->stages;
    }
}

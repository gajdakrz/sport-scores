<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use DateTimeImmutable;

class GameResultHandler
{
    public function handle(Game $game, User $user): void
    {
        foreach ($game->getGameResults() as $gameResult) {
            if (!$gameResult->getId()) {
                $gameResult->setCreatedBy($user);
            }

            $gameResult->setModifiedBy($user);
        }
    }
}

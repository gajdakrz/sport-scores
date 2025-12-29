<?php

namespace App\Dto;

final class MatchStats
{
    private int $total = 0;
    private int $wins = 0;
    private int $losses = 0;
    private int $draws = 0;
    private int $unknowns = 0;
    private int $points = 0;

    public function addWin(): void
    {
        $this->total++;
        $this->wins++;
        $this->points += 3;
    }

    public function addLoss(): void
    {
        $this->total++;
        $this->losses++;
    }

    public function addDraw(): void
    {
        $this->total++;
        $this->draws++;
        $this->points++;
    }

    public function addUnknown(): void
    {
        $this->total++;
        $this->unknowns++;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function wins(): int
    {
        return $this->wins;
    }

    public function losses(): int
    {
        return $this->losses;
    }

    public function draws(): int
    {
        return $this->draws;
    }

    public function unknowns(): int
    {
        return $this->unknowns;
    }

    public function points(): int
    {
        return $this->points;
    }

    public function winRate(): float
    {
        return $this->total === 0
            ? 0.0
            : round(($this->wins / $this->total) * 100, 2);
    }
}

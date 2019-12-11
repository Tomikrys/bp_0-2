<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SettingsRepository")
 */
class Settings
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="array")
     */
    private $days = [];

    /**
     * @ORM\Column(type="array")
     */
    private $meals = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDays(): ?array
    {
        return $this->days;
    }

    public function setDays(array $days): self
    {
        $this->days = $days;

        return $this;
    }

    public function getMeals(): ?array
    {
        return $this->meals;
    }

    public function setMeals(array $meals): self
    {
        $this->meals = $meals;

        return $this;
    }
}

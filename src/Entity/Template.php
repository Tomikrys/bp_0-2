<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TemplateRepository")
 */
class Template
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $path;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getDisplayPath($url = null): ?string {
        $path = "~/";
        if ($url) {
            return $path . $url;
        } else {
            return $path . $this->getPath();
        }
    }

    public function getRealPath($url = null): ?string {
        $path = "../public/words/";
        if ($url) {
            return $path . $url;
        } else {
            return $path . $this->getPath();
        }
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }
}

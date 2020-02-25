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

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="templates")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

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

    public function getRealPath($url = null, $user = null): ?string {
        if ($user == null) {
            $user = $this->getUser();
        }
        $user = $user->getCleanUsername();
        $path = "https://menickajednodusecz.s3.amazonaws.com/words/";
        //$path = "../public/words/";
        if ($url) {
            return $path . $user . '/' . $url;
        } else {
            return $path . $user . '/' . $this->getPath();
        }
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\MinistreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MinistreRepository::class)]
class Ministre extends User
{
    #[ORM\Column(length: 191)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }
}
<?php

namespace App\Entity;

use App\Repository\UniversiteRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\User;
use App\Entity\Etablissement;

#[ORM\Entity(repositoryClass: UniversiteRepository::class)]
class Universite extends User
{
    

    #[ORM\Column(length: 255)]
    private ?string $nom = null;


    #[ORM\OneToMany(mappedBy: 'groupe', targetEntity: Etablissement::class, orphanRemoval: true)]
    private Collection $etablissements;

    public function __construct()
    {
    $this->etablissements = new ArrayCollection();
    }

    public function getEtablissements(): Collection
    {
        return $this->etablissements;
    }
   

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

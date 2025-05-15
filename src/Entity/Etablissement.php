<?php

namespace App\Entity;

use App\Repository\EtablissementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementRepository::class)]
class Etablissement extends User
{
    public const ETYPE_PUBLIC = 'Publique';
    public const ETYPE_PRIVATE = 'Privée';
    public const EaTYPES = [
        self::ETYPE_PUBLIC,
        self::ETYPE_PRIVATE
    ];

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $etype = null;  // Changed from Etype to etype for consistency

    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;  // Made nullable as it might not be required immediately

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteweb = null;

    #[ORM\ManyToOne(targetEntity: Universite::class, inversedBy: 'etablissements')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Universite $groupe = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getEtype(): ?string
    {
        return $this->etype;
    }

public function setEtype(string $etype): static
{
    if (!in_array($etype, self::EaTYPES)) {
        throw new \InvalidArgumentException(sprintf(
            "Invalid etype. Expected one of: %s",
            implode(', ', self::EaTYPES)
        ));
    }
    
    $this->etype = $etype;
    return $this;
}

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getSiteweb(): ?string
    {
        return $this->siteweb;
    }

    public function setSiteweb(?string $siteweb): static
    {
        $this->siteweb = $siteweb;
        return $this;
    }

    public function getGroupe(): ?Universite
    {
        return $this->groupe;
    }

    public function setGroupe(?Universite $groupe): static
    {
        $this->groupe = $groupe;
        return $this;
    }
}
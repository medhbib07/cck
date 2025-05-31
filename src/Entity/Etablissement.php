<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\User;
use App\Repository\EtablissementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtablissementRepository::class)]
class Etablissement extends User
{
    public const ETYPE_PUBLIC = 'Publique';
    public const ETYPE_PRIVATE = 'Privé';
    public const EaTYPES = [
        self::ETYPE_PUBLIC,
        self::ETYPE_PRIVATE
    ];

    #[ORM\Column(length: 191)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $etype = null;

    #[ORM\Column(length: 191)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $code_postal = null;

    #[ORM\Column(length: 100)]
    private ?string $ville = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $capacite = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $siteweb = null;

    #[ORM\ManyToOne(targetEntity: Universite::class, inversedBy: 'etablissements')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Universite $groupe = null;

    #[ORM\OneToMany(mappedBy: 'etablissement', targetEntity: Etudiant::class)]
    private ?Collection $etudiants = null;

    public function __construct()
{
    $this->etudiants = new ArrayCollection();
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->code_postal;
    }

    public function setCodePostal(?string $code_postal): static
    {
        $this->code_postal = $code_postal;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(?\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;
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
    public function getEtudiants(): Collection
{
    return $this->etudiants;
}
}   
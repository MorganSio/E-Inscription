<?php

namespace App\Entity;

use App\Repository\RepresentantLegalRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use phpDocumentor\Reflection\Types\Nullable;

#[ORM\Entity(repositoryClass: RepresentantLegalRepository::class)]
class RepresentantLegal extends Humain
{
    #[ORM\Column(length: 15, nullable: true)]
    private ?string $telephone_fixe = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $telephone_pro = null;

    #[ORM\Column(nullable: true)]
    private ?bool $sms_send = null;

    #[ORM\Column(nullable: true)]
    private ?bool $com_addr_asso = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $poste = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom_employeur = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $adresse_employeur = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $lien_eleve;

    #[ORM\ManyToOne]
    private InfoEleve $infoEleve;
    /**
    * @var Collection<int, InfoEleve>
    */
    #[ORM\OneToMany(targetEntity: InfoEleve::class, mappedBy: 'responsable_un')]
    private Collection $infoEleves;

    public function __construct()
    {
        parent::__construct(); // Si la classe Humain a un constructeur
        $this->infoEleves = new ArrayCollection();
    }

    /**
     * @return Collection<int, InfoEleve>
     */
    public function getInfoEleves(): Collection
    {
        return $this->infoEleves;
    }

    public function addInfoEleve(InfoEleve $infoEleve): static
    {
        if (!$this->infoEleves->contains($infoEleve)) {
            $this->infoEleves->add($infoEleve);
            $infoEleve->setResponsableUn($this);
        }

        return $this;
    }

    public function removeInfoEleve(InfoEleve $infoEleve): static
    {
        if ($this->infoEleves->removeElement($infoEleve)) {
            if ($infoEleve->getResponsableUn() === $this) {
                $infoEleve->setResponsableUn(null);
            }
        }

        return $this;
    }

    public function getTelephoneFixe(): ?string
    {
        return $this->telephone_fixe;
    }

    public function setTelephoneFixe(?string $telephone_fixe): static
    {
        $this->telephone_fixe = $telephone_fixe;

        return $this;
    }

    public function getTelephonePro(): ?string
    {
        return $this->telephone_pro;
    }

    public function setTelephonePro(?string $telephone_pro): static
    {
        $this->telephone_pro = $telephone_pro;

        return $this;
    }

    public function getSmsSend(): ?bool
    {
        return $this->sms_send;
    }

    public function setSmsSend(?bool $sms_send): static
    {
        $this->sms_send = $sms_send;

        return $this;
    }

    public function getComAddrAsso(): ?bool
    {
        return $this->com_addr_asso;
    }

    public function setComAddrAsso(?bool $com_addr_asso): static
    {
        $this->com_addr_asso = $com_addr_asso;

        return $this;
    }


    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(?string $poste): static
    {
        $this->poste = $poste;

        return $this;
    }

    public function getNomEmployeur(): ?string
    {
        return $this->nom_employeur;
    }

    public function setNomEmployeur(?string $nom_employeur): static
    {
        $this->nom_employeur = $nom_employeur;

        return $this;
    }

    public function getAdresseEmployeur(): ?string
    {
        return $this->adresse_employeur;
    }

    public function setAdresseEmployeur(?string $adresse_employeur): static
    {
        $this->adresse_employeur = $adresse_employeur;

        return $this;
    }

    public function getLienEleve(): ?string
    {
        return $this->lien_eleve;
    }

    public function setLienEleve(?string $new_lien_eleve): static
    {
        $this->lien_eleve = $new_lien_eleve;

        return $this;
    }

    public function setInfoEleve(InfoEleve $eleve)
    {
        $this->infoEleve = $eleve;
    }
}

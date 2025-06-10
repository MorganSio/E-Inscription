<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'inscriptions')]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    // Informations personnelles de l'élève
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    private ?string $prenom = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    private ?string $lieuNaissance = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    #[Assert\Choice(choices: ['M', 'F'], groups: ['flow_infos_eleve'])]
    private ?string $sexe = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    private ?string $adresse = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    private ?string $codePostal = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    private ?string $ville = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_infos_eleve'])]
    private ?string $telephone = null;

    // Représentant légal 1
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_rep_legal1'])]
    private ?string $repLegal1Nom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_rep_legal1'])]
    private ?string $repLegal1Prenom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Email(groups: ['flow_rep_legal1'])]
    #[Assert\NotBlank(groups: ['flow_rep_legal1'])]
    private ?string $repLegal1Email = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_rep_legal1'])]
    private ?string $repLegal1Telephone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_rep_legal1'])]
    private ?string $repLegal1Lien = null;

    // Représentant légal 2 (optionnel)
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $repLegal2Nom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $repLegal2Prenom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Email(groups: ['flow_rep_legal2'])]
    private ?string $repLegal2Email = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $repLegal2Telephone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $repLegal2Lien = null;

    // Scolarité précédente
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_scolarite'])]
    private ?string $etablissementPrecedent = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_scolarite'])]
    private ?string $classePrecedente = null;

    #[ORM\Column(type: 'string', length: 4, nullable: true)]
    #[Assert\NotBlank(groups: ['flow_scolarite'])]
    private ?string $anneeScolairePrecedente = null;

    // Documents
    #[ORM\Column(type: 'boolean')]
    private bool $documentsCertificat = false;

    #[ORM\Column(type: 'boolean')]
    private bool $documentsPhoto = false;

    #[ORM\Column(type: 'boolean')]
    private bool $documentsBulletins = false;

    #[ORM\Column(type: 'boolean')]
    private bool $documentsAssurance = false;

    // Statut
    #[ORM\Column(type: 'boolean')]
    private bool $isComplete = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(?string $lieuNaissance): self
    {
        $this->lieuNaissance = $lieuNaissance;
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    // Représentant légal 1
    public function getRepLegal1Nom(): ?string
    {
        return $this->repLegal1Nom;
    }

    public function setRepLegal1Nom(?string $repLegal1Nom): self
    {
        $this->repLegal1Nom = $repLegal1Nom;
        return $this;
    }

    public function getRepLegal1Prenom(): ?string
    {
        return $this->repLegal1Prenom;
    }

    public function setRepLegal1Prenom(?string $repLegal1Prenom): self
    {
        $this->repLegal1Prenom = $repLegal1Prenom;
        return $this;
    }

    public function getRepLegal1Email(): ?string
    {
        return $this->repLegal1Email;
    }

    public function setRepLegal1Email(?string $repLegal1Email): self
    {
        $this->repLegal1Email = $repLegal1Email;
        return $this;
    }

    public function getRepLegal1Telephone(): ?string
    {
        return $this->repLegal1Telephone;
    }

    public function setRepLegal1Telephone(?string $repLegal1Telephone): self
    {
        $this->repLegal1Telephone = $repLegal1Telephone;
        return $this;
    }

    public function getRepLegal1Lien(): ?string
    {
        return $this->repLegal1Lien;
    }

    public function setRepLegal1Lien(?string $repLegal1Lien): self
    {
        $this->repLegal1Lien = $repLegal1Lien;
        return $this;
    }

    // Représentant légal 2
    public function getRepLegal2Nom(): ?string
    {
        return $this->repLegal2Nom;
    }

    public function setRepLegal2Nom(?string $repLegal2Nom): self
    {
        $this->repLegal2Nom = $repLegal2Nom;
        return $this;
    }

    public function getRepLegal2Prenom(): ?string
    {
        return $this->repLegal2Prenom;
    }

    public function setRepLegal2Prenom(?string $repLegal2Prenom): self
    {
        $this->repLegal2Prenom = $repLegal2Prenom;
        return $this;
    }

    public function getRepLegal2Email(): ?string
    {
        return $this->repLegal2Email;
    }

    public function setRepLegal2Email(?string $repLegal2Email): self
    {
        $this->repLegal2Email = $repLegal2Email;
        return $this;
    }

    public function getRepLegal2Telephone(): ?string
    {
        return $this->repLegal2Telephone;
    }

    public function setRepLegal2Telephone(?string $repLegal2Telephone): self
    {
        $this->repLegal2Telephone = $repLegal2Telephone;
        return $this;
    }

    public function getRepLegal2Lien(): ?string
    {
        return $this->repLegal2Lien;
    }

    public function setRepLegal2Lien(?string $repLegal2Lien): self
    {
        $this->repLegal2Lien = $repLegal2Lien;
        return $this;
    }

    // Scolarité
    public function getEtablissementPrecedent(): ?string
    {
        return $this->etablissementPrecedent;
    }

    public function setEtablissementPrecedent(?string $etablissementPrecedent): self
    {
        $this->etablissementPrecedent = $etablissementPrecedent;
        return $this;
    }

    public function getClassePrecedente(): ?string
    {
        return $this->classePrecedente;
    }

    public function setClassePrecedente(?string $classePrecedente): self
    {
        $this->classePrecedente = $classePrecedente;
        return $this;
    }

    public function getAnneeScolairePrecedente(): ?string
    {
        return $this->anneeScolairePrecedente;
    }

    public function setAnneeScolairePrecedente(?string $anneeScolairePrecedente): self
    {
        $this->anneeScolairePrecedente = $anneeScolairePrecedente;
        return $this;
    }

    // Documents
    public function isDocumentsCertificat(): bool
    {
        return $this->documentsCertificat;
    }

    public function setDocumentsCertificat(bool $documentsCertificat): self
    {
        $this->documentsCertificat = $documentsCertificat;
        return $this;
    }

    public function isDocumentsPhoto(): bool
    {
        return $this->documentsPhoto;
    }

    public function setDocumentsPhoto(bool $documentsPhoto): self
    {
        $this->documentsPhoto = $documentsPhoto;
        return $this;
    }

    public function isDocumentsBulletins(): bool
    {
        return $this->documentsBulletins;
    }

    public function setDocumentsBulletins(bool $documentsBulletins): self
    {
        $this->documentsBulletins = $documentsBulletins;
        return $this;
    }

    public function isDocumentsAssurance(): bool
    {
        return $this->documentsAssurance;
    }

    public function setDocumentsAssurance(bool $documentsAssurance): self
    {
        $this->documentsAssurance = $documentsAssurance;
        return $this;
    }

    public function isComplete(): bool
    {
        return $this->isComplete;
    }

    public function setIsComplete(bool $isComplete): self
    {
        $this->isComplete = $isComplete;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
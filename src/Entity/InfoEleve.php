<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Entity\RepresentantLegal;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\InfoEleveRepository;
use App\Entity\User;

#[ORM\Entity(repositoryClass: InfoEleveRepository::class)]
class InfoEleve
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_de_naissance = null;

    #[ORM\Column(nullable: true)]
    private ?string $promotion = null;

    #[ORM\ManyToOne(targetEntity: Classe::class, inversedBy: 'infoEleves')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Classe $classe = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nationalite = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $departement = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $communenaissance = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nom_contacte_urgence = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $numero_contacte_urgence = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dernier_rappel_antitetanique = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $observations = null;

    #type du payement mdl (par cheque ou espece) 
    #aurais du s'appeler paimentMdlParCheque
    #[ORM\Column(nullable: true)]
    private ?bool $cheque = null;

    #[ORM\Column(nullable: true)]
    private ?bool $droit_image = null;

    #[ORM\Column(nullable: true)]
    private ?bool $redoublant = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $dernier_diplome = null;

    #[ORM\Column(length: 9, nullable: true)]
    private ?string $immattriculationVeic = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $numero_mobile = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $numSecuSocial= null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $transportScolaire = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $LVUn = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $LVDeux = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $regime = null;


    #[ORM\Column(nullable: true)]
    private ?bool $sms_send = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ResposableFinancier $responsable_financier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?CentreSecuriteSociale $secu_sociale = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?ScolariteAnterieur $anne_scolaire_un = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?ScolariteAnterieur $anne_scolaire_deux = null;


    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?MedecinTraitant $medecin_traitant = null;

    #lien eleve ? #je voulais dire quoi par ça ?
    #[ORM\ManyToOne(inversedBy: 'infoEleves')]
    #[ORM\JoinColumn(nullable: true)]
    private ?RepresentantLegal $responsable_un = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?RepresentantLegal $responsable_deux = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?AssuranceScolaire $assureur = null;

    #[ORM\OneToOne(inversedBy: 'infoEleve')]
    #[ORM\JoinColumn(nullable: true)]
    private User $user;

    public function __construct(user $user)
    {
        $this->user = $user;
        $user->setInfoEleve($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDeNaissance(): ?\DateTimeInterface
    {
        return $this->date_de_naissance;
    }
    public function setDateDeNaissance(?\DateTimeInterface $dateNaissance): self
    {
        $this->date_de_naissance = $dateNaissance;
        return $this;
    }

    public function getPromotion(): ?string
    {
        return $this->promotion;
    }

    public function setPromotion(?string $promo):static
    {
        $this->promotion = $promo;

        return $this;
    }


    public function getClasse(): ?Classe
    {
        return $this->classe;
    }
    public function setClasse(?Classe $classe): static
    {
        $this->classe = $classe;
        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(?string $nationalite): static
    {
        $this->nationalite = $nationalite;

        return $this;
    }

    public function getDepartement(): ?string
    {
        return $this->departement;
    }

    public function setDepartement(?string $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function getCommuneNaissance(): ?string
    {
        return $this->communenaissance;
    }

    public function setCommuneNaissance(?string $commune): static
    {
        $this->communenaissance = $commune;

        return $this;
    }

    public function getNomContacteUrgence(): ?string
    {
        return $this->nom_contacte_urgence;
    }

    public function setNomContacteUrgence(?string $nom_contacte_urgence): static
    {
        $this->nom_contacte_urgence = $nom_contacte_urgence;

        return $this;
    }

    public function getNumeroContacteUrgence(): ?string
    {
        return $this->numero_contacte_urgence;
    }

    public function setNumeroContacteUrgence(?string $numero_contacte_urgence): static
    {
        $this->numero_contacte_urgence = $numero_contacte_urgence;

        return $this;
    }

    public function getDernierRappelAntitetanique(): ?\DateTimeInterface
    {
        return $this->dernier_rappel_antitetanique;
    }

    public function setDernierRappelAntitetanique(?\DateTimeInterface $dernier_rappel_antitetanique): static
    {
        $this->dernier_rappel_antitetanique = $dernier_rappel_antitetanique;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;

        return $this;
    }

    public function isCheque(): ?bool
    {
        return $this->cheque;
    }

    public function setCheque(?bool $cheque): static
    {
        $this->cheque = $cheque;

        return $this;
    }

    public function isDroitImage(): ?bool
    {
        return $this->droit_image;
    }

    public function setDroitImage(?bool $droit_image): static
    {
        $this->droit_image = $droit_image;

        return $this;
    }

    public function isRedoublant(): ?bool
    {
        return $this->redoublant;
    }

    public function setRedoublant(?bool $redoublant): static
    {
        $this->redoublant = $redoublant;

        return $this;
    }

    public function getDernierDiplome(): ?string
    {
        return $this->dernier_diplome;
    }

    public function setDernierDiplome(?string $dernier_diplome): static
    {
        $this->dernier_diplome = $dernier_diplome;

        return $this;
    }

    public function getImmattriculationVeic(): ?string
    {
        return $this->immattriculationVeic;
    }

    public function setImmattriculationVeic(?string $immattriculationVeic): static
    {
        $this->immattriculationVeic = $immattriculationVeic;

        return $this;
    }

    public function getNumeroMobile()
    {
        return $this->numero_mobile;
    }

    public function setNumeroMobile($numero_mobile): static
    {
        $this->numero_mobile = $numero_mobile;

        return $this;
    }

    public function getNumSecuSocial()
    {
        return $this->numSecuSocial;
    }

    public function setNumSecuSocial($numSecuSocial): static
    {
        $this->numSecuSocial = $numSecuSocial;

        return $this;
    }

    public function getTransportScolaire() : ?string
    {
        return $this->transportScolaire;
    }

    public function setTransportScolaire(?string $transportScolaire): self
    {
        $this->transportScolaire = $transportScolaire;
        return $this;
    }

    public function getResponsableFinancier(): ?ResposableFinancier
    {
        return $this->responsable_financier;
    }

    public function setResponsableFinancier(?ResposableFinancier $responsable_financier): static
    {
        $this->responsable_financier = $responsable_financier;

        return $this;
    }

    public function getSecuSociale(): ?CentreSecuriteSociale
    {
        return $this->secu_sociale;
    }

    public function setSecuSociale(?CentreSecuriteSociale $secu_sociale): static
    {
        $this->secu_sociale = $secu_sociale;

        return $this;
    }

    public function getAnneScolaireUn(): ?ScolariteAnterieur
    {
        return $this->anne_scolaire_un;
    }

    public function setAnneScolaireUn(?ScolariteAnterieur $anne_scolaire_un): static
    {
        $this->anne_scolaire_un = $anne_scolaire_un;

        return $this;
    }

    public function getAnneScolaireDeux(): ?ScolariteAnterieur
    {
        return $this->anne_scolaire_deux;
    }

    public function setAnneScolaireDeux(?ScolariteAnterieur $anne_scolaire_deux): static
    {
        $this->anne_scolaire_deux = $anne_scolaire_deux;

        return $this;
    }

    public function getLVUn(): ?string
    {
        return $this->LVUn;
    }
    
    public function setLVUn(?string $LVUn): static
    {
        $this->LVUn = $LVUn;

        return $this;
    }

    public function getLVDeux(): ?string
    {
        return $this->LVDeux;
    }

    public function setLVDeux(?string $LVDeux): static
    {
        $this->LVDeux = $LVDeux;

        return $this;
    }

    public function getMedecinTraitant(): ?MedecinTraitant
    {
        return $this->medecin_traitant;
    }

    public function setMedecinTraitant(?MedecinTraitant $medecin_traitant): static
    {
        $this->medecin_traitant = $medecin_traitant;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getRegime(): ?string
    {
        return $this->regime;
    }

    public function setRegime(?string $regime): static
    {
        $this->regime = $regime;

        return $this;
    }

    public function getResponsableUn(): ?RepresentantLegal
    {
        return $this->responsable_un;
    }

    public function setResponsableUn(?RepresentantLegal $responsable_un): static
    {
        $this->responsable_un = $responsable_un;

        return $this;
    }

    public function getResponsableDeux(): ?RepresentantLegal
    {
        return $this->responsable_deux;
    }

    public function setResponsableDeux(?RepresentantLegal $responsable_deux): static
    {
        $this->responsable_deux = $responsable_deux;

        return $this;
    }

    public function getAssureur(): ?AssuranceScolaire
    {
        return $this->assureur;
    }

    public function setAssureur(?AssuranceScolaire $assureur): static
    {
        $this->assureur = $assureur;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function switchResponsable()
    {
        $repLegaltemp = $this->getResponsableDeux();
        $this->setResponsableDeux($this->getResponsableUn());
        $this->setResponsableUn($repLegaltemp);
    }

    #[ORM\OneToOne(targetEntity: Adhesion::class)]
    private ?Adhesion $adhesion = null;

    public function getAdhesion(): ?Adhesion
    {
        return $this->adhesion;
    }

    public function setAdhesion(?Adhesion $adhesion): static
    {
        $this->adhesion = $adhesion;

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private bool $inscriptionComplete = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $dateInscription = null;

    public function setInscriptionComplete(bool $inscriptionComplete): self
    {
        $this->inscriptionComplete = $inscriptionComplete;
        return $this;
    }

    public function isInscriptionComplete(): bool
    {
        return $this->inscriptionComplete;
    }

    public function setDateInscription(?\DateTime $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->dateInscription;
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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $carteVitaleFilename = null;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $photoIdentiteFilename = null;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $attestationIdentiteFilename = null;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $bourseFilename = null;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $attestationJDCFilename = null;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $attestationReusiteFilename = null;
    
    // Getters et setters
    public function getCarteVitaleFilename(): ?string
    {
        return $this->carteVitaleFilename;
    }
    
    public function setCarteVitaleFilename(?string $carteVitaleFilename): self
    {
        $this->carteVitaleFilename = $carteVitaleFilename;
        return $this;
    }
    
    public function getPhotoIdentiteFilename(): ?string
    {
        return $this->photoIdentiteFilename;
    }
    
    public function setPhotoIdentiteFilename(?string $photoIdentiteFilename): self
    {
        $this->photoIdentiteFilename = $photoIdentiteFilename;
        return $this;
    }
    
    public function getAttestationIdentiteFilename(): ?string
    {
        return $this->attestationIdentiteFilename;
    }
    
    public function setAttestationIdentiteFilename(?string $attestationIdentiteFilename): self
    {
        $this->attestationIdentiteFilename = $attestationIdentiteFilename;
        return $this;
    }
    
    public function getBourseFilename(): ?string
    {
        return $this->bourseFilename;
    }
    
    public function setBourseFilename(?string $bourseFilename): self
    {
        $this->bourseFilename = $bourseFilename;
        return $this;
    }
    
    public function getAttestationJDCFilename(): ?string
    {
        return $this->attestationJDCFilename;
    }
    
    public function setAttestationJDCFilename(?string $attestationJDCFilename): self
    {
        $this->attestationJDCFilename = $attestationJDCFilename;
        return $this;
    }
    
    public function getAttestationReusiteFilename(): ?string
    {
        return $this->attestationReusiteFilename;
    }
    
    public function setAttestationReusiteFilename(?string $attestationReusiteFilename): self
    {
        $this->attestationReusiteFilename = $attestationReusiteFilename;
        return $this;
    }
}
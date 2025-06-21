<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // === Tous les champs de données ===
    // Étape 1 - Informations personnelles
    private ?string $nom = null;
    private ?string $prenom = null;
    private ?string $email = null;
    private ?\DateTimeInterface $dateNaissance = null;
    private ?string $sexe = null;
    private ?string $nationalite = null;
    private ?string $departement = null;
    private ?string $departementNaissance = null;
    private ?string $communeNaissance = null;

    // Étape 2 - Contact et urgence
    private ?string $numeroMobile = null;
    private ?string $nomContacteUrgence = null;
    private ?string $numeroContacteUrgence = null;

    // Étape 3 - Informations scolaires
    private ?string $classe = null;
    private ?string $promotion = null;
    private ?string $regime = null;
    private ?string $lvUn = null;
    private ?string $lvDeux = null;
    private ?bool $redoublant = false;
    private ?string $dernierDiplome = null;
    private ?string $transportScolaire = null;
    private ?string $immatriculationVeic = null;
    private ?string $numSecuSocial = null;

    // Étape 4 - Représentant légal 1
    private ?string $representantLegal1Nom = null;
    private ?string $representantLegal1Prenom = null;
    private ?string $representantLegal1Email = null;
    private ?string $representantLegal1Telephone = null;
    private ?string $representantLegal1TelephoneFixe = null;
    private ?string $representantLegal1TelephonePro = null;
    private ?string $representantLegal1Adresse = null;
    private ?string $representantLegal1CodePostal = null;
    private ?string $representantLegal1Commune = null;
    private ?string $representantLegal1LienEleve = null;
    private ?string $representantLegal1Poste = null;
    private ?string $representantLegal1NomEmployeur = null;
    private ?string $representantLegal1AdresseEmployeur = null;

    // Étape 5 - Représentant légal 2
    private ?string $representantLegal2Nom = null;
    private ?string $representantLegal2Prenom = null;
    private ?string $representantLegal2Email = null;
    private ?string $representantLegal2Telephone = null;
    private ?string $representantLegal2TelephoneFixe = null;
    private ?string $representantLegal2TelephonePro = null;
    private ?string $representantLegal2Adresse = null;
    private ?string $representantLegal2CodePostal = null;
    private ?string $representantLegal2Commune = null;
    private ?string $representantLegal2LienEleve = null;
    private ?string $representantLegal2Poste = null;
    private ?string $representantLegal2NomEmployeur = null;
    private ?string $representantLegal2AdresseEmployeur = null;

    // Étape 6 - Scolarité antérieure
    private ?string $etablissementPrecedent1 = null;
    private ?string $classePrecedente1 = null;
    private ?string $anneeScolairePrecedente1 = null;
    private ?string $etablissementPrecedent2 = null;
    private ?string $classePrecedente2 = null;
    private ?string $anneeScolairePrecedente2 = null;

    // Étape 7 - Informations médicales
    private ?string $medecinTraitantNom = null;
    private ?string $medecinTraitantTelephone = null;
    private ?string $medecinTraitantAdresse = null;
    private ?string $dernierRappelAntitetanique = null;
    private ?string $observations = null;
    private ?string $secuSocialeNom = null;
    private ?string $secuSocialeAdresse = null;
    private ?string $assureurNom = null;
    private ?string $assureurAdresse = null;
    private ?string $assureurNumeroAssurance = null;

    // Étape 8 - Responsable financier
    private ?string $responsableFinancierNom = null;
    private ?string $responsableFinancierPrenom = null;
    private ?string $responsableFinancierRIB = null;
    private ?string $responsableFinancierNomEmployeur = null;
    private ?string $responsableFinancierAdresseEmployeur = null;

    // Étape 9 - Documents à fournir
    private ?string $carteVitale = null;
    private ?string $photoIdentite = null;
    private ?string $bourse = null;
    private ?string $attestationJDC = null;
    private ?string $attestationIdentite = null;
    private ?string $attestationReusite = null;

    // Étape 10 - Finalisation et adhésion
    private ?bool $cheque = false;
    private ?bool $droitImage = false;
    private ?bool $adhesionAccepted = false;
    private ?string $adhesionPaymentMethod = null;
    private ?string $adhesionImageRights = null;

    // Générer automatiquement les getters/setters ci-dessous
    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getNom(): ?string
    {
        return $this->nom;
    }
    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }
    public function getPrenom(): ?string
    {
        return $this->prenom;
    }
    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }
    public function setDateNaissance(\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }
    public function getSexe(): ?string
    {
        return $this->sexe;
    }
    public function setSexe(string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }
}

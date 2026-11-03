<?php
namespace App\Core;

/**
 * Validation des entrées côté serveur (jamais uniquement côté navigateur).
 */
final class Validator
{
    private array $erreurs = [];

    public function __construct(private array $donnees)
    {
    }

    public function required(string $champ, string $libelle): self
    {
        $valeur = trim((string) ($this->donnees[$champ] ?? ''));
        if ($valeur === '') {
            $this->erreurs[$champ] = "Le champ « $libelle » est obligatoire.";
        }
        return $this;
    }

    public function maxLength(string $champ, string $libelle, int $max): self
    {
        $valeur = (string) ($this->donnees[$champ] ?? '');
        if (mb_strlen($valeur) > $max) {
            $this->erreurs[$champ] = "Le champ « $libelle » ne doit pas dépasser $max caractères.";
        }
        return $this;
    }

    public function minLongueur(string $champ, string $libelle, int $min): self
    {
        $valeur = (string) ($this->donnees[$champ] ?? '');
        if ($valeur !== '' && mb_strlen($valeur) < $min) {
            $this->erreurs[$champ] = "Le champ « $libelle » doit contenir au moins $min caractères.";
        }
        return $this;
    }

    public function email(string $champ, string $libelle): self
    {
        $valeur = trim((string) ($this->donnees[$champ] ?? ''));
        if ($valeur !== '' && !filter_var($valeur, FILTER_VALIDATE_EMAIL)) {
            $this->erreurs[$champ] = "Le champ « $libelle » doit contenir une adresse e-mail valide.";
        }
        return $this;
    }

    public function date(string $champ, string $libelle): self
    {
        $valeur = trim((string) ($this->donnees[$champ] ?? ''));
        if ($valeur !== '' && !preg_match('#^\d{4}-\d{2}-\d{2}$#', $valeur)) {
            $this->erreurs[$champ] = "Le champ « $libelle » doit être une date valide.";
        }
        return $this;
    }

    public function heure(string $champ, string $libelle): self
    {
        $valeur = trim((string) ($this->donnees[$champ] ?? ''));
        if ($valeur !== '' && !preg_match('#^\d{2}:\d{2}$#', $valeur)) {
            $this->erreurs[$champ] = "Le champ « $libelle » doit être une heure valide (format HH:MM).";
        }
        return $this;
    }

    public function entier(string $champ, string $libelle, int $min = 0, int $max = 100000): self
    {
        $valeur = $this->donnees[$champ] ?? '';
        if ($valeur !== '' && $valeur !== null) {
            if (!is_numeric($valeur) || (int) $valeur < $min || (int) $valeur > $max) {
                $this->erreurs[$champ] = "Le champ « $libelle » doit être un nombre entre $min et $max.";
            }
        }
        return $this;
    }

    public function dans(string $champ, string $libelle, array $valeurs): self
    {
        $valeur = $this->donnees[$champ] ?? '';
        if ($valeur !== '' && !in_array($valeur, $valeurs, true)) {
            $this->erreurs[$champ] = "La valeur du champ « $libelle » est invalide.";
        }
        return $this;
    }

    public function apres(string $champDebut, string $champFin, string $libelleDebut, string $libelleFin): self
    {
        $debut = $this->donnees[$champDebut] ?? '';
        $fin   = $this->donnees[$champFin] ?? '';
        if ($debut !== '' && $fin !== '' && $debut !== '00:00' && $fin !== '00:00' && $fin <= $debut) {
            $this->erreurs[$champFin] = "L'heure de fin doit être postérieure à l'heure de $libelleDebut.";
        }
        return $this;
    }

    public function echoue(): bool
    {
        return $this->erreurs !== [];
    }

    public function premierMessage(): string
    {
        return reset($this->erreurs) ?: 'Les données envoyées sont invalides.';
    }

    public function erreurs(): array
    {
        return $this->erreurs;
    }
}

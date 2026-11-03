# Cahier des charges : plateforme de gestion d'un centre de formation

**Nom de code du projet : GCF (Gestion Centre de Formation)**
**Version : 1.1 (cadrage validé, précisé le 3 novembre 2026)**
**Date : novembre 2026**

Propositions de nom final (au choix) : FormaDesk, CentreFlow, Salla. Le nom de code GCF est utilisé dans tout le document.

---

## 1. Vision

Le centre dispose de salles (conférences, réunions, formation) et organise des formations, uniquement dans le domaine du numérique. Aujourd'hui, les réservations et les inscriptions se gèrent de manière dispersée (téléphone, papier, WhatsApp). La plateforme GCF réunit tout dans un seul outil :

1. **Volet 1 : réservation des salles**, avec planning clair, heure précise, et statuts pilotés par le gestionnaire (libre, réservé, occupé)
2. **Volet 2 : gestion des formations numériques**, du catalogue à l'inscription en ligne, en passant par la gestion des apprenants, les retours des formateurs et les propositions de coût aux entreprises

L'inspiration : le fonctionnement d'un palais des congrès. Pour prendre une salle, il faut faire une réservation sur la plateforme, et le gestionnaire confirme en cochant le statut.

---

## 2. Les acteurs et leurs rôles

| Acteur | Ce qu'il fait sur la plateforme |
|---|---|
| **Administrateur** | Gère les comptes, les salles, les paramètres. Accès à tout. |
| **Gestionnaire de salles** | Valide les réservations, coche Libre / Réservé / Occupé, gère le planning des salles. |
| **Responsable formations** | Crée les formations et les sessions, affecte les formateurs, suit les inscriptions, établit les devis pour les entreprises. |
| **Formateur** | Consulte son planning, ses sessions, la liste des apprenants, saisit les feuilles de présence et ses retours. |
| **Apprenant** | Consulte le catalogue, s'inscrit en ligne à une session, suit son dossier. |
| **Entreprise (représentant)** | Demande une formation sur mesure pour son personnel, reçoit une proposition de coût, suit son dossier. |
| **Visiteur** | Consulte le catalogue public et les disponibilités des salles. |

Chaque rôle voit uniquement ce qui le concerne. Un formateur ne voit pas la gestion des salles, un apprenant ne voit pas les tableaux de bord.

---

## 3. Volet 1 : réservation des salles

### 3.1 Les fiches salles

Chaque salle possède sa fiche : nom, photo, capacité (nombre de places), équipements (vidéoprojecteur, tableau blanc, climatisation, sono, wifi), localisation dans le centre, et tarif de location éventuel.

### 3.2 Les statuts (le cœur du système)

| Statut | Signification | Couleur badge |
|---|---|---|
| **Libre** | Aucune réservation sur le créneau | vert |
| **En attente** | Pré-réservation déposée, non encore validée | jaune |
| **Réservé** | Réservation validée par le gestionnaire, créneau verrouillé | orange |
| **Occupé** | L'événement est en cours (le gestionnaire coche au démarrage) | rouge |
| **Indisponible** | Maintenance ou blocage manuel | gris |

Le scénario type, celui de l'exemple de Jean :

1. Jean veut la salle B, dimanche de 08h à 12h, pour 40 personnes
2. Il dépose sa demande (ou le gestionnaire la saisit) : la salle passe **En attente**
3. Le gestionnaire vérifie le planning et valide : la salle passe **Réservé** sur ce créneau
4. Dimanche 08h, l'événement commence : le gestionnaire coche **Occupé**
5. À 12h, l'événement finit : le gestionnaire coche **Libre** (ou la libération est automatique à la fin du créneau)

Une demande déposée en ligne ne vaut pas confirmation : seul le gestionnaire valide. C'est la règle des plateformes professionnelles de réservation d'équipements.

### 3.3 Le planning

- Vue hebdomadaire : les salles en lignes, les jours en colonnes, les créneaux horaires (demi-heure par demi-heure) colorés selon le statut
- Vue journalière par salle : la journée complète avec le détail des événements
- Vue liste filtrable : par salle, par statut, par date, par demandeur
- **Anti-conflit garanti** : deux réservations ne peuvent pas se chevaucher sur une même salle. Le système bloque et signale le conflit dès la saisie
- Réservations récurrentes : toutes les semaines, tous les lundis, etc., avec possibilité d'exception

### 3.4 La réservation

Une réservation contient : le demandeur (nom, organisation, contact), la salle, la date, l'heure de début et l'heure de fin précises, l'intitulé de l'événement ou de la conférence, le nombre de personnes attendues, et éventuellement des besoins spécifiques.

Le **nombre de personnes inscrites** apparaît directement sur le planning (par exemple : Salle B, 40 places, 37 inscrits). Pour les événements avec inscription, un compteur en temps réel indique le remplissage.

### 3.5 Indicateurs du volet 1

- Taux d'occupation par salle et par période
- Nombre de réservations par mois, annulations, no-show
- Heures d'utilisation par salle
- Export du planning et des statistiques

---

## 4. Volet 2 : les formations numériques

### 4.1 Le catalogue

Catalogue public (sans compte) des formations, toutes dans le numérique : bureautique, initiation à l'informatique, programmation web, réseaux, cybersécurité, maintenance, outils numériques pour entreprises, etc.

Chaque formation a sa fiche : titre, thème, programme détaillé (modules), durée, niveau, public visé, prérequis, tarif indicatif, formateur référent.

### 4.2 Les sessions

Une formation se déroule en **sessions** planifiées : dates, horaires, **salle attribuée** (liée au volet 1 : la salle de la session est automatiquement réservée et verrouillée dans le planning des salles), nombre de places, formateur affecté, coût.

C'est la règle d'or de la plateforme : une session de formation réserve sa salle automatiquement. Les deux volets sont reliés.

### 4.3 L'inscription en ligne

L'apprenant choisit une session dans le catalogue et s'inscrit directement en ligne : identité, contact, niveau, motivation. Les places disponibles diminuent en temps réel. Quand la session est complète, elle passe en liste d'attente.

Le responsable peut aussi qualifier une demande (la convertir en apprenant inscrit sans ressaisir le dossier).

### 4.4 La gestion des apprenants

- Dossier apprenant : coordonnées, formations suivies, historique, attestations
- Feuille de présence (émargement) par séance : présent, absent, retard
- Génération des documents : convocation, attestation de fin de formation, certificat de réalisation

### 4.5 L'espace formateur

- Tableau de bord : ses sessions à venir, passées
- Liste des apprenants inscrits avec leurs coordonnées
- Saisie de la feuille de présence à chaque séance
- **Retours du formateur** après chaque session : compte rendu, difficultés constatées, niveau réel du groupe, recommandations. Ces retours sont accessibles au responsable (c'est le cahier des charges pédagogique du formateur)
- Évaluation des apprenants par le formateur (notation, appréciation)

### 4.6 Les évaluations de satisfaction

En fin de session, l'apprenant remplit un questionnaire en ligne (à chaud) : contenu, formateur, organisation, salle. Résultats agrégés dans le tableau de bord. Option plus tard : évaluation à froid (quelques mois après).

### 4.7 Les demandes des entreprises (B2B)

Une entreprise demande une formation sur mesure pour son personnel :

1. Elle remplit le formulaire : thème souhaité, nombre de personnes, période, niveau
2. Le responsable construit une **proposition** : programme, formateur, durée, salle, **coût détaillé**
3. La proposition est envoyée (devis), puis validée ou ajustée
4. Une fois acceptée, une session dédiée est créée, avec inscription des employés de l'entreprise (liste fournie ou inscription par lien)

### 4.8 Indicateurs du volet 2

- Nombre d'inscrits par session, taux de remplissage, taux de présence réel
- Formations les plus demandées, taux de satisfaction par formation et par formateur
- Chiffre d'affaires des sessions payantes et des prestations entreprises

---

## 5. Les parcours clés (résumé)

**Parcours 1 : réserver une salle (l'exemple de Jean)**
Demande (Jean, dimanche 08h-12h, 40 personnes) → statut En attente → validation gestionnaire → Réservé → le jour J → Occupé → fin → Libre.

**Parcours 2 : un apprenant s'inscrit**
Catalogue en ligne → fiche formation → session du 15 au 20 décembre, 20 places → inscription (formulaire) → confirmation → convocation → présence → attestation.

**Parcours 3 : une entreprise commande une formation**
Formulaire entreprise → proposition avec coût → validation → session dédiée créée → salle réservée automatiquement → employés inscrits → déroulement → retours formateur + satisfaction → attestation et clôture.

**Parcours 4 : le formateur clôture une séance**
Espace formateur → séance du jour → feuille de présence → retours (compte rendu) → envoi au responsable.

---

## 6. Architecture technique

### 6.1 Choix

- **PHP 8.2+** et **MySQL** (conformément à la demande)
- Architecture MVC propre : séparation contrôleurs / modèles / vues, un routeur unique, autoloading PSR-4
- Requêtes exclusivement en **PDO préparé** (aucune concaténation SQL)
- Interface en **français**, pensée **mobile d'abord** (utilisable sur téléphone)

### 6.2 Sécurité (non négociable)

- Mots de passe hachés (bcrypt ou argon2)
- Protection CSRF sur tous les formulaires
- Sessions sécurisées (régénération d'identifiant, expiration, cookie httpOnly)
- Contrôle d'accès par rôle sur CHAQUE page et CHAQUE route API (côté serveur, jamais seulement côté interface)
- Validation et nettoyage de toutes les entrées (côté serveur systématiquement)
- Échappement de toutes les sorties (protection XSS)
- Journalisation des actions sensibles (connexions, validations, suppressions, modifications de statut)
- Messages d'erreur propres, jamais de message technique brut à l'écran
- Rate limiting sur la connexion et l'inscription
- Sauvegardes quotidiennes automatiques de la base

### 6.3 Modèle de données (tables principales)

| Table | Contenu |
|---|---|
| users | comptes, rôles, mots de passe hachés |
| salles | fiches salles, capacité, équipements, statut global |
| reservations | salle, demandeur, date, heure début/fin, événement, nb personnes, statut |
| reservation_events | historique des changements de statut (traçabilité : qui a coché quoi et quand) |
| formations | catalogue (titre, programme, durée, niveau, tarif indicatif) |
| sessions | formation, dates, salle (liée au planning), formateur, places, coût |
| inscriptions | apprenant, session, statut (inscrit, liste d'attente, annulé, terminé) |
| presences | séance, apprenant, statut (présent, absent, retard) |
| retours_formateur | session, compte rendu, appréciations |
| evaluations | session, apprenant, notes de satisfaction |
| entreprises | répertoire des entreprises clientes |
| demandes_formation | demande B2B, statut, proposition de coût |
| documents | convocations, attestations, devis générés |

---

## 7. Design et interface

Conformément au style établi sur les projets précédents :

- **Tableau de bord** avec sidebar de navigation par rôle, cartes de synthèse, badges de statut colorés
- Badges : vert (libre), jaune (en attente), orange (réservé), rouge (occupé), gris (indisponible)
- Tableaux clairs, triables, avec recherche ; entièrement responsive (lecture confortable sur téléphone)
- Formulaires épurés avec messages d'aide et validation en douceur
- Planning lisible : codes couleur constants, journée type visible d'un coup d'œil
- Typographie moderne (Poppins ou équivalent), palette sobre avec une couleur d'accent

---

## 8. Plan de réalisation (phases)

| Phase | Contenu | Livrable |
|---|---|---|
| **1. Socle + volet salles** | Authentification, rôles, fiches salles, réservations, statuts, planning hebdo, anti-conflit | La plateforme gère les salles de bout en bout |
| **2. Volet formations** | Catalogue, sessions (réservation auto de salle), inscription en ligne, gestion apprenants, émargement | Le catalogue et les inscriptions tournent |
| **3. Formateurs + B2B** | Espace formateur, retours, évaluations satisfaction, demandes entreprises, propositions de coût | Le circuit pédagogique et commercial complet |
| **4. Pilotage + documents** | Tableaux de bord, statistiques, PDF (convocations, attestations, devis), exports | Outil de pilotage complet |

Chaque phase est livrée testée et utilisable en production.

---

## 9. Hypothèses prises (à confirmer)

1. **Un seul centre** au départ (architecture préête pour plusieurs sites plus tard)
2. **Monnaie : le franc CFA (FCFA)** pour les coûts et propositions
3. **Pas de paiement en ligne** au démarrage : les règlements se règlent hors plateforme (le paiement en ligne peut s'ajouter dans une phase ultérieure)
4. Notifications par e-mail (et plus tard WhatsApp) : confirmation d'inscription, convocation, rappel de réservation
5. La plateforme est en français uniquement au départ (l'anglais pourra être ajouté)

Ces hypothèses guident le développement mais se modifient en une journée si besoin.

---

## 10. Ce que je demande à Chef

1. Valider ce cadrage (ou corriger les points qui clochent)
2. Confirmer les 5 hypothèses de la section 9
3. Donner le nom du centre, son logo et ses couleurs (pour la charte)
4. Le paiement en ligne : vraiment pas au départ ?

Dès validation, je démarre la phase 1.

---

## 11. Précisions v1.1 (3 novembre 2026)

Ces précisions complètent le cadrage initial sans le modifier. Elles concernent le parcours d'inscription et les notifications.

### 11.1 Parcours d'inscription en ligne

1. Le candidat ouvre la page publique d'inscription et choisit sa session dans une **liste déroulante** des sessions ouvertes (formation, dates, horaires, places restantes).
2. Il remplit le formulaire (état civil, e-mail, téléphone, niveau, motivation) et valide. Aucun compte n'est créé à ce stade.
3. Il reçoit un **e-mail de vérification** avec un lien à durée d'action (jeton à usage unique).
4. Au clic sur le lien : son **compte apprenant est créé automatiquement**, avec un **matricule** (format GCF-AAAA-NNNN) et un **mot de passe provisoire généré par la plateforme**.
5. Le matricule et le mot de passe lui sont **envoyés par e-mail**. Si la session est complète à ce moment-là, l'inscription passe en liste d'attente et le candidat en est informé.

### 11.2 Connexion

La connexion se fait avec **deux champs** : un identifiant qui accepte **l'e-mail OU le matricule**, et le mot de passe. L'apprenant peut changer son mot de passe depuis son espace.

### 11.3 Espace apprenant

Chaque apprenant dispose d'un espace personnel : ses formations à venir (dates, horaires, salle, formateur), ses formations terminées, les nouvelles sessions ouvertes aux inscriptions, et son profil (coordonnées, changement de mot de passe, matricule visible).

### 11.4 Notifications e-mail

Tout passe par e-mail, à chaque étape clé :

- **Réservation de salle** : le gestionnaire est prévenu de chaque nouvelle demande ; le demandeur est informé lorsque sa demande est confirmée, refusée, lorsque la salle devient occupée, puis lorsqu'elle est libérée.
- **Inscription** : e-mail de vérification, puis e-mail avec les identifiants (matricule + mot de passe).

Deux pilotes d'envoi : **journal** (les e-mails sont enregistrés en base, idéal en local et pour la démonstration) et **mail()** (envoi réel sur un hébergement configuré).

### 11.5 Découpage en livraisons

| Phase | Contenu | État |
|---|---|---|
| **1** | Socle (authentification, rôles), salles, réservations avec statuts et anti-conflit, planning hebdomadaire, catalogue formations, sessions avec réservation automatique de salle, inscription en ligne complète, espace apprenant, journal e-mails | **Livré** |
| 2 | Feuilles de présence, évaluations à chaud / à froid, espace formateur | À venir |
| 3 | Portail entreprises : demandes de formation sur mesure, propositions de coût, suivi | À venir |
| 4 | Pilotage : taux d'occupation, statistiques, documents PDF (convocations, attestations) | À venir |

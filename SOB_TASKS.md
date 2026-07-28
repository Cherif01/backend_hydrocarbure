# Tâches Module Comptabilite

## Première étape obligatoire

- Créer d'abord le module Comptabilite avec la commande:

```bash
php artisan make:module Comptabilite
```

- Après création du module, organiser l'implémentation dans:
- `app/Modules/Comptabilite/Models`
- `app/Modules/Comptabilite/Controllers`
- `app/Modules/Comptabilite/Requests`
- `app/Modules/Comptabilite/Resources`
- `app/Modules/Comptabilite/Routes`

## Migrations déjà disponibles

### `type_operations`

- Table: `type_operations`
- Champs:
- `id`
- `libelle` unique
- `description` nullable
- `nature` booléen
- `is_active` booléen
- `created_at`
- `updated_at`

### `caisses`

- Table: `caisses`
- Champs:
- `id`
- `station_id` obligatoire
- `reference` unique
- `libelle` 
- `solde_initial`
- `is_active`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

### `operations`

- Table: `operations`
- Champs:
- `id`
- `type_operation_id` nullable
- `station_id` nullable
- `caisse_id` nullable
- `montant`
- `commentaire` nullable
- `date_operation`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `deleted_at` avec `softDeletes`

## Ce qu'il faut implémenter

### 1. Modèles

- Créer `TypeOperation`
- Créer `Caisse`
- Créer `Operation`

### 2. Controllers CRUD

- Créer `TypeOperationController`
- Créer `CaisseController`
- Créer `OperationController`

### 3. Form Requests

- Créer `TypeOperationRequest`
- Créer `CaisseRequest`
- Créer `OperationRequest`

### 4. Resources

- Créer `TypeOperationResource`
- Créer `CaisseResource`
- Créer `OperationResource`

### 5. Routes API

- Ajouter les routes dans `app/Modules/Comptabilite/Routes/api.php`
- Utiliser un préfixe API cohérent, par exemple:
- `/api/v1/comptabilite/type-operations`
- `/api/v1/comptabilite/caisses`
- `/api/v1/comptabilite/operations`

## Règles de validation et comportement attendus

### TypeOperation

- CRUD complet
- `libelle` requis, string, unique
- `description` nullable
- `nature` requis ou nullable selon le besoin métier, mais géré comme booléen
- `is_active` booléen

### Caisse

- CRUD complet
- `station_id` non requis pour un utilisateur scope station
- si l'utilisateur connecté est scope station via `UserStationScopeService`, alors:
- `station_id` doit être injecté automatiquement avec sa station
- l'utilisateur ne doit voir que les caisses de sa station
- l'utilisateur ne doit pas pouvoir accéder, modifier ou supprimer une caisse d'une autre station
- `reference` doit être unique
- `libelle` requis, string
- `solde_initial` numérique, minimum `0`
- `is_active` booléen

### Operation

- CRUD complet
- utiliser `SoftDeletes` dans le modèle `Operation`
- `type_operation_id` est requis coté request
- `caisse_id` nullable mais doit exister s'il est fourni
- `station_id` nullable côté requête, mais:
- si l'utilisateur est scope station, `station_id` doit être automatiquement imposé
- si `caisse_id` est fourni, vérifier la cohérence entre la caisse et la station
- l'utilisateur scope station ne doit voir que les opérations de sa station
- l'utilisateur scope station ne doit pas pouvoir consulter, modifier ou supprimer une opération d'une autre station
- `montant` numérique, minimum `0`
- `commentaire` nullable
- `date_operation` requis, format date/datetime

## Utilisation obligatoire de `UserStationScopeService`

### Le service doit être utilisé dans les CRUD concernés

- `CaisseController@index`
- `CaisseController@show`
- `CaisseController@store`
- `CaisseController@update`
- `CaisseController@destroy`
- `OperationController@index`
- `OperationController@show`
- `OperationController@store`
- `OperationController@update`
- `OperationController@destroy`

### Logique attendue

- Vérifier si l'utilisateur connecté a un module actif `gerant_station`
- Si non:
- pas de scope station
- comportement normal
- Si oui:
- chercher son affectation station active
- si aucune affectation active n'existe, retourner `403`
- si une affectation existe:
- récupérer `station_id`
- filtrer les listes sur ce `station_id`
- restreindre `show`, `update`, `destroy` aux données de cette station
- injecter automatiquement `station_id` dans les créations et mises à jour nécessaires

## Relations à prévoir dans les modèles

### TypeOperation

- relation avec `Operation`

### Caisse

- relation avec `Station`
- relation avec `User` via `createdBy`
- relation avec `User` via `updatedBy`
- relation avec `Operation`

### Operation

- relation avec `TypeOperation`
- relation avec `Caisse`
- relation avec `Station`
- relation avec `User` via `createdBy`
- relation avec `User` via `updatedBy`

## Détails attendus dans les Resources

### TypeOperationResource

- `id`
- `libelle`
- `description`
- `nature`
- `is_active`
- `created_at`
- `updated_at`

### CaisseResource

- `id`
- `station_id`
- `reference`
- `libelle`
- `solde_initial`
- `is_active`
- station si chargée
- `created_by` si chargé
- `updated_by` si chargé
- `created_at`
- `updated_at`

### OperationResource

- `id`
- `type_operation_id`
- `station_id`
- `caisse_id`
- `montant`
- `commentaire`
- `date_operation`
- type operation si chargé
- caisse si chargée
- station si chargée
- `created_by` si chargé
- `updated_by` si chargé
- `created_at`
- `updated_at`

## Contrôles métier à ajouter

### Pour `type_operations`

- Clarifier la signification de `nature`
- `true` = entrée d'argent
- `false` = sortie d'argent
- Ajouter si besoin un libellé frontend dérivé: `entree` ou `sortie`

### Pour `caisses`

- Une caisse appartient à une station
- Deux caisses ne peuvent pas partager la même `reference`

### Pour `operations`

- Une opération peut être liée à un type d'opération
- Une opération peut être liée à une caisse
- Une opération peut être liée à une station
- Si une caisse est fournie, vérifier qu'elle appartient à la bonne station
- Si l'utilisateur est scope station, empêcher toute incohérence station/caisse
- Prévoir si besoin un contrôle futur sur le solde de caisse selon la nature du type d'opération

## Convention d'implémentation

- Utiliser `FormRequest` pour toutes les validations
- Utiliser `Resource` pour tous les retours API
- Utiliser `ApiResponses` pour homogénéiser les réponses
- Renseigner `created_by` à la création
- Renseigner `updated_by` à la mise à jour
- Journaliser les actions importantes avec `logActivity()`
- Charger les relations utiles avec `with(...)`

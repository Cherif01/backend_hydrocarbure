# Tâches

## Déjà implémenté

### Module Administration

- Authentification: register, login, logout, me, update profile, update password
- CRUD des modules
- CRUD des affectations modules utilisateurs
- Envoi du code d'accès
- Vérification du code d'accès

### Module Gestions

- CRUD des stations
- Switch du statut des stations
- CRUD des affectations stations
- Switch du statut des affectations stations

### Module Ressource Humaine

- CRUD des postes
- CRUD des employés
- Scope des employés par station pour les utilisateurs `gerant_station`

### Service transversal

- `UserStationScopeService` pour centraliser la logique du scope station

## À faire maintenant

### Dans le module Gestions

- CRUD des pompes
- CRUD des pistolets
- CRUD des hydrocarbures
- Ajouter les routes API du module
- Utiliser les `FormRequest` pour toutes les validations
- Utiliser les `Resource` pour tous les retours
- Utiliser `UserStationScopeService` pour le scope de l'utilisateur connecté dans les méthodes `index`, `show`, `store`, `update`, `destroy`
- Les migrations existent déjà

### Détails attendus pour les hydrocarbures

- Créer `Hydrocarbure` model
- Créer `HydrocarbureController`
- Créer `HydrocarbureRequest`
- Créer `HydrocarbureResource`
- Ajouter les routes sous `/api/v1/gestions/hydrocarbures`
- Gérer `libelle`, `prix_achat`, `prix_vente`

### Détails attendus pour les pompes

- Créer `Pompe` model si absent
- Créer `PompeController`
- Créer `PompeRequest`
- Créer `PompeResource`
- Ajouter les routes sous `/api/v1/gestions/pompes`
- Gérer l'auto-génération de la `reference` si elle n'est pas fournie
- Si l'utilisateur connecté est scope par station, forcer `station_id` avec sa station
- Filtrer la liste des pompes par `station_id` pour les utilisateurs `gerant_station`

### Détails attendus pour les pistolets

- Créer `Pistolet` model si absent
- Créer `PistoletController`
- Créer `PistoletRequest`
- Créer `PistoletResource`
- Ajouter les routes sous `/api/v1/gestions/pistolets`
- Lier chaque pistolet à une `pompe` et à un `hydrocarbure`
- Si l'utilisateur connecté est scope par station:
- vérifier que la pompe choisie appartient à sa station
- filtrer la liste des pistolets par la station de la pompe

## Logique de `UserStationScopeService`

### Quand appliquer le scope station

- Vérifier si l'utilisateur connecté a un module actif `gerant_station`
- Si non:
- ne pas appliquer de filtre station
- laisser les endpoints fonctionner normalement
- Si oui:
- chercher son affectation station active
- si aucune affectation active n'existe, retourner `403`

### Données retournées par le service

- `is_station_scoped`
- `station_id`
- `affectation_station_id`

### Cas d'usage du service

- Dans les méthodes `index`: filtrer par `station_id`
- Dans les méthodes `show`: empêcher l'accès aux données d'une autre station
- Dans les méthodes `store`: injecter automatiquement `station_id` si nécessaire
- Dans les méthodes `update`: forcer ou verrouiller `station_id` selon la logique métier
- Dans les méthodes `destroy`: empêcher la suppression des données d'une autre station
- Dans `verifyAccessCode`: vérifier qu'un utilisateur `gerant_station` a une affectation active

## Contrôles métier à respecter

### Généraux

- Toujours remplir `created_by` à la création
- Toujours remplir `updated_by` à la mise à jour
- Utiliser `logActivity()` pour les actions importantes
- Charger les relations utiles avec `with(...)`
- Retourner les données formatées via les `Resource`

### Pompes

- Une pompe appartient à une station
- La `reference` doit être unique
- Le scope station doit empêcher la création ou modification sur une autre station

### Pistolets

- Un pistolet appartient à une pompe
- Un pistolet appartient à un hydrocarbure
- Un utilisateur scope station ne doit voir que les pistolets de sa station

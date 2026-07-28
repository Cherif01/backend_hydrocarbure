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
- Liste, consultation, création et mise à jour des hydrocarbures
- Liste, consultation, création et mise à jour des pompes
- Liste, consultation, création et mise à jour des pistolets
- Routes API, `FormRequest` et `Resource` pour les hydrocarbures, pompes et pistolets
- Scope station des pompes et pistolets pour les utilisateurs `gerant_station`
- Aucune route de suppression pour les hydrocarbures, pompes et pistolets

### Module Ressource Humaine

- CRUD des postes
- CRUD des employés
- Scope des employés par station pour les utilisateurs `gerant_station`

### Service transversal

- `UserStationScopeService` pour centraliser la logique du scope station

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
- Dans les méthodes `destroy` des ressources qui exposent cette action: empêcher la suppression des données d'une autre station
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
- La `reference` absente à la création est générée séquentiellement (`POM01`, `POM02`, etc.)
- `PUT` est une mise à jour complète des champs métier obligatoires
- `PATCH` est une mise à jour partielle
- Le scope station doit empêcher la création ou modification sur une autre station
- Aucune route `DELETE` n'est exposée

### Pistolets

- Un pistolet appartient à une pompe
- Un pistolet appartient à un hydrocarbure
- Un utilisateur scope station ne doit voir que les pistolets de sa station
- Aucune route `DELETE` n'est exposée

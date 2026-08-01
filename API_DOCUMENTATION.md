# API Documentation (v1)

This document covers all currently implemented API endpoints (based on the current routes + controllers in this repository). It is written for frontend integration and follows the expected userflow.

- Base URL: `/api`
- API prefix: `/v1`
- Auth: Laravel Sanctum (Bearer token)
- Content-Type: JSON unless uploading files

## Response Envelope

All controllers use the same envelope defined in `App\Traits\ApiResponses`.

### Success

```json
{
    "status": 1,
    "message": "Operation successful",
    "data": {}
}
```

### Success With Token (auth)

```json
{
    "status": 1,
    "data": {},
    "token": "1|plain-text-token",
    "message": "Authentication successful"
}
```

### Error

```json
{
    "status": 0,
    "message": "Something went wrong",
    "error": []
}
```

### Success Without Data

```json
{
    "status": 1,
    "message": "Operation successful"
}
```

## Common Headers

### JSON endpoints

```http
Accept: application/json
Content-Type: application/json
```

### Protected endpoints

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>
```

### File uploads

Use `multipart/form-data` for:

- `avatar` (users, clients, employees)
- `image` (stations)
- `fichier_scan` (citerne documents)
- `facture_scan` (maintenance citerne)
- `facture` (affectation citerne depense)

## Userflow (Frontend)

1. `POST /api/v1/auth/login` (or register)
2. Store `token`
3. `GET /api/v1/auth/me` to retrieve `user_modules[]` and station affectations (`affectations[]`)
4. If access to a module requires a code, call `POST /api/v1/admin/verify-access-code`
5. Use module endpoints (Gestions, RH, Comptabilite, Transport) with the Bearer token

## Access Restrictions (Current Enforcement)

Authorization is mostly enforced at 3 levels:

1. **Route-level auth**: almost all endpoints are under `auth:sanctum` (401 if missing/invalid token).
2. **Role checks**: only some endpoints enforce roles (example: switch user status is `super_admin` only; Cuve/CuveJaugeage/Approvision enforce role/module).
3. **Station scope** (for some endpoints): when the authenticated user has an active module `gerant_station` and has an active station affectation, many endpoints become station-scoped using `UserStationScopeService`.

Important notes for frontend:

- If the user has module `gerant_station` active but has no active station affectation, some station-scoped endpoints respond with `403` and message similar to `Vous n'avez pas été affecté à une station`.
- Some controllers do not implement role checks and therefore allow any authenticated user to access (create/update/delete) their resources. This documentation reflects current behavior, not intended policy.

## Root Endpoint

### GET `/api/user` (protected)

- Auth: Yes (`auth:sanctum`)
- Response example:

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
}
```

# 1) Administration / Auth

Base: `/api/v1/auth` and `/api/v1/admin`

## 1.1 Register

### POST `/api/v1/auth/register` (public)

- Content-Type: `multipart/form-data` if `avatar` is provided

Request example:

```json
{
    "name": "John Doe",
    "telephone": "070000000",
    "email": "john@example.com",
    "role": "user",
    "password": "secret12",
    "password_confirmation": "secret12"
}
```

Response example:

```json
{
    "status": 1,
    "data": {
        "id": 1,
        "name": "John Doe",
        "telephone": "070000000",
        "email": "john@example.com",
        "avatar_url": null,
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:00:00"
    },
    "token": "1|plain-text-token",
    "message": "Utilisateur créé avec succès."
}
```

## 1.2 Login

### POST `/api/v1/auth/login` (public)

Request example:

```json
{
    "telephone": "070000000",
    "password": "secret12"
}
```

Response example:

```json
{
    "status": 1,
    "data": {
        "id": 1,
        "name": "John Doe",
        "telephone": "070000000",
        "email": "john@example.com",
        "avatar_url": null,
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:00:00"
    },
    "token": "1|plain-text-token",
    "message": "Utilisateur connecté avec succès."
}
```

## 1.3 Logout

### POST `/api/v1/auth/logout` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Utilisateur deconnecté avec succès."
}
```

## 1.4 Me (Get current user)

### GET `/api/v1/auth/me` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Utilisateur récupéré avec succès.",
    "data": {
        "id": 1,
        "name": "John Doe",
        "telephone": "070000000",
        "email": "john@example.com",
        "avatar_url": null,
        "role": "user",
        "user_modules": [
            {
                "id": 10,
                "module_id": 2,
                "name": "gerant_station",
                "description": null,
                "code_acces": "1234",
                "is_active": true
            }
        ],
        "affectations": [
            {
                "id": 5,
                "station_id": 3,
                "is_active": true,
                "station": {
                    "reference": "STAABC123",
                    "libelle": "Station A",
                    "description": null,
                    "addresse": "Rue 1",
                    "ville": "Abidjan"
                }
            }
        ],
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:00:00"
    }
}
```

## 1.5 Update profile

### PUT `/api/v1/auth/me` (protected)

- Content-Type: `multipart/form-data` if `avatar` is provided

Request example:

```json
{
    "name": "John Updated",
    "telephone": "070000001",
    "email": "john.updated@example.com"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Profil mis à jour avec succès.",
    "data": {
        "id": 1,
        "name": "John Updated",
        "telephone": "070000001",
        "email": "john.updated@example.com",
        "avatar_url": null,
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:10:00"
    }
}
```

## 1.6 Update password

### PUT `/api/v1/auth/password` (protected)

Request example:

```json
{
    "current_password": "old-secret",
    "new_password": "new-secret",
    "new_password_confirmation": "new-secret"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Mot de passe mis à jour avec succès.",
    "data": {
        "id": 1,
        "name": "John Updated",
        "telephone": "070000001",
        "email": "john.updated@example.com",
        "avatar_url": null,
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:12:00"
    }
}
```

## 1.7 Admin - list users

### GET `/api/v1/admin/users` (protected)

Access (current enforcement): authenticated user (no explicit role check in the action).

Response example:

```json
{
    "status": 1,
    "message": "Utilisateurs récupérés avec succès.",
    "data": [
        {
            "id": 7,
            "name": "Gerant Station",
            "telephone": "0712345678",
            "email": "gerant@example.com",
            "avatar_url": null,
            "role": "user",
            "user_modules": [],
            "affectations": [],
            "created_at": "29-07-2026 10:00:00",
            "updated_at": "29-07-2026 10:00:00"
        }
    ]
}
```

## 1.8 Admin - switch user status

### PATCH `/api/v1/admin/switch-status/{user_id}` (protected)

Access: `super_admin` only (enforced in controller).

Response example:

```json
{
    "status": 1,
    "message": "Statut de l'utilisateur changé avec succès.",
    "data": {
        "id": 7,
        "name": "Gerant Station",
        "telephone": "0712345678",
        "email": "gerant@example.com",
        "avatar_url": null,
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:20:00"
    }
}
```

## 1.9 Modules

### GET `/api/v1/admin/modules` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Liste des modules chargée avec succès",
    "data": [
        {
            "id": 2,
            "name": "gerant_station",
            "description": "Gestion des stations",
            "is_active": true,
            "created_at": "29-07-2026 10:00:00",
            "updated_at": "29-07-2026 10:00:00"
        }
    ]
}
```

### POST `/api/v1/admin/modules` (protected)

Request example:

```json
{
    "name": "comptabilite",
    "description": "Module comptabilite"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Module créé avec succès",
    "data": {
        "id": 3,
        "name": "comptabilite",
        "description": "Module comptabilite",
        "is_active": true,
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:00:00"
    }
}
```

### GET `/api/v1/admin/modules/{module}` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Module chargé avec succès",
    "data": {
        "id": 3,
        "name": "comptabilite",
        "description": "Module comptabilite",
        "is_active": true,
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:00:00"
    }
}
```

### PUT/PATCH `/api/v1/admin/modules/{module}` (protected)

Request example:

```json
{
    "description": "Module comptabilite (updated)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Module mis à jour avec succès",
    "data": {
        "id": 3,
        "name": "comptabilite",
        "description": "Module comptabilite (updated)",
        "is_active": true,
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:05:00"
    }
}
```

### DELETE `/api/v1/admin/modules/{module}` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Module supprimé avec succès"
}
```

## 1.10 User Modules (assign module to user)

### GET `/api/v1/admin/user-modules` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Listes des affectations des modules aux utilisateurs",
    "data": [
        {
            "id": 10,
            "user_id": 7,
            "module_id": 2,
            "code_acces": "1234",
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/admin/user-modules` (protected)

Request example:

```json
{
    "module_id": 2,
    "user_id": 7
}
```

Response example:

```json
{
    "status": 1,
    "message": "Affectation du module aux utilisateurs",
    "data": {
        "id": 10,
        "module_id": 2,
        "user_id": 7,
        "code_acces": "1234",
        "is_active": true
    }
}
```

### GET `/api/v1/admin/user-modules/{user_module}` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Affectation chargée avec succès",
    "data": {
        "id": 10,
        "module_id": 2,
        "user_id": 7,
        "code_acces": "1234",
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/admin/user-modules/{user_module}` (protected)

Request example:

```json
{
    "is_active": false
}
```

Response example:

```json
{
    "status": 1,
    "message": "Affectation mise à jour avec succès",
    "data": {
        "id": 10,
        "module_id": 2,
        "user_id": 7,
        "code_acces": "1234",
        "is_active": false
    }
}
```

### DELETE `/api/v1/admin/user-modules/{user_module}` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Suppression de l'affectation du module aux utilisateurs"
}
```

### PATCH `/api/v1/admin/switch-status/{user_module_id}` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Changement de statut de l'affectation du module aux utilisateurs",
    "data": {
        "id": 10,
        "is_active": false
    }
}
```

### POST `/api/v1/admin/send-access-code/{user_module_id}` (protected)

Response example:

```json
{
    "status": 1,
    "message": "Envoi de code d'accès",
    "data": {
        "id": 10,
        "code_acces": "1234"
    }
}
```

### POST `/api/v1/admin/verify-access-code` (protected)

Request example:

```json
{
    "code_acces": "1234"
}
```

Success response example:

```json
{
    "status": 1,
    "message": "Vérification de code réussie",
    "data": {
        "id": 10,
        "user_id": 7,
        "module_id": 2,
        "code_acces": "1234",
        "is_active": true,
        "module": {
            "id": 2,
            "name": "gerant_station",
            "description": null,
            "is_active": true
        }
    }
}
```

Error response example (no station affectation while verifying `gerant_station`):

```json
{
    "status": 0,
    "message": "Vous n'avez pas été affecté à une station",
    "error": []
}
```

# 2) Gestions

Base: `/api/v1/gestions`

## 2.1 Stations

Access (current enforcement): any authenticated user.

### GET `/api/v1/gestions/stations`

Response example:

```json
{
    "status": 1,
    "message": "Liste des stations chargee avec succes.",
    "data": [
        {
            "id": 3,
            "reference": "STAABC123",
            "libelle": "Station A",
            "adresse": "Rue 1",
            "ville": "Abidjan",
            "is_active": true,
            "created_at": "29-07-2026 10:00:00",
            "updated_at": "29-07-2026 10:00:00"
        }
    ]
}
```

### POST `/api/v1/gestions/stations`

- `reference` is optional (auto-generated if missing)
- Content-Type: `multipart/form-data` if `image` is provided

Request example:

```json
{
    "libelle": "Station A",
    "ville": "Abidjan",
    "adresse": "Rue 1",
    "longitude": -4.01,
    "latitude": 5.32,
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Station creee avec succes.",
    "data": {
        "id": 3,
        "reference": "STAABC123",
        "libelle": "Station A",
        "adresse": "Rue 1",
        "ville": "Abidjan",
        "is_active": true,
        "created_at": "29-07-2026 10:00:00",
        "updated_at": "29-07-2026 10:00:00"
    }
}
```

### GET `/api/v1/gestions/stations/{station}`

Response example:

```json
{
    "status": 1,
    "message": "Station chargee avec succes.",
    "data": {
        "id": 3,
        "reference": "STAABC123",
        "libelle": "Station A",
        "adresse": "Rue 1",
        "ville": "Abidjan",
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/gestions/stations/{station}`

Request example:

```json
{
    "libelle": "Station A (updated)",
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Station mise a jour avec succes.",
    "data": {
        "id": 3,
        "reference": "STAABC123",
        "libelle": "Station A (updated)",
        "is_active": true
    }
}
```

### PATCH `/api/v1/gestions/stations/{station}/switch`

Response example:

```json
{
    "status": 1,
    "message": "Statut de la station change avec succes.",
    "data": {
        "id": 3,
        "is_active": false
    }
}
```

### DELETE `/api/v1/gestions/stations/{station}`

Response example:

```json
{
    "status": 1,
    "message": "Station supprimee avec succes."
}
```

## 2.2 Affectation Stations (assign station to user)

Access (current enforcement): any authenticated user. Business rule enforced: a user cannot have 2 active station affectations.

### GET `/api/v1/gestions/affectation-stations`

Response example:

```json
{
    "status": 1,
    "message": "Liste des affectations de stations chargee avec succes.",
    "data": [
        {
            "id": 5,
            "station_id": 3,
            "user_id": 7,
            "is_active": true,
            "created_at": "29-07-2026 10:00:00",
            "updated_at": "29-07-2026 10:00:00"
        }
    ]
}
```

### POST `/api/v1/gestions/affectation-stations`

Request example:

```json
{
    "station_id": 3,
    "user_id": 7
}
```

Success response example:

```json
{
    "status": 1,
    "message": "Affectation de station creee avec succes.",
    "data": {
        "id": 5,
        "station_id": 3,
        "user_id": 7,
        "is_active": true
    }
}
```

Business error example (duplicate active affectation):

```json
{
    "status": 0,
    "message": "Cet utilisateur a deja une affectation de station active.",
    "error": []
}
```

### GET `/api/v1/gestions/affectation-stations/{affectation_station}`

Response example:

```json
{
    "status": 1,
    "message": "Affectation chargee avec succes.",
    "data": {
        "id": 5,
        "station_id": 3,
        "user_id": 7,
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/gestions/affectation-stations/{affectation_station}`

Request example:

```json
{
    "station_id": 4
}
```

Response example:

```json
{
    "status": 1,
    "message": "Affectation de station mise a jour avec succes.",
    "data": {
        "id": 5,
        "station_id": 4,
        "user_id": 7,
        "is_active": true
    }
}
```

### PATCH `/api/v1/gestions/affectation-stations/{affectation_station}/switch-status`

Response example:

```json
{
    "status": 1,
    "message": "Statut de l'affectation de station change avec succes.",
    "data": {
        "id": 5,
        "is_active": false
    }
}
```

### DELETE `/api/v1/gestions/affectation-stations/{affectation_station}`

Response example:

```json
{
    "status": 1,
    "message": "Affectation de station supprimee avec succes."
}
```

## 2.3 Hydrocarbures

Access (current enforcement): any authenticated user.

### GET `/api/v1/gestions/hydrocarbures`

Response example:

```json
{
    "status": 1,
    "message": "Liste des hydrocarbures chargee avec succes.",
    "data": [
        {
            "id": 1,
            "libelle": "Essence",
            "prix_achat": "800.00",
            "prix_vente": "850.00",
            "created_at": "29-07-2026 10:00:00",
            "updated_at": "29-07-2026 10:00:00"
        }
    ]
}
```

### POST `/api/v1/gestions/hydrocarbures`

Request example:

```json
{
    "libelle": "Essence",
    "prix_achat": 800,
    "prix_vente": 850
}
```

Response example:

```json
{
    "status": 1,
    "message": "Hydrocarbure cree avec succes.",
    "data": {
        "id": 1,
        "libelle": "Essence",
        "prix_achat": "800.00",
        "prix_vente": "850.00"
    }
}
```

### GET `/api/v1/gestions/hydrocarbures/{hydrocarbure}`

Response example:

```json
{
    "status": 1,
    "message": "Hydrocarbure charge avec succes.",
    "data": {
        "id": 1,
        "libelle": "Essence",
        "prix_achat": "800.00",
        "prix_vente": "850.00"
    }
}
```

### PUT/PATCH `/api/v1/gestions/hydrocarbures/{hydrocarbure}`

Request example:

```json
{
    "prix_vente": 860
}
```

Response example:

```json
{
    "status": 1,
    "message": "Hydrocarbure mis a jour avec succes.",
    "data": {
        "id": 1,
        "libelle": "Essence",
        "prix_achat": "800.00",
        "prix_vente": "860.00"
    }
}
```

## 2.4 Pompes

Station scope: if user is station-scoped (`gerant_station` + active affectation), results are filtered by station and `station_id` is forced on create/update.

### GET `/api/v1/gestions/pompes`

Response example:

```json
{
    "status": 1,
    "message": "Liste des pompes chargee avec succes.",
    "data": [
        {
            "id": 1,
            "reference": "POM01",
            "station_id": 3,
            "libelle": "Pompe principale",
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/gestions/pompes`

Request example:

```json
{
    "station_id": 3,
    "libelle": "Pompe principale",
    "description": "Pompe piste 1",
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Pompe creee avec succes.",
    "data": {
        "id": 1,
        "reference": "POM01",
        "station_id": 3,
        "libelle": "Pompe principale",
        "description": "Pompe piste 1",
        "is_active": true
    }
}
```

### GET `/api/v1/gestions/pompes/{pompe}`

Response example:

```json
{
    "status": 1,
    "message": "Pompe chargee avec succes.",
    "data": {
        "id": 1,
        "reference": "POM01",
        "station_id": 3,
        "libelle": "Pompe principale",
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/gestions/pompes/{pompe}`

Request example:

```json
{
    "libelle": "Pompe principale (updated)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Pompe mise a jour avec succes.",
    "data": {
        "id": 1,
        "reference": "POM01",
        "station_id": 3,
        "libelle": "Pompe principale (updated)",
        "is_active": true
    }
}
```

## 2.5 Pistolets

Station scope: resolved through `pistolet -> pompe -> station`.

### GET `/api/v1/gestions/pistolets`

Response example:

```json
{
    "status": 1,
    "message": "Liste des pistolets chargee avec succes.",
    "data": [
        {
            "id": 1,
            "pompe_id": 1,
            "hydrocarbure_id": 1,
            "libelle": "Pistolet essence 1",
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/gestions/pistolets`

Request example:

```json
{
    "pompe_id": 1,
    "hydrocarbure_id": 1,
    "libelle": "Pistolet essence 1",
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Pistolet cree avec succes.",
    "data": {
        "id": 1,
        "pompe_id": 1,
        "hydrocarbure_id": 1,
        "libelle": "Pistolet essence 1",
        "is_active": true
    }
}
```

### GET `/api/v1/gestions/pistolets/{pistolet}`

Response example:

```json
{
    "status": 1,
    "message": "Pistolet charge avec succes.",
    "data": {
        "id": 1,
        "pompe_id": 1,
        "hydrocarbure_id": 1,
        "libelle": "Pistolet essence 1",
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/gestions/pistolets/{pistolet}`

Request example:

```json
{
    "is_active": false
}
```

Response example:

```json
{
    "status": 1,
    "message": "Pistolet mis a jour avec succes.",
    "data": {
        "id": 1,
        "is_active": false
    }
}
```

## 2.6 Affectation Pistolets

Station scope: `affectation_pistolet -> pistolet -> pompe -> station`.

Business rules:

- One employee cannot have 2 active affectations at the same time.
- One pistolet cannot have 2 active affectations at the same time.
- Close transition: when updating an existing record from `is_active=true` to `is_active=false`, the backend requires `index_fermeture`, `litre_retouner`, and `montant_recu`.

### GET `/api/v1/gestions/affectation-pistolets`

Response example:

```json
{
    "status": 1,
    "message": "Liste des affectations pistolets chargee avec succes.",
    "data": [
        {
            "id": 12,
            "employee_id": 10,
            "pistolet_id": 5,
            "index_ouverture": 1200.5,
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/gestions/affectation-pistolets`

Request example:

```json
{
    "employee_id": 10,
    "pistolet_id": 5,
    "index_ouverture": 1200.5
}
```

Response example:

```json
{
    "status": 1,
    "message": "Affectation pistolet creee avec succes.",
    "data": {
        "id": 12,
        "employee_id": 10,
        "pistolet_id": 5,
        "index_ouverture": 1200.5,
        "is_active": true
    }
}
```

### GET `/api/v1/gestions/affectation-pistolets/{affectation_pistolet}`

Response example:

```json
{
    "status": 1,
    "message": "Affectation pistolet chargee avec succes.",
    "data": {
        "id": 12,
        "employee_id": 10,
        "pistolet_id": 5,
        "index_ouverture": 1200.5,
        "is_active": true,
        "sum_total_litre": 0,
        "sum_montant_paye": 0
    }
}
```

### PUT/PATCH `/api/v1/gestions/affectation-pistolets/{affectation_pistolet}`

Request example (close transition):

```json
{
    "is_active": false,
    "index_fermeture": 1300.5,
    "litre_retouner": 0,
    "montant_recu": 85000,
    "commentaire": "Fin de service"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Affectation pistolet mise a jour avec succes.",
    "data": {
        "id": 12,
        "is_active": false,
        "index_fermeture": 1300.5,
        "montant_recu": 85000
    }
}
```

### DELETE `/api/v1/gestions/affectation-pistolets/{affectation_pistolet}`

Response example:

```json
{
    "status": 1,
    "message": "Affectation pistolet supprimee avec succes."
}
```

## 2.7 Clients

Clients can be created/updated with assigned hydrocarbures using `hydrocarbure[]` in the same request.

Payload rule: `hydrocarbure.*.hydrocarbure_id` must be distinct in the request payload.

### GET `/api/v1/gestions/clients`

Response example:

```json
{
    "status": 1,
    "message": "Liste des clients chargee avec succes.",
    "data": [
        {
            "id": 9,
            "name": "Company X",
            "telephone": "070003333",
            "email": "x@corp.com",
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/gestions/clients`

- Content-Type: `multipart/form-data` if `avatar` is provided

Request example:

```json
{
    "name": "Company X",
    "telephone": "070003333",
    "email": "x@corp.com",
    "adresse": "Zone indus",
    "is_active": true,
    "hydrocarbure": [
        {
            "hydrocarbure_id": 1,
            "max_litre": 5000,
            "prix": 650
        }
    ]
}
```

Response example:

```json
{
    "status": 1,
    "message": "Client cree avec succes.",
    "data": {
        "id": 9,
        "name": "Company X",
        "telephone": "070003333",
        "email": "x@corp.com",
        "adresse": "Zone indus",
        "is_active": true
    }
}
```

### GET `/api/v1/gestions/clients/{client}`

Response example:

```json
{
    "status": 1,
    "message": "Client charge avec succes.",
    "data": {
        "id": 9,
        "name": "Company X",
        "telephone": "070003333",
        "email": "x@corp.com",
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/gestions/clients/{client}`

Request example:

```json
{
    "adresse": "Zone indus (updated)",
    "hydrocarbure": [
        {
            "hydrocarbure_id": 1,
            "max_litre": 8000,
            "prix": 645
        }
    ]
}
```

Response example:

```json
{
    "status": 1,
    "message": "Client mis a jour avec succes.",
    "data": {
        "id": 9,
        "adresse": "Zone indus (updated)"
    }
}
```

### DELETE `/api/v1/gestions/clients/{client}`

Response example:

```json
{
    "status": 1,
    "message": "Client supprime avec succes."
}
```

## 2.8 Client Hydrocarbures

Business rule: a client cannot have two active assignments for the same hydrocarbon.

### GET `/api/v1/gestions/client-hydrocarbures`

Response example:

```json
{
    "status": 1,
    "message": "Liste des clients hydrocarbures chargee avec succes.",
    "data": [
        {
            "id": 1,
            "client_id": 9,
            "hydrocarbure_id": 1,
            "max_litre": 5000,
            "prix": 650,
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/gestions/client-hydrocarbures`

Request example:

```json
{
    "client_id": 9,
    "hydrocarbure_id": 1,
    "max_litre": 5000,
    "prix": 650,
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Client hydrocarbure cree avec succes.",
    "data": {
        "id": 1,
        "client_id": 9,
        "hydrocarbure_id": 1,
        "max_litre": 5000,
        "prix": 650,
        "is_active": true
    }
}
```

### GET `/api/v1/gestions/client-hydrocarbures/{client_hydrocarbure}`

Response example:

```json
{
    "status": 1,
    "message": "Client hydrocarbure charge avec succes.",
    "data": {
        "id": 1,
        "client_id": 9,
        "hydrocarbure_id": 1,
        "max_litre": 5000,
        "prix": 650,
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/gestions/client-hydrocarbures/{client_hydrocarbure}`

Request example:

```json
{
    "prix": 645
}
```

Response example:

```json
{
    "status": 1,
    "message": "Client hydrocarbure mis a jour avec succes.",
    "data": {
        "id": 1,
        "prix": 645
    }
}
```

### DELETE `/api/v1/gestions/client-hydrocarbures/{client_hydrocarbure}`

Response example:

```json
{
    "status": 1,
    "message": "Client hydrocarbure supprime avec succes."
}
```

## 2.9 Creances

Station scope: `creance -> affectation_pistolet -> pistolet -> pompe -> station`.

Business rules:

- If station-scoped, access outside the station returns `403`.
- Using a closed affectation pistolet (`is_active=false`) is rejected with `422`.
- Amount calculation uses `client_hydrocarbure.prix` when present and coherent with the pistolet hydrocarbure, otherwise uses `affectation_pistolet.prix_vente_jour`.

### GET `/api/v1/gestions/creances`

Response example:

```json
{
    "status": 1,
    "message": "Liste des creances chargee avec succes.",
    "data": [
        {
            "id": 4,
            "client_id": 9,
            "affectation_pistolet_id": 12,
            "date_creance": "29-07-2026 00:00:00",
            "total_litre": 120,
            "montant": 78000
        }
    ]
}
```

### POST `/api/v1/gestions/creances`

Request example:

```json
{
    "client_id": 9,
    "affectation_pistolet_id": 12,
    "date_creance": "2026-07-29",
    "total_litre": 120,
    "commentaire": "Monthly credit"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Creance creee avec succes.",
    "data": {
        "id": 4,
        "client_id": 9,
        "affectation_pistolet_id": 12,
        "date_creance": "29-07-2026 00:00:00",
        "total_litre": 120,
        "montant": 78000,
        "commentaire": "Monthly credit"
    }
}
```

### GET `/api/v1/gestions/creances/{creance}`

Response example:

```json
{
    "status": 1,
    "message": "Creance chargee avec succes.",
    "data": {
        "id": 4,
        "client_id": 9,
        "total_litre": 120,
        "montant": 78000
    }
}
```

### PUT/PATCH `/api/v1/gestions/creances/{creance}`

Request example:

```json
{
    "total_litre": 130,
    "commentaire": "Adjusted"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Creance mise a jour avec succes.",
    "data": {
        "id": 4,
        "total_litre": 130,
        "montant": 84500,
        "commentaire": "Adjusted"
    }
}
```

### DELETE `/api/v1/gestions/creances/{creance}`

Response example:

```json
{
    "status": 1,
    "message": "Creance supprimee avec succes."
}
```

## 2.10 Cuves

Access (enforced):

- `super_admin` / `admin`: global
- active `gerant_station`: restricted to the active affectation station

### GET `/api/v1/gestions/cuves`

Response example:

```json
{
    "status": 1,
    "message": "Liste des cuves chargee avec succes.",
    "data": [
        {
            "id": 6,
            "station_id": 3,
            "hydrocarbure_id": 1,
            "reference": "CU-01",
            "libelle": "Cuve SP95",
            "capacite": 30000,
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/gestions/cuves`

Request example:

```json
{
    "station_id": 3,
    "hydrocarbure_id": 1,
    "reference": "CU-01",
    "libelle": "Cuve SP95",
    "capacite": 30000,
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Cuve creee avec succes.",
    "data": {
        "id": 6,
        "station_id": 3,
        "hydrocarbure_id": 1,
        "reference": "CU-01",
        "libelle": "Cuve SP95",
        "capacite": 30000,
        "is_active": true
    }
}
```

### GET `/api/v1/gestions/cuves/{cuve}`

Response example:

```json
{
    "status": 1,
    "message": "Cuve chargee avec succes.",
    "data": {
        "id": 6,
        "station_id": 3,
        "hydrocarbure_id": 1,
        "reference": "CU-01",
        "libelle": "Cuve SP95",
        "capacite": 30000,
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/gestions/cuves/{cuve}`

Request example:

```json
{
    "capacite": 32000
}
```

Response example:

```json
{
    "status": 1,
    "message": "Cuve mise a jour avec succes.",
    "data": {
        "id": 6,
        "capacite": 32000
    }
}
```

### DELETE `/api/v1/gestions/cuves/{cuve}`

Response example:

```json
{
    "status": 1,
    "message": "Cuve supprimee avec succes."
}
```

## 2.11 Cuve Jaugeages

Access (enforced): `super_admin` / `admin` OR active `gerant_station` (station-scoped).

On `store/update`, when station-scoped, `cuve_id` must belong to the same station (403 otherwise).

### GET `/api/v1/gestions/cuve-jaugeages`

Response example:

```json
{
    "status": 1,
    "message": "Liste des jaugeages chargee avec succes.",
    "data": [
        {
            "id": 1,
            "cuve_id": 6,
            "date_jauge": "2026-07-29",
            "volume_reel": 12000,
            "volume_theorique": 12100,
            "ecart": 100
        }
    ]
}
```

### POST `/api/v1/gestions/cuve-jaugeages`

Request example:

```json
{
    "cuve_id": 6,
    "date_jauge": "2026-07-29",
    "valeur_jauge": 1.25,
    "volume_reel": 12000,
    "volume_theorique": 12100,
    "commentaire": "Daily gauge"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Jaugeage cree avec succes.",
    "data": {
        "id": 1,
        "cuve_id": 6,
        "date_jauge": "2026-07-29",
        "volume_reel": 12000,
        "volume_theorique": 12100,
        "ecart": 100
    }
}
```

### GET `/api/v1/gestions/cuve-jaugeages/{cuve_jaugeage}`

Response example:

```json
{
    "status": 1,
    "message": "Jaugeage charge avec succes.",
    "data": {
        "id": 1,
        "cuve_id": 6,
        "date_jauge": "2026-07-29",
        "volume_reel": 12000,
        "volume_theorique": 12100,
        "ecart": 100
    }
}
```

### PUT/PATCH `/api/v1/gestions/cuve-jaugeages/{cuve_jaugeage}`

Request example:

```json
{
    "volume_reel": 11950,
    "commentaire": "Adjusted after recount"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Jaugeage mis a jour avec succes.",
    "data": {
        "id": 1,
        "volume_reel": 11950,
        "ecart": 150
    }
}
```

### DELETE `/api/v1/gestions/cuve-jaugeages/{cuve_jaugeage}`

Response example:

```json
{
    "status": 1,
    "message": "Jaugeage supprime avec succes."
}
```

# 3) Ressource Humaine (RH)

Base: `/api/v1/rh`

## 3.1 Posts

### GET `/api/v1/rh/posts`

Response example:

```json
{
    "status": 1,
    "message": "Liste des postes chargee avec succes.",
    "data": [
        {
            "id": 2,
            "libelle": "Caissier",
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/rh/posts`

Request example:

```json
{
    "libelle": "Caissier",
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Poste cree avec succes.",
    "data": {
        "id": 2,
        "libelle": "Caissier",
        "is_active": true
    }
}
```

### GET `/api/v1/rh/posts/{post}`

Response example:

```json
{
    "status": 1,
    "message": "Poste charge avec succes.",
    "data": {
        "id": 2,
        "libelle": "Caissier",
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/rh/posts/{post}`

Request example:

```json
{
    "libelle": "Caissier (updated)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Poste mis a jour avec succes.",
    "data": {
        "id": 2,
        "libelle": "Caissier (updated)",
        "is_active": true
    }
}
```

### DELETE `/api/v1/rh/posts/{post}`

Response example:

```json
{
    "status": 1,
    "message": "Poste supprime avec succes."
}
```

## 3.2 Employees

Station scope: if station-scoped, list/create/update are restricted to `station_id` and it is forced on create/update.

### GET `/api/v1/rh/employees`

Response example:

```json
{
    "status": 1,
    "message": "Liste des employes chargee avec succes.",
    "data": [
        {
            "id": 10,
            "name": "Cashier 1",
            "telephone": "070002222",
            "post_id": 2,
            "station_id": 3,
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/rh/employees`

- Content-Type: `multipart/form-data` if `avatar` is provided

Request example:

```json
{
    "name": "Cashier 1",
    "telephone": "070002222",
    "post_id": 2,
    "station_id": 3,
    "adresse": "Rue 1",
    "salaire_base": 150000,
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Employe cree avec succes.",
    "data": {
        "id": 10,
        "name": "Cashier 1",
        "telephone": "070002222",
        "post_id": 2,
        "station_id": 3,
        "is_active": true
    }
}
```

### GET `/api/v1/rh/employees/{employee}`

Response example:

```json
{
    "status": 1,
    "message": "Employe charge avec succes.",
    "data": {
        "id": 10,
        "name": "Cashier 1",
        "telephone": "070002222",
        "post_id": 2,
        "station_id": 3,
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/rh/employees/{employee}`

Request example:

```json
{
    "adresse": "Rue 1 (updated)",
    "salaire_base": 160000
}
```

Response example:

```json
{
    "status": 1,
    "message": "Employe mis a jour avec succes.",
    "data": {
        "id": 10,
        "adresse": "Rue 1 (updated)",
        "salaire_base": 160000
    }
}
```

### DELETE `/api/v1/rh/employees/{employee}`

Response example:

```json
{
    "status": 1,
    "message": "Employe supprime avec succes."
}
```

# 4) Comptabilite

Base: `/api/v1/comptabilite`

## 4.1 Type Operations

Access (current enforcement): any authenticated user.

### GET `/api/v1/comptabilite/type-operations`

Response example:

```json
{
    "status": 1,
    "message": "Liste des types d'operation chargee avec succes.",
    "data": [
        {
            "id": 1,
            "libelle": "Vente carburant",
            "description": "Recette journaliere",
            "nature": true,
            "nature_libelle": "entree",
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/comptabilite/type-operations`

Request example:

```json
{
    "libelle": "Vente carburant",
    "description": "Recette journaliere",
    "nature": true,
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Type d'operation cree avec succes.",
    "data": {
        "id": 1,
        "libelle": "Vente carburant",
        "nature": true,
        "nature_libelle": "entree",
        "is_active": true
    }
}
```

### GET `/api/v1/comptabilite/type-operations/{type_operation}`

Response example:

```json
{
    "status": 1,
    "message": "Type d'operation charge avec succes.",
    "data": {
        "id": 1,
        "libelle": "Vente carburant",
        "nature": true,
        "nature_libelle": "entree",
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/comptabilite/type-operations/{type_operation}`

Request example:

```json
{
    "description": "Recette journaliere (updated)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Type d'operation mis a jour avec succes.",
    "data": {
        "id": 1,
        "description": "Recette journaliere (updated)"
    }
}
```

### DELETE `/api/v1/comptabilite/type-operations/{type_operation}`

Response example:

```json
{
    "status": 1,
    "message": "Type d'operation supprime avec succes."
}
```

## 4.2 Comptes

Access (current enforcement): any authenticated user.

### GET `/api/v1/comptabilite/comptes`

Response example:

```json
{
    "status": 1,
    "message": "Liste des comptes chargee avec succes.",
    "data": [
        {
            "id": 1,
            "numero_compte": "CI-001-0001",
            "libelle": "Main Bank",
            "solde_initial": 1000000,
            "solde": 1000000,
            "devise": "XOF",
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/comptabilite/comptes`

Request example:

```json
{
    "numero_compte": "CI-001-0001",
    "libelle": "Main Bank",
    "solde_initial": 1000000,
    "devise": "XOF",
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Compte cree avec succes.",
    "data": {
        "id": 1,
        "numero_compte": "CI-001-0001",
        "libelle": "Main Bank",
        "solde_initial": 1000000,
        "solde": 1000000,
        "devise": "XOF",
        "is_active": true
    }
}
```

### GET `/api/v1/comptabilite/comptes/{compte}`

Response example:

```json
{
    "status": 1,
    "message": "Compte charge avec succes.",
    "data": {
        "id": 1,
        "numero_compte": "CI-001-0001",
        "libelle": "Main Bank",
        "solde_initial": 1000000,
        "solde": 1000000,
        "devise": "XOF",
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/comptabilite/comptes/{compte}`

Request example:

```json
{
    "libelle": "Main Bank (updated)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Compte mis a jour avec succes.",
    "data": {
        "id": 1,
        "libelle": "Main Bank (updated)"
    }
}
```

### DELETE `/api/v1/comptabilite/comptes/{compte}`

Response example:

```json
{
    "status": 1,
    "message": "Compte supprime avec succes."
}
```

## 4.3 Caisses

Station scope: if station-scoped, list/show/update/delete are filtered by station and `station_id` is forced. On create, if not station-scoped, `station_id` is required (422 if missing).

Solde calculation:

- `solde_initial`
- `+ SUM(operations.montant)` where `type_operations.nature = true` (entree)
- `- SUM(operations.montant)` where `type_operations.nature = false` (sortie)
- `- SUM(versements.montant)` where `status IN ('recu','confirmer')`
- Only for the primary (auto-created) caisse of a station (MIN(caisse.id)):
    - `+ SUM(affectation_pistolets.montant_recu)` (closed affectations only)
    - `+ SUM(paiement_creances.montant)` (excluding soft-deleted)

### GET `/api/v1/comptabilite/caisses`

Response example:

```json
{
    "status": 1,
    "message": "Liste des caisses chargee avec succes.",
    "data": [
        {
            "id": 1,
            "station_id": 3,
            "reference": "CAISSE-01",
            "libelle": "Caisse principale",
            "solde_initial": 0,
            "solde": 0,
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/comptabilite/caisses`

Request example:

```json
{
    "station_id": 3,
    "reference": "CAISSE-01",
    "libelle": "Caisse principale",
    "solde_initial": 0,
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Caisse creee avec succes.",
    "data": {
        "id": 1,
        "station_id": 3,
        "reference": "CAISSE-01",
        "libelle": "Caisse principale",
        "solde_initial": 0,
        "solde": 0,
        "is_active": true
    }
}
```

### GET `/api/v1/comptabilite/caisses/{caisse}`

Response example:

```json
{
    "status": 1,
    "message": "Caisse chargee avec succes.",
    "data": {
        "id": 1,
        "station_id": 3,
        "reference": "CAISSE-01",
        "libelle": "Caisse principale",
        "solde_initial": 0,
        "solde": 0,
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/comptabilite/caisses/{caisse}`

Request example:

```json
{
    "libelle": "Caisse principale (updated)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Caisse mise a jour avec succes.",
    "data": {
        "id": 1,
        "libelle": "Caisse principale (updated)"
    }
}
```

### DELETE `/api/v1/comptabilite/caisses/{caisse}`

Response example:

```json
{
    "status": 1,
    "message": "Caisse supprimee avec succes."
}
```

## 4.4 Operations

Station scope: if station-scoped, `station_id` is forced to the scoped station.

Caisse/station consistency:

- If `caisse_id` is present and `station_id` is also present, the caisse must belong to that station (422 otherwise).
- If `caisse_id` is present and `station_id` is missing, station is inferred from the caisse.

Business rule:

- If the operation is a `sortie` (type_operation.nature = false), the operation is rejected if it would make the caisse balance negative.

### GET `/api/v1/comptabilite/operations`

Response example:

```json
{
    "status": 1,
    "message": "Liste des operations chargee avec succes.",
    "data": [
        {
            "id": 1,
            "type_operation_id": 1,
            "station_id": 3,
            "caisse_id": 1,
            "montant": 50000,
            "date_operation": "29-07-2026 20:00:00"
        }
    ]
}
```

### POST `/api/v1/comptabilite/operations`

Request example:

```json
{
    "type_operation_id": 1,
    "station_id": 3,
    "caisse_id": 1,
    "montant": 50000,
    "commentaire": "Cloture caisse",
    "date_operation": "2026-07-29"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Operation creee avec succes.",
    "data": {
        "id": 1,
        "type_operation_id": 1,
        "station_id": 3,
        "caisse_id": 1,
        "montant": 50000,
        "commentaire": "Cloture caisse",
        "date_operation": "29-07-2026 00:00:00"
    }
}
```

### GET `/api/v1/comptabilite/operations/{operation}`

Response example:

```json
{
    "status": 1,
    "message": "Operation chargee avec succes.",
    "data": {
        "id": 1,
        "type_operation_id": 1,
        "station_id": 3,
        "caisse_id": 1,
        "montant": 50000,
        "date_operation": "29-07-2026 00:00:00"
    }
}
```

### PUT/PATCH `/api/v1/comptabilite/operations/{operation}`

Request example:

```json
{
    "montant": 52000,
    "commentaire": "Correction"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Operation mise a jour avec succes.",
    "data": {
        "id": 1,
        "montant": 52000,
        "commentaire": "Correction"
    }
}
```

### DELETE `/api/v1/comptabilite/operations/{operation}`

Response example:

```json
{
    "status": 1,
    "message": "Operation supprimee avec succes."
}
```

## 4.5 Versements

Access (enforced):

- index/show/store/update/destroy: `super_admin`/`admin` OR active module `comptabilite` OR active module `gerant_station`
- switch-status: `super_admin`/`admin` OR active module `comptabilite` OR intermediary user (`versement.user_id == auth_user.id`, but cannot set `confirmer`)

Status values:

- `en_cours`
- `rejeter`
- `annuler`
- `recu` (means the intermediary user has received the money; `date_reception` is set when switching to `recu`)
- `confirmer`

Station scope: when user is `gerant_station` (and not admin/comptabilite), versements are limited to the scoped station via `caisse.station_id`, plus any versements where `user_id == auth_user.id`.

Business rule:

- When a versement is in status `recu` or `confirmer`, it is considered debited from the caisse.
- Operations that would make the caisse balance negative are rejected on create/update/switch-status.

### GET `/api/v1/comptabilite/versements`

Response example:

```json
{
    "status": 1,
    "message": "Liste des versements chargee avec succes.",
    "data": [
        {
            "id": 1,
            "caisse_id": 1,
            "compte_id": 1,
            "type": "direct",
            "user_id": null,
            "montant": 250000,
            "date_versement": "29-07-2026 00:00:00",
            "date_reception": null,
            "status": "en_cours"
        }
    ]
}
```

### POST `/api/v1/comptabilite/versements`

Request example:

```json
{
    "compte_id": 1,
    "caisse_id": 1,
    "type": "direct",
    "montant": 250000,
    "date_versement": "2026-07-29",
    "commentaire": "Deposit"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Versement cree avec succes.",
    "data": {
        "id": 1,
        "compte_id": 1,
        "caisse_id": 1,
        "type": "direct",
        "montant": 250000,
        "date_versement": "29-07-2026 00:00:00",
        "date_reception": null,
        "status": "en_cours"
    }
}
```

### GET `/api/v1/comptabilite/versements/{versement}`

Response example:

```json
{
    "status": 1,
    "message": "Versement charge avec succes.",
    "data": {
        "id": 1,
        "compte_id": 1,
        "caisse_id": 1,
        "type": "direct",
        "montant": 250000,
        "date_reception": null,
        "status": "en_cours"
    }
}
```

### PUT/PATCH `/api/v1/comptabilite/versements/{versement}`

Request example:

```json
{
    "commentaire": "Deposit (updated)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Versement mis a jour avec succes.",
    "data": {
        "id": 1,
        "commentaire": "Deposit (updated)"
    }
}
```

### PATCH `/api/v1/comptabilite/versements/{versement}/switch-status`

Request example:

```json
{
    "status": "recu"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Statut du versement change avec succes.",
    "data": {
        "id": 1,
        "date_reception": "30-07-2026 10:00:00",
        "status": "recu"
    }
}
```

### DELETE `/api/v1/comptabilite/versements/{versement}`

Response example:

```json
{
    "status": 1,
    "message": "Versement supprime avec succes."
}
```

## 4.6 Paiement Creances

Access:

- If user has active module `comptabilite`: global access + no station filtering on index
- Otherwise: station scope may apply through `UserStationScopeService`

Business rules:

- `client_id` must match `creance.client_id`
- Overpayment is not allowed (`total_payments <= creance.montant`)

### GET `/api/v1/comptabilite/paiement-creances`

Response example:

```json
{
    "status": 1,
    "message": "Liste des paiements chargee avec succes.",
    "data": [
        {
            "id": 1,
            "reference": "PC-2026-001",
            "client_id": 9,
            "creance_id": 4,
            "montant": 50000,
            "mode_paiement": "cash",
            "date_paiement": "29-07-2026 00:00:00"
        }
    ]
}
```

### POST `/api/v1/comptabilite/paiement-creances`

Request example:

```json
{
    "reference": "PC-2026-001",
    "client_id": 9,
    "creance_id": 4,
    "montant": 50000,
    "mode_paiement": "cash",
    "date_paiement": "2026-07-29",
    "commentaire": "Partial payment"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Paiement cree avec succes.",
    "data": {
        "id": 1,
        "reference": "PC-2026-001",
        "client_id": 9,
        "creance_id": 4,
        "montant": 50000,
        "mode_paiement": "cash",
        "date_paiement": "29-07-2026 00:00:00"
    }
}
```

### GET `/api/v1/comptabilite/paiement-creances/{paiement_creance}`

Response example:

```json
{
    "status": 1,
    "message": "Paiement charge avec succes.",
    "data": {
        "id": 1,
        "reference": "PC-2026-001",
        "client_id": 9,
        "creance_id": 4,
        "montant": 50000
    }
}
```

### PUT/PATCH `/api/v1/comptabilite/paiement-creances/{paiement_creance}`

Request example:

```json
{
    "montant": 55000
}
```

Response example:

```json
{
    "status": 1,
    "message": "Paiement mis a jour avec succes.",
    "data": {
        "id": 1,
        "montant": 55000
    }
}
```

### DELETE `/api/v1/comptabilite/paiement-creances/{paiement_creance}`

Response example:

```json
{
    "status": 1,
    "message": "Paiement supprime avec succes."
}
```

## 4.7 Client Depots

Base: `/api/v1/comptabilite/client-depots`

Access (current enforcement): any authenticated user.

### GET `/api/v1/comptabilite/client-depots`

Response example:

```json
{
    "status": 1,
    "message": "Liste des depots clients chargee avec succes.",
    "data": [
        {
            "id": 1,
            "client_id": 1,
            "reference": "CLDEP-ABC123",
            "libelle": "Depot client",
            "commentaire": null,
            "date_depot": "01-08-2026 10:00:00",
            "montant": 100000
        }
    ]
}
```

### POST `/api/v1/comptabilite/client-depots`

Request example:

```json
{
    "client_id": 1,
    "libelle": "Depot client",
    "commentaire": "Depot du matin",
    "date_depot": "2026-08-01",
    "montant": 100000
}
```

Response example:

```json
{
    "status": 1,
    "message": "Depot client cree avec succes.",
    "data": {
        "id": 1,
        "client_id": 1,
        "reference": "CLDEP-ABC123",
        "libelle": "Depot client",
        "commentaire": "Depot du matin",
        "date_depot": "01-08-2026 00:00:00",
        "montant": 100000
    }
}
```

### GET `/api/v1/comptabilite/client-depots/{client_depot}`

Response example:

```json
{
    "status": 1,
    "message": "Depot client charge avec succes.",
    "data": {
        "id": 1,
        "client_id": 1,
        "reference": "CLDEP-ABC123",
        "libelle": "Depot client",
        "montant": 100000
    }
}
```

### PUT/PATCH `/api/v1/comptabilite/client-depots/{client_depot}`

Request example:

```json
{
    "commentaire": "Depot du matin (modifie)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Depot client mis a jour avec succes.",
    "data": {
        "id": 1,
        "commentaire": "Depot du matin (modifie)"
    }
}
```

### DELETE `/api/v1/comptabilite/client-depots/{client_depot}`

Response example:

```json
{
    "status": 1,
    "message": "Depot client supprime avec succes."
}
```

## 4.8 Compte Transactions

Base: `/api/v1/comptabilite/compte-transactions`

Access (enforced):

- `super_admin` / `admin` OR active module `comptabilite`

Business rule:

- A transaction from `compte_source_id` to `compte_destination_id` is rejected if the source account does not have enough balance.

### GET `/api/v1/comptabilite/compte-transactions`

Response example:

```json
{
    "status": 1,
    "message": "Liste des transactions de comptes chargee avec succes.",
    "data": [
        {
            "id": 1,
            "reference": "CPTR-ABC123",
            "compte_source_id": 1,
            "compte_destination_id": 2,
            "montant": 50000,
            "libelle": "Transfert",
            "commentaire": null,
            "date_transaction": "01-08-2026 10:00:00"
        }
    ]
}
```

### POST `/api/v1/comptabilite/compte-transactions`

Request example:

```json
{
    "compte_source_id": 1,
    "compte_destination_id": 2,
    "montant": 50000,
    "libelle": "Transfert",
    "commentaire": "Transfert interne",
    "date_transaction": "2026-08-01"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Transaction de compte creee avec succes.",
    "data": {
        "id": 1,
        "reference": "CPTR-ABC123",
        "compte_source_id": 1,
        "compte_destination_id": 2,
        "montant": 50000,
        "libelle": "Transfert",
        "date_transaction": "01-08-2026 00:00:00"
    }
}
```

### GET `/api/v1/comptabilite/compte-transactions/{compte_transaction}`

Response example:

```json
{
    "status": 1,
    "message": "Transaction de compte chargee avec succes.",
    "data": {
        "id": 1,
        "reference": "CPTR-ABC123",
        "montant": 50000
    }
}
```

### PUT/PATCH `/api/v1/comptabilite/compte-transactions/{compte_transaction}`

Request example:

```json
{
    "commentaire": "Transfert interne (modifie)"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Transaction de compte mise a jour avec succes.",
    "data": {
        "id": 1,
        "commentaire": "Transfert interne (modifie)"
    }
}
```

### DELETE `/api/v1/comptabilite/compte-transactions/{compte_transaction}`

Response example:

```json
{
    "status": 1,
    "message": "Transaction de compte supprimee avec succes."
}
```

# 5) Transport

Base: `/api/v1/transport`

General note: most Transport controllers do not enforce role/module restrictions (current enforcement: any authenticated user), except `Approvisions` which applies Cuve-like access restrictions.

## 5.1 Citernes

### GET `/api/v1/transport/citernes`

Response example:

```json
{
    "status": 1,
    "message": "Liste des citernes chargee avec succes.",
    "data": [
        {
            "id": 1,
            "immatriculation": "AB-123-CD",
            "type_citerne": "camion_citerne",
            "statut": "disponible",
            "etat": "interne",
            "capacite_nominale_litres": 30000,
            "is_active": true
        }
    ]
}
```

### POST `/api/v1/transport/citernes`

Request example:

```json
{
    "immatriculation": "AB-123-CD",
    "type_citerne": "camion_citerne",
    "marque": "Mercedes",
    "modele": "Actros",
    "statut": "disponible",
    "etat": "interne",
    "annee_fabrication": 2018,
    "capacite_nominale_litres": 30000,
    "capacite_utile_litres": 29500,
    "is_active": true
}
```

Response example:

```json
{
    "status": 1,
    "message": "Citerne creee avec succes.",
    "data": {
        "id": 1,
        "immatriculation": "AB-123-CD",
        "type_citerne": "camion_citerne",
        "statut": "disponible",
        "etat": "interne",
        "capacite_nominale_litres": 30000,
        "capacite_utile_litres": 29500,
        "is_active": true
    }
}
```

### GET `/api/v1/transport/citernes/{citerne}`

Response example:

```json
{
    "status": 1,
    "message": "Citerne chargee avec succes.",
    "data": {
        "id": 1,
        "immatriculation": "AB-123-CD",
        "type_citerne": "camion_citerne",
        "statut": "disponible",
        "etat": "interne",
        "capacite_nominale_litres": 30000,
        "is_active": true
    }
}
```

### PUT/PATCH `/api/v1/transport/citernes/{citerne}`

Request example:

```json
{
    "statut": "en_maintenance"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Citerne mise a jour avec succes.",
    "data": {
        "id": 1,
        "statut": "en_maintenance"
    }
}
```

### DELETE `/api/v1/transport/citernes/{citerne}`

Response example:

```json
{
    "status": 1,
    "message": "Citerne supprimee avec succes."
}
```

## 5.2 Citerne Compartiments

Note: `numero_compartiment` is validated as unique in the table (current enforcement: unique globally, not per citerne).

### GET `/api/v1/transport/citerne-compartiments`

Response example:

```json
{
    "status": 1,
    "message": "Liste des compartiments chargee avec succes.",
    "data": [
        {
            "id": 1,
            "citerne_id": 1,
            "hydrocarbure_id": 1,
            "numero_compartiment": 1,
            "capacite_litres": 15000
        }
    ]
}
```

### POST `/api/v1/transport/citerne-compartiments`

Request example:

```json
{
    "citerne_id": 1,
    "hydrocarbure_id": 1,
    "numero_compartiment": 1,
    "capacite_litres": 15000
}
```

Response example:

```json
{
    "status": 1,
    "message": "Compartiment cree avec succes.",
    "data": {
        "id": 1,
        "citerne_id": 1,
        "hydrocarbure_id": 1,
        "numero_compartiment": 1,
        "capacite_litres": 15000
    }
}
```

### GET `/api/v1/transport/citerne-compartiments/{citerne_compartiment}`

Response example:

```json
{
    "status": 1,
    "message": "Compartiment charge avec succes.",
    "data": {
        "id": 1,
        "citerne_id": 1,
        "hydrocarbure_id": 1,
        "numero_compartiment": 1,
        "capacite_litres": 15000
    }
}
```

### PUT/PATCH `/api/v1/transport/citerne-compartiments/{citerne_compartiment}`

Request example:

```json
{
    "capacite_litres": 15500
}
```

Response example:

```json
{
    "status": 1,
    "message": "Compartiment mis a jour avec succes.",
    "data": {
        "id": 1,
        "capacite_litres": 15500
    }
}
```

### DELETE `/api/v1/transport/citerne-compartiments/{citerne_compartiment}`

Response example:

```json
{
    "status": 1,
    "message": "Compartiment supprime avec succes."
}
```

## 5.3 Citerne Documents

File: `fichier_scan` (optional) must be `pdf,jpeg,jpg,png` max 5MB.

### GET `/api/v1/transport/citerne-documents`

Response example:

```json
{
    "status": 1,
    "message": "Liste des documents chargee avec succes.",
    "data": [
        {
            "id": 1,
            "citerne_id": 1,
            "type_document": "assurance",
            "numero_document": "ASS-2026-001",
            "date_emission": "2026-07-01",
            "date_expiration": "2027-07-01",
            "fichier_scan": null
        }
    ]
}
```

### POST `/api/v1/transport/citerne-documents`

- Content-Type: `multipart/form-data` if `fichier_scan` is provided

Request example:

```json
{
    "citerne_id": 1,
    "type_document": "assurance",
    "numero_document": "ASS-2026-001",
    "date_emission": "2026-07-01",
    "date_expiration": "2027-07-01"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Document cree avec succes.",
    "data": {
        "id": 1,
        "citerne_id": 1,
        "type_document": "assurance",
        "numero_document": "ASS-2026-001",
        "date_emission": "2026-07-01",
        "date_expiration": "2027-07-01",
        "fichier_scan": null
    }
}
```

### GET `/api/v1/transport/citerne-documents/{citerne_document}`

Response example:

```json
{
    "status": 1,
    "message": "Document charge avec succes.",
    "data": {
        "id": 1,
        "citerne_id": 1,
        "type_document": "assurance",
        "numero_document": "ASS-2026-001",
        "date_emission": "2026-07-01",
        "date_expiration": "2027-07-01"
    }
}
```

### PUT/PATCH `/api/v1/transport/citerne-documents/{citerne_document}`

Request example:

```json
{
    "numero_document": "ASS-2026-002"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Document mis a jour avec succes.",
    "data": {
        "id": 1,
        "numero_document": "ASS-2026-002"
    }
}
```

### DELETE `/api/v1/transport/citerne-documents/{citerne_document}`

Response example:

```json
{
    "status": 1,
    "message": "Document supprime avec succes."
}
```

## 5.4 Maintenances Citerne

File: `facture_scan` (optional) must be `pdf,jpeg,jpg,png` max 5MB.

### GET `/api/v1/transport/maintenances-citerne`

Response example:

```json
{
    "status": 1,
    "message": "Liste des maintenances chargee avec succes.",
    "data": [
        {
            "id": 1,
            "citerne_id": 1,
            "type_maintenance": "preventive",
            "nature": "Vidange",
            "date_prevue": "2026-08-01",
            "cout": 250000,
            "status": "planifiee"
        }
    ]
}
```

### POST `/api/v1/transport/maintenances-citerne`

- Content-Type: `multipart/form-data` if `facture_scan` is provided

Request example:

```json
{
    "citerne_id": 1,
    "type_maintenance": "preventive",
    "nature": "Vidange",
    "description": "Entretien general",
    "date_prevue": "2026-08-01",
    "cout": 250000,
    "prestataire": "Garage X",
    "status": "planifiee"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Maintenance creee avec succes.",
    "data": {
        "id": 1,
        "citerne_id": 1,
        "type_maintenance": "preventive",
        "nature": "Vidange",
        "date_prevue": "2026-08-01",
        "cout": 250000,
        "status": "planifiee"
    }
}
```

### GET `/api/v1/transport/maintenances-citerne/{maintenance_citerne}`

Response example:

```json
{
    "status": 1,
    "message": "Maintenance chargee avec succes.",
    "data": {
        "id": 1,
        "citerne_id": 1,
        "type_maintenance": "preventive",
        "nature": "Vidange",
        "status": "planifiee"
    }
}
```

### PUT/PATCH `/api/v1/transport/maintenances-citerne/{maintenance_citerne}`

Request example:

```json
{
    "status": "terminee",
    "date_fin": "2026-08-05"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Maintenance mise a jour avec succes.",
    "data": {
        "id": 1,
        "status": "terminee",
        "date_fin": "2026-08-05"
    }
}
```

### DELETE `/api/v1/transport/maintenances-citerne/{maintenance_citerne}`

Response example:

```json
{
    "status": 1,
    "message": "Maintenance supprimee avec succes."
}
```

## 5.5 Affectation Citernes

Business rules:

- If `status` is `en_cours`, an employee cannot have another affectation in progress (422).
- If `status` is `en_cours`, a citerne cannot have another affectation in progress (422).

### GET `/api/v1/transport/affectation-citernes`

Response example:

```json
{
    "status": 1,
    "message": "Liste des affectations de citerne chargee avec succes.",
    "data": [
        {
            "id": 2,
            "employee_id": 10,
            "citerne_id": 1,
            "date_affectation": "2026-07-29",
            "ville_depart": "Abidjan",
            "ville_destination": "Yamoussoukro",
            "status": "en_cours"
        }
    ]
}
```

### POST `/api/v1/transport/affectation-citernes`

Request example:

```json
{
    "employee_id": 10,
    "citerne_id": 1,
    "date_affectation": "2026-07-29",
    "ville_depart": "Abidjan",
    "ville_destination": "Yamoussoukro",
    "status": "en_cours"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Affectation de citerne creee avec succes.",
    "data": {
        "id": 2,
        "employee_id": 10,
        "citerne_id": 1,
        "date_affectation": "2026-07-29",
        "ville_depart": "Abidjan",
        "ville_destination": "Yamoussoukro",
        "status": "en_cours"
    }
}
```

### GET `/api/v1/transport/affectation-citernes/{affectation_citerne}`

Response example:

```json
{
    "status": 1,
    "message": "Affectation de citerne chargee avec succes.",
    "data": {
        "id": 2,
        "employee_id": 10,
        "citerne_id": 1,
        "date_affectation": "2026-07-29",
        "status": "en_cours"
    }
}
```

### PUT/PATCH `/api/v1/transport/affectation-citernes/{affectation_citerne}`

Request example:

```json
{
    "status": "terminer",
    "date_retour_reel": "2026-08-01"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Affectation de citerne mise a jour avec succes.",
    "data": {
        "id": 2,
        "status": "terminer",
        "date_retour_reel": "2026-08-01"
    }
}
```

### DELETE `/api/v1/transport/affectation-citernes/{affectation_citerne}`

Response example:

```json
{
    "status": 1,
    "message": "Affectation de citerne supprimee avec succes."
}
```

## 5.6 Affectation Citerne Depenses

File: `facture` (optional) must be `pdf,jpg,jpeg,png,gif` max 5MB.

### GET `/api/v1/transport/affectation-citerne-depenses`

Response example:

```json
{
    "status": 1,
    "message": "Liste des depenses chargee avec succes.",
    "data": [
        {
            "id": 1,
            "affectation_citerne_id": 2,
            "libelle": "Peage",
            "montant": 5000,
            "date_depense": "2026-07-29",
            "facture": null
        }
    ]
}
```

### POST `/api/v1/transport/affectation-citerne-depenses`

- Content-Type: `multipart/form-data` if `facture` is provided

Request example:

```json
{
    "affectation_citerne_id": 2,
    "libelle": "Peage",
    "description": "Peage autoroute",
    "montant": 5000,
    "date_depense": "2026-07-29"
}
```

Response example:

```json
{
    "status": 1,
    "message": "Depense creee avec succes.",
    "data": {
        "id": 1,
        "affectation_citerne_id": 2,
        "libelle": "Peage",
        "montant": 5000,
        "date_depense": "2026-07-29",
        "facture": null
    }
}
```

### GET `/api/v1/transport/affectation-citerne-depenses/{affectation_citerne_depense}`

Response example:

```json
{
    "status": 1,
    "message": "Depense chargee avec succes.",
    "data": {
        "id": 1,
        "affectation_citerne_id": 2,
        "libelle": "Peage",
        "montant": 5000,
        "date_depense": "2026-07-29"
    }
}
```

### PUT/PATCH `/api/v1/transport/affectation-citerne-depenses/{affectation_citerne_depense}`

Request example:

```json
{
    "montant": 6000
}
```

Response example:

```json
{
    "status": 1,
    "message": "Depense mise a jour avec succes.",
    "data": {
        "id": 1,
        "montant": 6000
    }
}
```

### DELETE `/api/v1/transport/affectation-citerne-depenses/{affectation_citerne_depense}`

Response example:

```json
{
    "status": 1,
    "message": "Depense supprimee avec succes."
}
```

## 5.7 Approvisions

Access (enforced):

- `super_admin` / `admin`: global (can create for any station)
- active `gerant_station`: restricted to their station (`station_id` forced)

Nested create: `POST /approvisions` supports creating `appro_compartiment_jauges[]` in the same request.

Resource behavior:

- If `total_litre_reel` is 0, API returns `sum(appro_compartiment_jauges.volume_reel)` as `total_litre_reel`.
- `ecart = total_litre_theorique - total_litre_reel`.

### GET `/api/v1/transport/approvisions`

Response example:

```json
{
    "status": 1,
    "message": "Liste des approvisions chargee avec succes.",
    "data": [
        {
            "id": 1,
            "reference": "APP-2026-001",
            "station_id": 3,
            "affectation_citerne_id": 2,
            "date_approvision": "2026-07-29",
            "total_litre_theorique": 10000,
            "total_litre_reel": 9950,
            "ecart": 50
        }
    ]
}
```

### POST `/api/v1/transport/approvisions`

Request example:

```json
{
    "reference": "APP-2026-001",
    "station_id": 3,
    "affectation_citerne_id": 2,
    "date_approvision": "2026-07-29",
    "total_litre_theorique": 10000,
    "total_litre_reel": 0,
    "appro_compartiment_jauges": [
        {
            "hydrocarbure_id": 1,
            "num_compartiment": 1,
            "valeur_jauge": 2.1,
            "volume_reel": 5000,
            "volume_theorique": 5000
        },
        {
            "hydrocarbure_id": 1,
            "num_compartiment": 2,
            "valeur_jauge": 2.1,
            "volume_reel": 4950,
            "volume_theorique": 5000
        }
    ]
}
```

Response example:

```json
{
    "status": 1,
    "message": "Approvision cree avec succes.",
    "data": {
        "id": 1,
        "reference": "APP-2026-001",
        "station_id": 3,
        "total_litre_theorique": 10000,
        "total_litre_reel": 9950,
        "ecart": 50
    }
}
```

### GET `/api/v1/transport/approvisions/{approvision}`

Response example:

```json
{
    "status": 1,
    "message": "Approvision charge avec succes.",
    "data": {
        "id": 1,
        "reference": "APP-2026-001",
        "station_id": 3,
        "total_litre_theorique": 10000,
        "total_litre_reel": 9950,
        "ecart": 50
    }
}
```

### PUT/PATCH `/api/v1/transport/approvisions/{approvision}`

Request example:

```json
{
    "total_litre_theorique": 10100
}
```

Response example:

```json
{
    "status": 1,
    "message": "Approvision mis a jour avec succes.",
    "data": {
        "id": 1,
        "total_litre_theorique": 10100,
        "ecart": 150
    }
}
```

### DELETE `/api/v1/transport/approvisions/{approvision}`

Response example:

```json
{
    "status": 1,
    "message": "Approvision supprime avec succes."
}
```

## 5.8 Appro Compartiment Jauges

Note for frontend: send `volume_theorique` in payload; the backend stores it in `volueme_theorique` (internal column naming), but the API response returns it as `volume_theorique`.

### GET `/api/v1/transport/appro-compartiment-jauges`

Response example:

```json
{
    "status": 1,
    "message": "Liste des jauges chargee avec succes.",
    "data": [
        {
            "id": 1,
            "approvision_id": 1,
            "hydrocarbure_id": 1,
            "num_compartiment": 1,
            "volume_reel": 5000,
            "volume_theorique": 5000,
            "ecart": 0
        }
    ]
}
```

### POST `/api/v1/transport/appro-compartiment-jauges`

Request example:

```json
{
    "approvision_id": 1,
    "hydrocarbure_id": 1,
    "num_compartiment": 1,
    "valeur_jauge": 2.1,
    "volume_reel": 5000,
    "volume_theorique": 5000
}
```

Response example:

```json
{
    "status": 1,
    "message": "Jauge creee avec succes.",
    "data": {
        "id": 1,
        "approvision_id": 1,
        "hydrocarbure_id": 1,
        "num_compartiment": 1,
        "volume_reel": 5000,
        "volume_theorique": 5000,
        "ecart": 0
    }
}
```

### GET `/api/v1/transport/appro-compartiment-jauges/{appro_compartiment_jauge}`

Response example:

```json
{
    "status": 1,
    "message": "Jauge chargee avec succes.",
    "data": {
        "id": 1,
        "approvision_id": 1,
        "hydrocarbure_id": 1,
        "num_compartiment": 1,
        "volume_reel": 5000,
        "volume_theorique": 5000,
        "ecart": 0
    }
}
```

### PUT/PATCH `/api/v1/transport/appro-compartiment-jauges/{appro_compartiment_jauge}`

Request example:

```json
{
    "volume_reel": 4980
}
```

Response example:

```json
{
    "status": 1,
    "message": "Jauge mise a jour avec succes.",
    "data": {
        "id": 1,
        "volume_reel": 4980,
        "ecart": 20
    }
}
```

### DELETE `/api/v1/transport/appro-compartiment-jauges/{appro_compartiment_jauge}`

Response example:

```json
{
    "status": 1,
    "message": "Jauge supprimee avec succes."
}
```

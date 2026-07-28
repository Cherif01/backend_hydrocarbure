# API Documentation

## Overview

This document covers the currently implemented API endpoints in the project.

- Base URL: `/api`
- Auth strategy: `Bearer` token via Laravel Sanctum
- Main API prefix: `/v1`
- Response format: JSON

This documentation is written for frontend integration and follows the actual implementation flow:

1. Register or log in
2. Store the returned bearer token
3. Call protected endpoints with `Authorization: Bearer <token>`
4. If the user is assigned to a module requiring an access code, call `verify-access-code`
5. Continue using the protected module endpoints

## Response Envelope

Most endpoints use one of the following response shapes.

### Success

```json
{
    "status": 1,
    "message": "Operation successful",
    "data": {}
}
```

### Success With Token

```json
{
    "status": 1,
    "data": {},
    "token": "1|example_token_here",
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

### Public JSON Endpoints

```http
Content-Type: application/json
Accept: application/json
```

### Protected Endpoints

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <token>
```

### File Upload Endpoints

Use `multipart/form-data` when sending `avatar` or `image`.

## Authentication Flow

### Frontend Flow

1. Call `POST /api/v1/auth/login`
2. Save the returned `token`
3. Send the token on all protected requests
4. If the logged-in user has a protected module assignment, call `POST /api/v1/admin/verify-access-code`
5. If the user belongs to the `gerant_station` module, the backend checks the active station affectation internally

### Important Note For `gerant_station`

When a user verifies the access code for a module and has an active `gerant_station` assignment:

- the backend checks that the user has an active station affectation
- if none exists, access code verification fails
- the resolved station context is stored server-side for later use
- no special extra payload is currently required from the frontend

## Data Models Used By Frontend

### User Resource

```json
{
    "id": 1,
    "name": "John Doe",
    "telephone": "0700000000",
    "email": "john@example.com",
    "avatar_url": "https://example.com/avatar.png",
    "role": "admin",
    "user_modules": [
        {
            "id": 2,
            "module_id": 1,
            "name": "gerant_station",
            "description": "Gestion des stations",
            "code_acces": "123456",
            "is_active": true
        }
    ],
    "affectations": [
        {
            "id": 1,
            "station_id": 3,
            "is_active": true,
            "station": {
                "reference": "STAAB12CD",
                "libelle": "Station Bonaberi",
                "description": "Station principale",
                "addresse": "Bonaberi",
                "ville": "Douala"
            }
        }
    ],
    "created_at": "27-07-2026 21:10:00",
    "updated_at": "27-07-2026 21:20:00"
}
```

### Station Resource

```json
{
    "id": 3,
    "reference": "STAAB12CD",
    "libelle": "Station Bonaberi",
    "description": "Station principale",
    "adresse": "Bonaberi",
    "ville": "Douala",
    "longitude": 9.7,
    "latitude": 4.05,
    "image": "station-image.png",
    "image_url": "https://example.com/station-image.png",
    "is_active": true,
    "created_by": {
        "id": 1,
        "name": "Admin User",
        "telephone": "0700000000"
    },
    "updated_by": {
        "id": 2,
        "name": "Manager User",
        "telephone": "0711111111"
    },
    "created_at": "27-07-2026 21:00:00",
    "updated_at": "27-07-2026 21:30:00"
}
```

### Affectation Station Resource

```json
{
    "id": 1,
    "station_id": 3,
    "user_id": 7,
    "is_active": true,
    "station": {
        "id": 3,
        "reference": "STAAB12CD",
        "libelle": "Station Bonaberi",
        "description": "Station principale",
        "adresse": "Bonaberi",
        "ville": "Douala"
    },
    "user": {
        "id": 7,
        "name": "Gerant Station",
        "telephone": "0712345678",
        "email": "gerant@example.com"
    },
    "created_by": {
        "id": 1,
        "name": "Admin User"
    },
    "updated_by": {
        "id": 1,
        "name": "Admin User"
    },
    "created_at": "27-07-2026 21:35:00",
    "updated_at": "27-07-2026 21:40:00"
}
```

### Hydrocarbure Resource

Hydrocarbon prices are returned as strings with exactly two decimal places. This
keeps monetary values stable and avoids floating-point rounding in JSON clients.

```json
{
    "id": 1,
    "libelle": "Essence",
    "prix_achat": "800.00",
    "prix_vente": "850.00",
    "created_by": {
        "id": 1,
        "name": "Admin User",
        "telephone": "0700000000"
    },
    "updated_by": {
        "id": 2,
        "name": "Admin User",
        "telephone": "0711111111"
    },
    "created_at": "28-07-2026 14:00:00",
    "updated_at": "28-07-2026 14:30:00"
}
```

### Pompe Resource

```json
{
    "id": 1,
    "reference": "POM01",
    "station_id": 3,
    "libelle": "Pompe principale",
    "description": "Pompe de la piste 1",
    "is_active": true,
    "station": {
        "id": 3,
        "reference": "STAAB12CD",
        "libelle": "Station Bonaberi",
        "description": "Station principale",
        "adresse": "Bonaberi",
        "ville": "Douala"
    },
    "created_by": {
        "id": 1,
        "name": "Admin User",
        "telephone": "0700000000"
    },
    "updated_by": {
        "id": 2,
        "name": "Manager User",
        "telephone": "0711111111"
    },
    "created_at": "28-07-2026 14:00:00",
    "updated_at": "28-07-2026 14:30:00"
}
```

### Pistolet Resource

```json
{
    "id": 1,
    "pompe_id": 1,
    "hydrocarbure_id": 1,
    "libelle": "Pistolet essence 1",
    "is_active": true,
    "pompe": {
        "id": 1,
        "reference": "POM01",
        "station_id": 3,
        "libelle": "Pompe principale",
        "station": {
            "id": 3,
            "reference": "STAAB12CD",
            "libelle": "Station Bonaberi"
        }
    },
    "hydrocarbure": {
        "id": 1,
        "libelle": "Essence",
        "prix_achat": "800.00",
        "prix_vente": "850.00"
    },
    "created_by": {
        "id": 1,
        "name": "Admin User",
        "telephone": "0700000000"
    },
    "updated_by": {
        "id": 2,
        "name": "Manager User",
        "telephone": "0711111111"
    },
    "created_at": "28-07-2026 14:00:00",
    "updated_at": "28-07-2026 14:30:00"
}
```

## Authentication Endpoints

### Register

- Method: `POST`
- URL: `/api/v1/auth/register`
- Auth: No
- Content-Type: `multipart/form-data` if sending avatar, otherwise `application/json`

### Payload

```json
{
    "name": "John Doe",
    "telephone": "0700000000",
    "email": "john@example.com",
    "role": "user",
    "password": "secret123",
    "password_confirmation": "secret123"
}
```

### Optional Fields

- `email`
- `role`
- `avatar`

### Response Example

```json
{
    "status": 1,
    "data": {
        "id": 1,
        "name": "John Doe",
        "telephone": "0700000000",
        "email": "john@example.com",
        "avatar_url": "https://ui-avatars.com/api/?name=J+D&color=7F9CF5&background=EBF4FF",
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "27-07-2026 20:00:00",
        "updated_at": "27-07-2026 20:00:00"
    },
    "token": "1|register_token_example",
    "message": "Utilisateur créé avec succès."
}
```

### Login

- Method: `POST`
- URL: `/api/v1/auth/login`
- Auth: No
- Content-Type: `application/json`

### Payload

```json
{
    "telephone": "0700000000",
    "password": "secret123"
}
```

### Response Example

```json
{
    "status": 1,
    "data": {
        "id": 1,
        "name": "John Doe",
        "telephone": "0700000000",
        "email": "john@example.com",
        "avatar_url": "https://ui-avatars.com/api/?name=J+D&color=7F9CF5&background=EBF4FF",
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "27-07-2026 20:00:00",
        "updated_at": "27-07-2026 20:00:00"
    },
    "token": "1|login_token_example",
    "message": "Utilisateur connecté avec succès."
}
```

### Logout

- Method: `POST`
- URL: `/api/v1/auth/logout`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Utilisateur deconnecté avec succès."
}
```

### Get Authenticated User

- Method: `GET`
- URL: `/api/v1/auth/me`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Utilisateur récupéré avec succès.",
    "data": {
        "id": 1,
        "name": "John Doe",
        "telephone": "0700000000",
        "email": "john@example.com",
        "avatar_url": "https://ui-avatars.com/api/?name=J+D&color=7F9CF5&background=EBF4FF",
        "role": "admin",
        "user_modules": [
            {
                "id": 2,
                "module_id": 1,
                "name": "gerant_station",
                "description": "Gestion des stations",
                "code_acces": "123456",
                "is_active": true
            }
        ],
        "affectations": [
            {
                "id": 1,
                "station_id": 3,
                "is_active": true,
                "station": {
                    "reference": "STAAB12CD",
                    "libelle": "Station Bonaberi",
                    "description": "Station principale",
                    "addresse": "Bonaberi",
                    "ville": "Douala"
                }
            }
        ],
        "created_at": "27-07-2026 20:00:00",
        "updated_at": "27-07-2026 20:00:00"
    }
}
```

### Update Profile

- Method: `PUT`
- URL: `/api/v1/auth/me`
- Auth: Yes
- Content-Type: `multipart/form-data` if sending avatar, otherwise `application/json`

### Payload Example

```json
{
    "name": "John Updated",
    "telephone": "0700000001",
    "email": "john.updated@example.com"
}
```

### Response Example

```json
{
    "status": 1,
    "message": "Profil mis à jour avec succès.",
    "data": {
        "id": 1,
        "name": "John Updated",
        "telephone": "0700000001",
        "email": "john.updated@example.com",
        "avatar_url": "https://example.com/new-avatar.png",
        "role": "admin",
        "user_modules": [],
        "affectations": [],
        "created_at": "27-07-2026 20:00:00",
        "updated_at": "27-07-2026 20:10:00"
    }
}
```

### Update Password

- Method: `PUT`
- URL: `/api/v1/auth/password`
- Auth: Yes

### Payload

```json
{
    "current_password": "old-secret",
    "new_password": "new-secret",
    "new_password_confirmation": "new-secret"
}
```

### Response Example

```json
{
    "status": 1,
    "message": "Mot de passe mis à jour avec succès.",
    "data": {
        "id": 1,
        "name": "John Doe",
        "telephone": "0700000000",
        "email": "john@example.com",
        "avatar_url": "https://ui-avatars.com/api/?name=J+D&color=7F9CF5&background=EBF4FF",
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "27-07-2026 20:00:00",
        "updated_at": "27-07-2026 20:20:00"
    }
}
```

## Administration Endpoints

### Get Users

- Method: `GET`
- URL: `/api/v1/admin/users`
- Auth: Yes

### Response Example

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
            "avatar_url": "https://ui-avatars.com/api/?name=G+S&color=7F9CF5&background=EBF4FF",
            "role": "user",
            "user_modules": [
                {
                    "id": 3,
                    "module_id": 2,
                    "name": "gerant_station",
                    "description": "Gestion station",
                    "code_acces": "123456",
                    "is_active": true
                }
            ],
            "affectations": [
                {
                    "id": 5,
                    "station_id": 3,
                    "is_active": true,
                    "station": {
                        "reference": "STAAB12CD",
                        "libelle": "Station Bonaberi",
                        "description": "Station principale",
                        "addresse": "Bonaberi",
                        "ville": "Douala"
                    }
                }
            ],
            "created_at": "27-07-2026 20:00:00",
            "updated_at": "27-07-2026 20:00:00"
        }
    ]
}
```

### Switch User Status

- Method: `PATCH`
- URL: `/api/v1/admin/switch-status/{user_id}`
- Auth: Yes
- Intended access: `super_admin`

### Response Example

```json
{
    "status": 1,
    "message": "Statut de l'utilisateur changé avec succès.",
    "data": {
        "id": 7,
        "name": "Gerant Station",
        "telephone": "0712345678",
        "email": "gerant@example.com",
        "avatar_url": "https://ui-avatars.com/api/?name=G+S&color=7F9CF5&background=EBF4FF",
        "role": "user",
        "user_modules": [],
        "affectations": [],
        "created_at": "27-07-2026 20:00:00",
        "updated_at": "27-07-2026 20:30:00"
    }
}
```

## Module Endpoints

### List Modules

- Method: `GET`
- URL: `/api/v1/admin/modules`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Liste des modules chargée avec succès",
    "data": [
        {
            "id": 1,
            "name": "gerant_station",
            "description": "Gestion des stations",
            "is_active": true,
            "created_at": "2026-07-27T20:00:00.000000Z",
            "updated_at": "2026-07-27T20:00:00.000000Z"
        }
    ]
}
```

### Create Module

- Method: `POST`
- URL: `/api/v1/admin/modules`
- Auth: Yes

### Payload

```json
{
    "name": "gerant_station",
    "description": "Gestion des stations"
}
```

### Response Example

```json
{
    "status": 1,
    "message": "Module créé avec succès",
    "data": {
        "id": 1,
        "name": "gerant_station",
        "description": "Gestion des stations",
        "is_active": true,
        "created_at": "2026-07-27T20:00:00.000000Z",
        "updated_at": "2026-07-27T20:00:00.000000Z"
    }
}
```

### Get One Module

- Method: `GET`
- URL: `/api/v1/admin/modules/{module}`
- Auth: Yes

### Update Module

- Method: `PUT` or `PATCH`
- URL: `/api/v1/admin/modules/{module}`
- Auth: Yes

### Payload

```json
{
    "name": "gerant_station",
    "description": "Gestion des stations et affectations"
}
```

### Delete Module

- Method: `DELETE`
- URL: `/api/v1/admin/modules/{module}`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Module supprimé avec succès",
    "data": {
        "id": 1,
        "name": "gerant_station",
        "description": "Gestion des stations"
    }
}
```

## User Module Endpoints

### List User Modules

- Method: `GET`
- URL: `/api/v1/admin/user-modules`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Listes des affectations des modules aux utilisateurs",
    "data": [
        {
            "id": 3,
            "user_id": 7,
            "module_id": 2,
            "code_acces": "123456",
            "is_active": true,
            "user": {
                "id": 7,
                "name": "Gerant Station"
            },
            "module": {
                "id": 2,
                "name": "gerant_station"
            }
        }
    ]
}
```

### Create User Module

- Method: `POST`
- URL: `/api/v1/admin/user-modules`
- Auth: Yes

### Payload

```json
{
    "module_id": 2,
    "user_id": 7
}
```

### Notes

- `code_acces` is generated automatically by the backend
- duplicate user/module assignments are rejected

### Response Example

```json
{
    "status": 1,
    "message": "Affectation du module aux utilisateurs",
    "data": {
        "id": 3,
        "module_id": 2,
        "user_id": 7,
        "created_by": 1,
        "code_acces": "123456",
        "is_active": true,
        "created_at": "2026-07-27T20:40:00.000000Z",
        "updated_at": "2026-07-27T20:40:00.000000Z"
    }
}
```

### Get One User Module

- Method: `GET`
- URL: `/api/v1/admin/user-modules/{user_module}`
- Auth: Yes

### Update User Module

- Method: `PUT` or `PATCH`
- URL: `/api/v1/admin/user-modules/{user_module}`
- Auth: Yes

### Payload

```json
{
    "module_id": 2,
    "user_id": 7
}
```

### Delete User Module

- Method: `DELETE`
- URL: `/api/v1/admin/user-modules/{user_module}`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Suppression de l'affectation du module aux utilisateurs"
}
```

### Switch User Module Status

- Method: `PATCH`
- URL: `/api/v1/admin/switch-status/{user_module_id}`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Changement de statut de l'affectation du module aux utilisateurs",
    "data": {
        "id": 3,
        "module_id": 2,
        "user_id": 7,
        "code_acces": "123456",
        "is_active": false
    }
}
```

### Send Access Code

- Method: `POST`
- URL: `/api/v1/admin/send-access-code/{user_module_id}`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Envoi de code d'accès",
    "data": {
        "id": 3,
        "module_id": 2,
        "user_id": 7,
        "code_acces": "123456",
        "is_active": true
    }
}
```

### Verify Access Code

- Method: `POST`
- URL: `/api/v1/admin/verify-access-code`
- Auth: Yes

### Payload

```json
{
    "code_acces": "123456"
}
```

### Frontend Behavior

- call this after login when access to a protected assigned module is required
- if the user belongs to `gerant_station`, backend checks that the user has an active station affectation
- if no active station affectation exists, this endpoint returns an error
- if everything is valid, the assigned module is returned

### Success Response Example

```json
{
    "status": 1,
    "message": "Vérification de code réussie",
    "data": {
        "id": 3,
        "user_id": 7,
        "module_id": 2,
        "code_acces": "123456",
        "is_active": true,
        "created_by": 1,
        "updated_by": null,
        "created_at": "2026-07-27T20:40:00.000000Z",
        "updated_at": "2026-07-27T20:40:00.000000Z",
        "module": {
            "id": 2,
            "name": "gerant_station",
            "description": "Gestion station",
            "is_active": true
        }
    }
}
```

### Error Response Example

```json
{
    "status": 0,
    "message": "Vous n'avez pas été affecté à une station",
    "error": []
}
```

## Station Endpoints

### List Stations

- Method: `GET`
- URL: `/api/v1/gestions/stations`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Liste des stations chargee avec succes.",
    "data": [
        {
            "id": 3,
            "reference": "STAAB12CD",
            "libelle": "Station Bonaberi",
            "description": "Station principale",
            "adresse": "Bonaberi",
            "ville": "Douala",
            "longitude": 9.7,
            "latitude": 4.05,
            "image": "station-image.png",
            "image_url": "https://example.com/station-image.png",
            "is_active": true,
            "created_by": {
                "id": 1,
                "name": "Admin User",
                "telephone": "0700000000"
            },
            "updated_by": {
                "id": 1,
                "name": "Admin User",
                "telephone": "0700000000"
            },
            "created_at": "27-07-2026 21:00:00",
            "updated_at": "27-07-2026 21:00:00"
        }
    ]
}
```

### Create Station

- Method: `POST`
- URL: `/api/v1/gestions/stations`
- Auth: Yes
- Content-Type: `multipart/form-data` if sending image

### Payload Example

```json
{
    "reference": "STAAB12CD",
    "libelle": "Station Bonaberi",
    "description": "Station principale",
    "adresse": "Bonaberi",
    "ville": "Douala",
    "longitude": 9.7,
    "latitude": 4.05,
    "is_active": true
}
```

### Notes

- `libelle` is required
- `reference` is optional
- if `reference` is omitted, backend auto-generates a unique one
- `image` is optional

### Response Example

```json
{
    "status": 1,
    "message": "Station creee avec succes.",
    "data": {
        "id": 3,
        "reference": "STAAB12CD",
        "libelle": "Station Bonaberi",
        "description": "Station principale",
        "adresse": "Bonaberi",
        "ville": "Douala",
        "longitude": 9.7,
        "latitude": 4.05,
        "image": "station-image.png",
        "image_url": "https://example.com/station-image.png",
        "is_active": true,
        "created_by": {
            "id": 1,
            "name": "Admin User",
            "telephone": "0700000000"
        },
        "updated_by": {
            "id": null,
            "name": null,
            "telephone": null
        },
        "created_at": "27-07-2026 21:00:00",
        "updated_at": "27-07-2026 21:00:00"
    }
}
```

### Get One Station

- Method: `GET`
- URL: `/api/v1/gestions/stations/{station}`
- Auth: Yes

### Update Station

- Method: `PUT` or `PATCH`
- URL: `/api/v1/gestions/stations/{station}`
- Auth: Yes
- Content-Type: `multipart/form-data` if sending image

### Payload Example

```json
{
    "libelle": "Station Bonaberi Updated",
    "description": "Station principale mise a jour",
    "adresse": "Bonaberi",
    "ville": "Douala",
    "longitude": 9.71,
    "latitude": 4.06,
    "is_active": true
}
```

### Notes

- if `reference` is omitted on update, the old reference is kept
- if a new image is sent, the old image is replaced

### Delete Station

- Method: `DELETE`
- URL: `/api/v1/gestions/stations/{station}`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Station supprimee avec succes."
}
```

### Switch Station Status

- Method: `PATCH`
- URL: `/api/v1/gestions/stations/{station}/switch`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Statut de la station change avec succes.",
    "data": {
        "id": 3,
        "reference": "STAAB12CD",
        "libelle": "Station Bonaberi",
        "description": "Station principale",
        "adresse": "Bonaberi",
        "ville": "Douala",
        "longitude": 9.7,
        "latitude": 4.05,
        "image": "station-image.png",
        "image_url": "https://example.com/station-image.png",
        "is_active": false,
        "created_by": {
            "id": 1,
            "name": "Admin User",
            "telephone": "0700000000"
        },
        "updated_by": {
            "id": 1,
            "name": "Admin User",
            "telephone": "0700000000"
        },
        "created_at": "27-07-2026 21:00:00",
        "updated_at": "27-07-2026 21:15:00"
    }
}
```

## Affectation Station Endpoints

### List Affectation Stations

- Method: `GET`
- URL: `/api/v1/gestions/affectation-stations`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Liste des affectations de stations chargee avec succes.",
    "data": [
        {
            "id": 1,
            "station_id": 3,
            "user_id": 7,
            "is_active": true,
            "station": {
                "id": 3,
                "reference": "STAAB12CD",
                "libelle": "Station Bonaberi",
                "description": "Station principale",
                "adresse": "Bonaberi",
                "ville": "Douala"
            },
            "user": {
                "id": 7,
                "name": "Gerant Station",
                "telephone": "0712345678",
                "email": "gerant@example.com"
            },
            "created_by": {
                "id": 1,
                "name": "Admin User"
            },
            "updated_by": {
                "id": null,
                "name": null
            },
            "created_at": "27-07-2026 21:35:00",
            "updated_at": "27-07-2026 21:35:00"
        }
    ]
}
```

### Create Affectation Station

- Method: `POST`
- URL: `/api/v1/gestions/affectation-stations`
- Auth: Yes

### Payload

```json
{
    "station_id": 3,
    "user_id": 7
}
```

### Notes

- one user cannot have two active station affectations at the same time
- backend sets `created_by` automatically

### Success Response Example

```json
{
    "status": 1,
    "message": "Affectation de station creee avec succes.",
    "data": {
        "id": 1,
        "station_id": 3,
        "user_id": 7,
        "is_active": true,
        "station": {
            "id": 3,
            "reference": "STAAB12CD",
            "libelle": "Station Bonaberi",
            "description": "Station principale",
            "adresse": "Bonaberi",
            "ville": "Douala"
        },
        "user": {
            "id": 7,
            "name": "Gerant Station",
            "telephone": "0712345678",
            "email": "gerant@example.com"
        },
        "created_by": {
            "id": 1,
            "name": "Admin User"
        },
        "updated_by": {
            "id": null,
            "name": null
        },
        "created_at": "27-07-2026 21:35:00",
        "updated_at": "27-07-2026 21:35:00"
    }
}
```

### Business Error Example

```json
{
    "status": 0,
    "message": "Cet utilisateur a deja une affectation de station active.",
    "error": []
}
```

### Get One Affectation Station

- Method: `GET`
- URL: `/api/v1/gestions/affectation-stations/{affectation_station}`
- Auth: Yes

### Update Affectation Station

- Method: `PUT` or `PATCH`
- URL: `/api/v1/gestions/affectation-stations/{affectation_station}`
- Auth: Yes

### Payload

```json
{
    "station_id": 4,
    "user_id": 7
}
```

### Delete Affectation Station

- Method: `DELETE`
- URL: `/api/v1/gestions/affectation-stations/{affectation_station}`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Affectation de station supprimee avec succes."
}
```

### Switch Affectation Station Status

- Method: `PATCH`
- URL: `/api/v1/gestions/affectation-stations/{affectation_station}/switch-status`
- Auth: Yes

### Response Example

```json
{
    "status": 1,
    "message": "Statut de l'affectation de station change avec succes.",
    "data": {
        "id": 1,
        "station_id": 3,
        "user_id": 7,
        "is_active": false,
        "station": {
            "id": 3,
            "reference": "STAAB12CD",
            "libelle": "Station Bonaberi",
            "description": "Station principale",
            "adresse": "Bonaberi",
            "ville": "Douala"
        },
        "user": {
            "id": 7,
            "name": "Gerant Station",
            "telephone": "0712345678",
            "email": "gerant@example.com"
        },
        "created_by": {
            "id": 1,
            "name": "Admin User"
        },
        "updated_by": {
            "id": 1,
            "name": "Admin User"
        },
        "created_at": "27-07-2026 21:35:00",
        "updated_at": "27-07-2026 21:45:00"
    }
}
```

## Hydrocarbure Endpoints

Hydrocarbons and their prices are global. They are never filtered by station.

### Access

- `admin` and `super_admin`: read, create and update
- active `gerant_station` with an active station assignment: read only
- `gerant_station` without an active station assignment: `403`
- ordinary authenticated user: `403`
- unauthenticated user: `401`

### Available Routes

| Method | URL | Access |
| --- | --- | --- |
| `GET` | `/api/v1/gestions/hydrocarbures` | Admin or assigned manager |
| `POST` | `/api/v1/gestions/hydrocarbures` | Admin only |
| `GET` | `/api/v1/gestions/hydrocarbures/{hydrocarbure}` | Admin or assigned manager |
| `PUT`, `PATCH` | `/api/v1/gestions/hydrocarbures/{hydrocarbure}` | Admin only |

### Create Payload (`POST`)

```json
{
    "libelle": "Essence",
    "prix_achat": 800,
    "prix_vente": 850
}
```

All three fields are required.

### Complete Update Payload (`PUT`)

```json
{
    "libelle": "Essence super",
    "prix_achat": 810,
    "prix_vente": 875
}
```

All three fields are required.

### Partial Update Payload (`PATCH`)

Only the fields to update are required. Fields that are not sent keep their
current values.

```json
{
    "prix_vente": 875
}
```

There is no `DELETE` route for hydrocarbons.

## Pompe Endpoints

A pump belongs to one station. Its reference is globally unique and is generated
as `POM01`, `POM02`, and so on when omitted.

### Access And Station Scope

- `admin` and `super_admin`: global access and free station selection
- active `gerant_station`: access limited to the active assigned station
- a manager-supplied `station_id` is ignored and replaced by the scoped station
- direct access to a pump outside the manager station returns `404`
- missing active station assignment returns `403`
- ordinary authenticated user returns `403`
- unauthenticated user returns `401`

### Available Routes

| Method | URL |
| --- | --- |
| `GET` | `/api/v1/gestions/pompes` |
| `POST` | `/api/v1/gestions/pompes` |
| `GET` | `/api/v1/gestions/pompes/{pompe}` |
| `PUT`, `PATCH` | `/api/v1/gestions/pompes/{pompe}` |

### Create Payload (`POST`)

```json
{
    "station_id": 3,
    "libelle": "Pompe principale",
    "description": "Pompe de la piste 1",
    "is_active": true
}
```

`reference` is optional. When it is absent, null, or empty, the backend generates
the next sequential reference. `station_id` is required for administrators and
may be omitted by a station-scoped manager.

### Complete Update Payload (`PUT`)

```json
{
    "station_id": 4,
    "libelle": "Pompe principale actualisee",
    "description": "Pompe de la piste 2",
    "is_active": true
}
```

`libelle` and, for administrators, `station_id` are required. `reference`,
`description`, and `is_active` remain optional. An absent, null, or empty
`reference` keeps the current reference.

### Partial Update Payload (`PATCH`)

Only the fields to update are required. Fields that are not sent keep their
current values.

```json
{
    "description": "Maintenance terminee",
    "is_active": false
}
```

For a station-scoped manager, the backend always replaces any supplied
`station_id` with the manager station, for both creation and update. There is
intentionally no `DELETE` route for pumps; use `is_active` to stop using a pump.

## Pistolet Endpoints

A nozzle belongs to a pump and a global hydrocarbon. Its station is resolved
through `pistolet -> pompe -> station`.

### Access And Station Scope

- `admin` and `super_admin`: global access
- active `gerant_station`: access only through pumps belonging to the assigned station
- a pump outside the manager station is rejected with `404` on create or update
- a pistolet outside the manager station returns `404`
- missing active station assignment returns `403`
- ordinary authenticated user returns `403`
- unauthenticated user returns `401`

### Available Routes

| Method | URL |
| --- | --- |
| `GET` | `/api/v1/gestions/pistolets` |
| `POST` | `/api/v1/gestions/pistolets` |
| `GET` | `/api/v1/gestions/pistolets/{pistolet}` |
| `PUT`, `PATCH` | `/api/v1/gestions/pistolets/{pistolet}` |

### Create Payload (`POST`)

```json
{
    "pompe_id": 1,
    "hydrocarbure_id": 1,
    "libelle": "Pistolet essence 1",
    "is_active": true
}
```

`pompe_id`, `hydrocarbure_id`, and `libelle` are required. `is_active` is
optional and uses the database default when it is absent.

### Complete Update Payload (`PUT`)

```json
{
    "pompe_id": 2,
    "hydrocarbure_id": 1,
    "libelle": "Pistolet essence principal",
    "is_active": true
}
```

`pompe_id`, `hydrocarbure_id`, and `libelle` are also required for a complete
update.

### Partial Update Payload (`PATCH`)

A partial update may contain a single field. Fields that are not sent keep
their current values.

```json
{
    "libelle": "Pistolet essence secondaire"
}
```

```json
{
    "is_active": false
}
```

```json
{
    "pompe_id": 3
}
```

When provided, `is_active` accepts only `true` or `false`, never `null`.
There is no `DELETE` route for pistolets; use `is_active: false` to deactivate
a pistolet.

## Validation Summary

### Register

- `name`: required, string, min 2, max 160
- `telephone`: required, unique, min 9, max 14
- `email`: optional, valid email, unique
- `role`: optional, one of `user`, `super_admin`, `admin`, `client`
- `avatar`: optional image, `png`, `jpg`, `jpeg`, max `2048 KB`
- `password`: required, min 6, confirmed

### Login

- `telephone`: required
- `password`: required

### Update Profile

- `name`, `telephone`, `email`, `avatar`: optional

### Update Password

- `current_password`: required
- `new_password`: required, confirmed

### Module

- `name`: required, unique
- `description`: optional

### User Module

- `module_id`: required, existing module id
- `user_id`: required, existing user id

### Station

- `libelle`: required
- `reference`: optional, unique
- `description`: optional
- `adresse`: optional
- `ville`: optional
- `longitude`: optional numeric
- `latitude`: optional numeric
- `image`: optional image
- `is_active`: optional boolean

### Affectation Station

- `station_id`: required, existing station id
- `user_id`: required, existing user id

### Hydrocarbure

- `libelle`: required string, max 255
- `prix_achat`: required numeric, minimum 0
- `prix_vente`: required numeric, minimum 0
- the label is not required to be unique
- no purchase/sale price comparison is currently applied
- all three fields are required for `POST` and `PUT`
- `PATCH` validates only the fields included in the request

### Pompe

- `reference`: optional string, max 255, globally unique
- `station_id`: required for administrators on `POST` and `PUT`, optional on `PATCH`, existing station id
- `libelle`: required string on `POST` and `PUT`, optional on `PATCH`, max 255
- `description`: optional string
- `is_active`: optional non-null boolean
- an absent, null, or empty reference generates the next `POM<number>` on `POST`
- an absent, null, or empty reference preserves the current value on `PUT` and `PATCH`

### Pistolet

- `pompe_id`: required on `POST` and `PUT`, optional on `PATCH`, existing pump id, additionally checked against manager station scope
- `hydrocarbure_id`: required on `POST` and `PUT`, optional on `PATCH`, existing hydrocarbon id
- `libelle`: required on `POST` and `PUT`, optional on `PATCH`, string, max 255
- `is_active`: optional non-null boolean
- fields absent from a `PATCH` keep their current values

## Common Frontend Notes

- Always store the bearer token after login or register.
- For file upload fields, use `multipart/form-data`.
- `verify-access-code` is a second-level access validation for assigned modules.
- For users linked to `gerant_station`, an active station affectation is required.
- Some delete endpoints return only `status` and `message`.
- Hydrocarbures, pompes and pistolets do not expose a `DELETE` route.
- Hydrocarbon monetary values are serialized as strings with two decimal places.
- Dates can differ by endpoint:
    - some raw models return ISO timestamps
    - resources return formatted dates like `27-07-2026 21:35:00`

## Recommended Frontend Sequence

### Standard User

1. Login
2. Save token
3. Call `GET /api/v1/auth/me`
4. If needed, call `POST /api/v1/admin/verify-access-code`
5. Use allowed module endpoints

### `gerant_station` User

1. Login
2. Save token
3. Call `GET /api/v1/auth/me`
4. Call `POST /api/v1/admin/verify-access-code`
5. If successful, continue using station-related endpoints
6. If the backend returns `Vous n'avez pas été affecté à une station`, the user must receive an active station affectation before continuing

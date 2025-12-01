# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Pour utiliser cette API, vous devez d'abord vous authentifier :

1. **Se connecter** : `POST /api/v1/login`
```json
{
  "email": "admin@college-abc.com",
  "password": "password123"
}
```

2. **Utiliser le token** : Dans le header `Authorization: Bearer {TOKEN}`

👤 **Compte admin test** : `admin@college-abc.com` / `password123`

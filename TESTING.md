# Guide de test Postman — ThreadForge API

## 0. Pré-requis

Dans deux terminaux séparés, lancer :
```bash
php artisan serve --port=8001
php artisan queue:work --tries=2 --timeout=120
```

> ⚠️ Le worker doit tourner en permanence, sinon les jobs de génération ne seront pas traités.

---

## 1. Configurer l'environnement Postman

Créer un environnement avec ces variables :

| Variable | Valeur initiale |
|---|---|
| `base_url` | `http://127.0.0.1:8001` |
| `token` | (vide — sera rempli automatiquement) |

---

## 2. AUTHENTIFICATION (US1)

### 2.1 Inscription — `POST {{base_url}}/api/register`

**Headers :**
```
Content-Type: application/json
Accept: application/json
```

**Body (raw JSON) :**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123"
}
```

**Réponse attendue — `201 Created` :**
```json
{
  "data": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "created_at": "2026-06-25T12:00:00+00:00",
    "updated_at": "2026-06-25T12:00:00+00:00"
  },
  "token": "1|abcdef123456..."
}
```

**Tests ( onglet Tests ) :**
```javascript
let res = pm.response.json();
pm.test("Status 201", () => pm.response.to.have.status(201));
pm.test("Token retourné", () => pm.expect(res.token).to.be.a('string'));
pm.test("Pas de password dans la réponse", () => pm.expect(res.data).to.not.have.property('password'));
pm.environment.set("token", res.token);
```

> Le token est automatiquement sauvegardé dans la variable `{{token}}`.

---

### 2.2 Connexion — `POST {{base_url}}/api/login`

**Body (raw JSON) :**
```json
{
  "email": "jane@example.com",
  "password": "password123",
  "device_name": "Postman"
}
```

**Réponse attendue — `200 OK` :**
```json
{
  "data": { "id": 1, "name": "Jane Doe", ... },
  "token": "2|xyz789..."
}
```

**Tests :**
```javascript
let res = pm.response.json();
pm.test("Status 200", () => pm.response.to.have.status(200));
pm.environment.set("token", res.token);
```

---

### 2.3 Route protégée SANS token — `GET {{base_url}}/api/health`

**Headers :** (ne PAS mettre de Authorization)

**Réponse attendue — `401 Unauthorized` :**
```json
{ "message": "Unauthenticated." }
```

**Tests :**
```javascript
pm.test("401 sans token", () => pm.response.to.have.status(401));
```

---

### 2.4 Route protégée AVEC token — `GET {{base_url}}/api/health`

**Headers :**
```
Authorization: Bearer {{token}}
Accept: application/json
```

**Réponse attendue — `200 OK` :**
```json
{ "status": "ok" }
```

---

### 2.5 Déconnexion — `POST {{base_url}}/api/logout`

**Headers :**
```
Authorization: Bearer {{token}}
```

**Réponse attendue — `200 OK` :**
```json
{ "message": "Logged out" }
```

> Après logout, refaire un `GET /api/health` avec l'ancien token → doit retourner `401`.

---

## 3. CAMPAIGNS / BLUEPRINTS (US2-3)

> Tous les endpoints suivants nécessitent : `Authorization: Bearer {{token}}`

### 3.1 Créer un Blueprint — `POST {{base_url}}/api/campaigns`

**Body (raw JSON) :**
```json
{
  "name": "Tech Twitter Pro",
  "target_audience": "developer community on X",
  "tone": "professional but relaxed, witty",
  "max_length": 280,
  "max_hashtags": 1,
  "rules": [
    "No buzzwords like synergy or revolutionary",
    "Always cite the source or library",
    "One clear CTA at the end",
    "No emoji spam — max 2 emoji per post"
  ]
}
```

**Réponse attendue — `201 Created` :**
```json
{
  "data": {
    "id": 1,
    "name": "Tech Twitter Pro",
    "target_audience": "developer community on X",
    "tone": "professional but relaxed, witty",
    "max_length": 280,
    "max_hashtags": 1,
    "rules": ["No buzzwords...", "Always cite...", "One clear CTA...", "No emoji spam..."],
    "posts_count": 0,
    "created_at": "2026-06-25T12:00:00+00:00",
    "updated_at": "2026-06-25T12:00:00+00:00"
  }
}
```

**Tests :**
```javascript
let res = pm.response.json();
pm.test("Status 201", () => pm.response.to.have.status(201));
pm.test("Rules est un array", () => pm.expect(res.data.rules).to.be.an('array'));
pm.test("posts_count = 0", () => pm.expect(res.data.posts_count).to.eql(0));
pm.environment.set("campaign_id", res.data.id);
```

---

### 3.2 Validation — envoyer un body vide

**Body :** `{}`

**Réponse attendue — `422 Unprocessable Entity` :**
```json
{
  "message": "The given data was invalid.",
  "errors": { "name": ["The name field is required."] }
}
```

---

### 3.3 Lister les Blueprints — `GET {{base_url}}/api/campaigns`

**Réponse attendue — `200 OK` :**
```json
{
  "data": [
    { "id": 1, "name": "Tech Twitter Pro", ..., "posts_count": 2, ... }
  ]
}
```

---

### 3.4 Détail d'un Blueprint — `GET {{base_url}}/api/campaigns/1`

**Tests :**
```javascript
let res = pm.response.json();
pm.test("posts_count présent", () => pm.expect(res.data).to.have.property('posts_count'));
pm.test("Rules est un array PHP natif", () => pm.expect(res.data.rules).to.be.an('array'));
```

---

## 4. REPURPOSING — CONTENU ASYNCHRONE (US4-6)

### 4.1 Soumettre du contenu brut — `POST {{base_url}}/api/content/repurpose`

**Body (raw JSON) :**
```json
{
  "campaign_id": 1,
  "content": "# PHP 8.4 Property Hooks\n\nPHP 8.4 introduces property hooks — a clean way to define getters and setters inline without boilerplate methods.\n\n## What changed:\n- `public string $name { get; set; }` syntax\n- No more `__get()` / `__set()` magic methods needed\n- Works with readonly properties\n- Backed by real virtual properties\n\nSource: https://wiki.php.net/rfc/property-hooks",
  "source_type": "markdown"
}
```

**Réponse attendue — `202 Accepted` (immédiat, < 1 seconde) :**
```json
{
  "data": {
    "id": 1,
    "campaign_id": 1,
    "content": "# PHP 8.4 Property Hooks...",
    "source_type": "markdown",
    "status": "pending",
    "created_at": "2026-06-25T12:00:00+00:00",
    "updated_at": "2026-06-25T12:00:00+00:00"
  }
}
```

**Tests :**
```javascript
let res = pm.response.json();
pm.test("Status 202 Accepted", () => pm.response.to.have.status(202));
pm.test("Status = pending", () => pm.expect(res.data.status).to.eql('pending'));
pm.environment.set("raw_content_id", res.data.id);
```

> ⏳ Attendre 10-15 secondes que le queue worker traite le job.

---

### 4.2 Validation — contenu vide

**Body :**
```json
{ "campaign_id": 1, "content": "" }
```

**Réponse attendue — `422` :**
```json
{ "message": "...", "errors": { "content": ["The content field is required."] } }
```

---

### 4.3 Lister les posts générés — `GET {{base_url}}/api/posts`

**Après ~12 secondes, la réponse attendue — `200 OK` :**
```json
{
  "data": [
    {
      "id": 1,
      "campaign_id": 1,
      "raw_content_id": 1,
      "hook": "Say goodbye to __get/__set chaos! PHP 8.4's property hooks...",
      "body_points": ["Point 1", "Point 2", "Point 3"],
      "readability_score": 85,
      "suggested_hashtags": ["#PHP8.4"],
      "tone_justification": "The hook uses a witty tone...",
      "status": "draft",
      "version": 1,
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

**Tests :**
```javascript
let res = pm.response.json();
pm.test("body_points est un array", () => pm.expect(res.data[0].body_points).to.be.an('array'));
pm.test("suggested_hashtags est un array", () => pm.expect(res.data[0].suggested_hashtags).to.be.an('array'));
pm.test("readability_score est un integer", () => pm.expect(res.data[0].readability_score).to.be.a('number'));
pm.environment.set("post_id", res.data[0].id);
```

---

### 4.4 Détail d'un post — `GET {{base_url}}/api/posts/1`

---

### 4.5 Changer le statut — `PATCH {{base_url}}/api/posts/1/status`

**Body (raw JSON) :**
```json
{ "status": "posted" }
```

**Réponse attendue — `200 OK` :**
```json
{ "data": { ..., "status": "posted", ... } }
```

**Tests :**
```javascript
let res = pm.response.json();
pm.test("Status mis à jour", () => pm.expect(res.data.status).to.eql('posted'));
```

---

### 4.6 Validation — statut invalide

**Body :**
```json
{ "status": "invalid" }
```

**Réponse attendue — `422` :**
```json
{ "message": "...", "errors": { "status": ["The selected status is invalid."] } }
```

---

## 5. GHOSTWRITER — CHAT AGENT (US7-9)

### 5.1 Question sur les règles du Blueprint — `POST {{base_url}}/api/posts/1/chat`

**Body (raw JSON) :**
```json
{
  "message": "Quelles sont les règles de mon Blueprint actuel ?"
}
```

**Réponse attendue — `200 OK` (~5 secondes) :**
```json
{
  "data": {
    "response": "Les règles de votre campagne actuelle (ID: 1) sont :\n- Audience cible : developer community on X\n- Ton : professional but relaxed, witty\n- Longueur max : 280 caractères\n- Hashtags max : 1\n- No buzzwords like synergy...\n- Always cite the source...",
    "conversation_id": "019eff42-1eb0-731d-a011-b363b9e7c85c"
  }
}
```

**Tests :**
```javascript
let res = pm.response.json();
pm.test("Response non vide", () => pm.expect(res.data.response).to.be.a('string'));
pm.test("conversation_id retourné", () => pm.expect(res.data.conversation_id).to.be.a('string'));
pm.test("Tool déclenché (règles réelles)", () => {
  let r = res.data.response.toLowerCase();
  pm.expect(r).to.include('developer community');
  pm.expect(r).to.include('280');
});
pm.environment.set("conversation_id", res.data.conversation_id);
```

> ✅ Critère validé : si la réponse contient les **vraies règles** de la BDD
> (ex: "No buzzwords like synergy"), le tool `GetCampaignRules` a été déclenché (zéro hallucination).

---

### 5.2 Continuité contextuelle (mémoire) — `POST {{base_url}}/api/posts/1/chat`

**Body (raw JSON) :**
```json
{
  "message": "Traduis le hook en anglais, et donne-moi 3 variantes plus agressives pour celui-ci",
  "conversation_id": "{{conversation_id}}"
}
```

**Réponse attendue — `200 OK` :**
```json
{
  "data": {
    "response": "Here are 3 more aggressive variants:\n1. \"Ditch the __get/__set mess!...\"\n2. \"Tired of boilerplate?...\"\n3. \"Stop wasting time on magic methods!...\"",
    "conversation_id": "019eff42-1eb0-731d-a011-b363b9e7c85c"
  }
}
```

**Tests :**
```javascript
let res = pm.response.json();
pm.test("Même conversation_id (mémoire)", () => {
  pm.expect(res.data.conversation_id).to.eql(pm.environment.get("conversation_id"));
});
pm.test("Référence contextuelle comprise", () => {
  let r = res.data.response.toLowerCase();
  pm.expect(r).to.include('variant');
});
```

> ✅ Critère validé : "celui-ci" a été compris comme référençant le post de la Q1.

---

### 5.3 Demander l'historique du post — `POST {{base_url}}/api/posts/1/chat`

**Body :**
```json
{
  "message": "Montre-moi l'historique et les versions précédentes de ce post",
  "conversation_id": "{{conversation_id}}"
}
```

> Le tool `GetPostHistory` sera déclenché pour récupérer les vraies versions en BDD.

---

## 6. VÉRIFICATIONS TRANSVERSALES

### 6.1 Filtre anti-N+1 (eager loading)

Sur `GET /api/posts`, la réponse inclut `campaign_id` et `raw_content_id` sans requêtes supplémentaires.

### 6.2 Isolation des données par utilisateur

Créer un 2e utilisateur → ses posts ne doivent pas apparaître dans le listing du 1er.

### 6.3 Collection Postman

Importer le fichier :
```
storage/app/private/scribe/collection.json
```
Tous les endpoints sont pré-configurés avec examples.

### 6.4 Documentation Scribe

Ouvrir dans le navigateur :
```
http://127.0.0.1:8001/docs
```

---

## Récap des endpoints testés

| # | Méthode | Endpoint | Statut attendu | Critère validé |
|---|---------|----------|---------------|----------------|
| 1 | POST | /api/register | 201 | Auth + token |
| 2 | POST | /api/login | 200 | Auth + token |
| 3 | GET | /api/health (sans token) | 401 | Rejet stateless |
| 4 | GET | /api/health (avec token) | 200 | Token valide |
| 5 | POST | /api/logout | 200 | Révocation token |
| 6 | POST | /api/campaigns | 201 | Blueprint + rules:array |
| 7 | POST | /api/campaigns (vide) | 422 | Form Request |
| 8 | GET | /api/campaigns | 200 | Liste + posts_count |
| 9 | GET | /api/campaigns/1 | 200 | Détail + eager loading |
| 10 | POST | /api/content/repurpose | 202 | Async < 100ms |
| 11 | POST | /api/content/repurpose (vide) | 422 | Validation contenu |
| 12 | GET | /api/posts | 200 | Liste + casts array |
| 13 | GET | /api/posts/1 | 200 | Détail post généré |
| 14 | PATCH | /api/posts/1/status | 200 | Lifecycle |
| 15 | PATCH | /api/posts/1/status (invalide) | 422 | Validation statut |
| 16 | POST | /api/posts/1/chat | 200 | Tool GetCampaignRules |
| 17 | POST | /api/posts/1/chat (suite) | 200 | Mémoire conversation |
| 18 | POST | /api/posts/1/chat (historique) | 200 | Tool GetPostHistory |
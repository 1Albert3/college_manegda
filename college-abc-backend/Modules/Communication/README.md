# Module Communication

Le module Communication fournit un système global et modulaire pour gérer tous les types de communications dans l'application : emails, SMS, notifications push et notifications in-app.

## Fonctionnalités

- ✅ **Multi-canaux** : Email, SMS, Push, In-app
- ✅ **Templates dynamiques** : Variables et contenu personnalisable
- ✅ **File d'attente** : Envoi asynchrone avec retry automatique
- ✅ **Suivi complet** : Historique et statistiques des envois
- ✅ **Configuration facile** : Variables d'environnement simples
- ✅ **Extensible** : Ajout facile de nouveaux canaux/providers

## Installation

### 1. Configuration des variables d'environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
# Email Configuration
COMMUNICATION_EMAIL_ENABLED=true
COMMUNICATION_EMAIL_PROVIDER=smtp
COMMUNICATION_EMAIL_HOST=smtp.gmail.com
COMMUNICATION_EMAIL_PORT=587
COMMUNICATION_EMAIL_USERNAME=your-email@gmail.com
COMMUNICATION_EMAIL_PASSWORD=your-password
COMMUNICATION_EMAIL_ENCRYPTION=tls

# SMS Configuration (optionnel)
COMMUNICATION_SMS_ENABLED=false
COMMUNICATION_SMS_PROVIDER=twilio
COMMUNICATION_SMS_TWILIO_SID=your-sid
COMMUNICATION_SMS_TWILIO_TOKEN=your-token
COMMUNICATION_SMS_TWILIO_FROM=+1234567890

# Push Notifications (optionnel)
COMMUNICATION_PUSH_ENABLED=false
COMMUNICATION_PUSH_PROVIDER=firebase

# In-app Notifications
COMMUNICATION_IN_APP_ENABLED=true

# File d'attente
COMMUNICATION_QUEUE_ENABLED=true
COMMUNICATION_RETRY_ATTEMPTS=3
```

### 2. Migration et seeding

```bash
php artisan migrate
php artisan module:seed Communication
```

## Utilisation

### Utilisation basique avec la façade

```php
use Modules\Communication\Communication;

// Envoi simple
Communication::send('email', 'user@example.com', 'welcome', [
    'user_name' => 'Jean Dupont'
]);

// Envoi à un utilisateur (multi-canaux)
$user = User::find(1);
Communication::sendToUser($user, 'grade-published', [
    'subject' => 'Mathématiques',
    'grade' => '15/20'
], [
    'channels' => ['email', 'sms', 'push']
]);

// Envoi en masse
$users = User::where('role', 'student')->get();
Communication::sendBulk('announcement', $users, [
    'title' => 'Nouvelle année scolaire',
    'message' => 'L\'année commence demain !'
]);
```

### Utilisation avancée avec le service

```php
use Modules\Communication\Services\CommunicationService;

$communicationService = app(CommunicationService::class);

// Test d'un canal
$result = $communicationService->testChannel('email', 'test@example.com');

// Statistiques
$stats = $communicationService->getStats([
    'channel' => 'email',
    'date_from' => '2025-01-01'
]);

// Retry des échecs
$retryCount = $communicationService->retryFailed();
```

## API Endpoints

### Envoi de communications

```http
POST /api/v1/communication/send
{
  "channel": "email",
  "recipient": "user@example.com",
  "template": "welcome",
  "variables": {
    "user_name": "Jean Dupont"
  }
}
```

```http
POST /api/v1/communication/send-to-user
{
  "user_id": 1,
  "template": "grade-published",
  "channels": ["email", "sms"],
  "variables": {
    "subject": "Mathématiques",
    "grade": "15/20"
  }
}
```

```http
POST /api/v1/communication/send-bulk
{
  "template": "announcement",
  "recipients": [1, 2, 3, 4, 5],
  "channel": "email",
  "variables": {
    "title": "Nouvelle année scolaire"
  }
}
```

### Gestion et monitoring

```http
GET /api/v1/communication/logs
GET /api/v1/communication/stats
GET /api/v1/communication/templates
POST /api/v1/communication/test-channel
POST /api/v1/communication/retry-failed
```

## Templates

### Création d'un template

```php
use Modules\Communication\Entities\CommunicationTemplate;

CommunicationTemplate::create([
    'name' => 'Note publiée',
    'slug' => 'grade-published',
    'channel' => 'email',
    'subject' => 'Nouvelle note - {{subject}}',
    'content' => 'Une note a été publiée pour {{subject}}.',
    'html_content' => '<h2>Nouvelle note</h2><p>Une note a été publiée pour <strong>{{subject}}</strong>.</p>',
    'variables' => [
        'subject' => ['type' => 'string', 'required' => true, 'description' => 'Nom de la matière'],
        'grade' => ['type' => 'string', 'required' => true, 'description' => 'Note obtenue']
    ],
    'is_active' => true,
    'category' => 'academic'
]);
```

### Variables disponibles

- `{{app_name}}` - Nom de l'application
- `{{app_url}}` - URL de l'application
- `{{user_name}}` - Nom de l'utilisateur
- `{{user_email}}` - Email de l'utilisateur
- Variables personnalisées définies dans le template

## Canaux supportés

### Email
- **Providers** : SMTP, Mailgun, SendGrid, SES
- **Fonctionnalités** : HTML, pièces jointes, tracking

### SMS
- **Providers** : Twilio, AfricasTalking, AWS SNS
- **Fonctionnalités** : Texte simple, livraison confirmée

### Push Notifications
- **Providers** : Firebase, OneSignal
- **Fonctionnalités** : Notifications riches, actions

### In-App Notifications
- **Stockage** : Base de données
- **Fonctionnalités** : Notifications utilisateur, marquage lu/non lu

## Événements automatiques

Le système peut être configuré pour envoyer automatiquement des communications lors d'événements :

```php
// Dans config/communication.php
'events' => [
    'enabled' => true,
    'listeners' => [
        'Modules\Grade\Events\GradeCreated' => [
            'template' => 'grade-published',
            'channels' => ['email', 'in_app']
        ],
        'Modules\Attendance\Events\AbsenceCreated' => [
            'template' => 'absence-notification',
            'channels' => ['email', 'sms']
        ]
    ]
]
```

## File d'attente et retry

### Configuration de la file d'attente

```php
// Tentatives de retry
'queue' => [
    'retry_attempts' => 3,
    'retry_delay' => 60, // secondes
]
```

### Jobs disponibles

- `SendEmail` - Envoi d'emails
- `SendSms` - Envoi de SMS
- `SendPushNotification` - Envoi de notifications push

## Sécurité et performance

### Rate Limiting

```php
'rate_limiting' => [
    'email' => [
        'per_minute' => 60,
        'per_hour' => 1000
    ],
    'sms' => [
        'per_minute' => 10,
        'per_hour' => 100
    ]
]
```

### Logging et audit

- Historique complet de tous les envois
- Masquage automatique des données sensibles
- Métriques de performance par canal

## Extension du système

### Ajout d'un nouveau canal

1. Créer une classe implémentant `CommunicationChannelInterface`
2. L'enregistrer dans `CommunicationService`
3. Ajouter la configuration dans `config.php`

### Ajout d'un nouveau provider

1. Étendre le canal approprié
2. Ajouter la logique d'envoi
3. Mettre à jour la configuration

## Tests

```bash
# Tests du module
php artisan test --filter=Communication

# Test d'un canal spécifique
php artisan tinker
>>> app(\Modules\Communication\Services\CommunicationService::class)->testChannel('email', 'test@example.com')
```

## Dépannage

### Problèmes courants

1. **Emails non envoyés** : Vérifier la configuration SMTP
2. **SMS échoués** : Vérifier les credentials du provider
3. **Files d'attente bloquées** : Vérifier les workers Laravel

### Logs de débogage

```php
// Voir les logs de communication
Log::channel('communication')->info('Debug message');
```

## Support et contribution

Pour des questions ou contributions, veuillez contacter l'équipe de développement.

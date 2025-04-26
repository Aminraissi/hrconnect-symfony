# Configuration des clés API

Ce projet utilise plusieurs APIs externes qui nécessitent des clés d'authentification. Pour des raisons de sécurité, ces clés ne sont pas stockées directement dans le code source, mais sont gérées via des variables d'environnement.

## Comment configurer les clés API

1. Créez un fichier `.env.local` à la racine du projet (ce fichier ne sera pas commité dans Git)
2. Copiez le contenu du fichier `.env.local.example` dans votre fichier `.env.local`
3. Remplacez les valeurs par défaut par vos propres clés API

Exemple de contenu pour `.env.local` :

```
# Gemini API Key pour l'analyse de CV et la correction orthographique
GEMINI_API_KEY=votre_cle_api_gemini

# Affinda API Key pour l'extraction de données des CV
AFFINDA_API_KEY=votre_cle_api_affinda

# HrFlow.ai API Key pour l'analyse de CV
HRFLOW_API_KEY=votre_cle_api_hrflow

# Tx Platform API credentials
TX_PLATFORM_ACCOUNT_ID=votre_id_compte_tx_platform
TX_PLATFORM_SERVICE_KEY=votre_cle_service_tx_platform
TX_PLATFORM_REGION=EU

# Mailjet Template IDs
MAILJET_TEMPLATE_ID_ACCEPTED=votre_id_template_acceptation
MAILJET_TEMPLATE_ID_REJECTED=votre_id_template_refus
```

## APIs utilisées dans le projet

1. **Google Gemini API** - Utilisée pour l'analyse des CV et la correction orthographique
   - Obtenir une clé : [Google AI Studio](https://ai.google.dev/)

2. **Affinda API** - Utilisée pour l'extraction de données à partir des CV
   - Obtenir une clé : [Affinda](https://www.affinda.com/)

3. **HrFlow.ai API** - Utilisée pour l'analyse des CV
   - Obtenir une clé : [HrFlow.ai](https://www.hrflow.ai/)

4. **Tx Platform API** - Utilisée pour le traitement des données
   - Obtenir des identifiants : [Tx Platform](https://www.txplatform.io/)

5. **Mailjet API** - Utilisée pour l'envoi d'emails avec des templates
   - Obtenir des identifiants : [Mailjet](https://www.mailjet.com/)

## Remarque importante

Ne partagez jamais vos clés API avec d'autres personnes et ne les committez pas dans Git. Le fichier `.env.local` est déjà inclus dans le `.gitignore` pour éviter qu'il ne soit accidentellement commité.

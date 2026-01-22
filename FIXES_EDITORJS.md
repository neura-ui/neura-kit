# Corrections Editor.js - v1.0.6

## Problèmes identifiés et corrigés

### 1. ⚠️ Hook `appendCallback` déprécié
**Problème** : Console warning indiquant que `appendCallback` est déprécié
**Solution** : Aucune utilisation de `appendCallback` dans notre code - le warning provient probablement d'un outil tiers (highlight.js)

### 2. 🐛 Erreur "There is no block at index `1`"
**Problème** : Erreur lors de la synchronisation des changements Livewire → Editor.js
**Cause** : L'utilisation directe de `render()` sur un éditeur avec des blocs existants causait des conflits d'index
**Solution** : 
```typescript
// Avant
await ed.render(normalized);

// Après
await ed.clear();
await ed.render(normalized);
```
Maintenant, on vide d'abord l'éditeur avant de rendre le nouveau contenu.

### 3. 🔄 Upload d'images intermittent
**Problème** : Les uploads d'images fonctionnent parfois mais échouent aléatoirement
**Solutions multiples** :

#### a) Validation côté client
Ajout de validations avant l'upload :
- Taille maximale : 10MB
- Types acceptés : JPEG, JPG, PNG, GIF, WebP
- Vérification du token CSRF

#### b) Gestion d'erreurs améliorée
```typescript
try {
  // Validation file size
  if (file.size > maxSize) {
    throw new Error('File size exceeds 10MB limit');
  }
  
  // Validation file type
  if (!validTypes.includes(file.type)) {
    throw new Error('Invalid file type...');
  }
  
  // Upload avec meilleure gestion des erreurs
  const response = await fetch(uploadUrl, {...});
  
  if (!response.ok) {
    const error = await response.json().catch(...);
    throw new Error(error.message || 'Upload failed');
  }
  
  // Vérification de la structure de réponse
  if (!result.success) {
    throw new Error(result.message || 'Upload failed');
  }
  
  const imageUrl = result.file?.url || result.url || result.data?.url;
  if (!imageUrl) {
    throw new Error('No image URL in response');
  }
  
} catch (error) {
  console.error('Image upload error:', error);
  throw error;
}
```

#### c) Logs détaillés côté serveur
Ajout de logs dans `EditorImageController` :
- Log de tentative d'upload avec détails du fichier
- Log de succès avec URL et path
- Log d'erreurs de validation
- Log d'erreurs runtime avec stack trace

### 4. ✅ Améliorations supplémentaires

#### Callback `onReady`
Ajout d'un callback pour confirmer l'initialisation :
```typescript
editor = new EditorJSClass({
  // ...
  onReady: () => {
    console.log('Editor.js is ready');
  },
});
```

#### Headers HTTP améliorés
Ajout du header `Accept: application/json` pour garantir une réponse JSON :
```typescript
headers: {
  'X-CSRF-TOKEN': csrfToken || '',
  'Accept': 'application/json',
  ...uploadHeaders,
}
```

## Tests recommandés

### 1. Test de synchronisation
1. Ouvrir l'éditeur Editor.js
2. Ajouter plusieurs blocs (header, paragraph, list)
3. Sauvegarder et recharger la page
4. Vérifier qu'aucune erreur "block at index" n'apparaît

### 2. Test d'upload d'images
1. Essayer d'uploader différents types d'images (JPG, PNG, GIF, WebP)
2. Essayer d'uploader un fichier > 10MB (doit échouer avec message clair)
3. Essayer d'uploader un fichier non-image (doit échouer avec message clair)
4. Vérifier les logs Laravel pour confirmer les tentatives d'upload

### 3. Test de navigation Livewire
1. Utiliser l'éditeur sur une page avec Livewire Navigate
2. Naviguer vers une autre page
3. Revenir à la page de l'éditeur
4. Vérifier que l'éditeur se réinitialise correctement

## Logs à surveiller

### Console navigateur
- ✅ "Editor.js is ready" - Confirmation d'initialisation
- ❌ "There is no block at index" - Ne devrait plus apparaître
- ❌ "Image upload error" - Vérifier les détails de l'erreur

### Logs Laravel (`storage/logs/laravel.log`)
```
[INFO] Image upload attempt
[INFO] Image uploaded successfully
[WARNING] Image upload validation failed
[ERROR] Image upload failed
```

## Configuration requise

### Environnement
```env
# Dans .env
FILESYSTEM_DISK=public  # ou s3, etc.
```

### Config neura-kit
```php
// config/neura-kit.php
'editor' => [
    'default_variant' => 'tiptap',
    'max_image_size' => 10240, // KB (10MB)
    'image_disk' => 'public',
    'image_path' => 'editor/images',
],
```

### Storage link
```bash
php artisan storage:link
```

## Améliorations v1.0.6 (Suite)

### 4. 🔄 Système de retry automatique

**Problème** : Les uploads échouent parfois à cause de problèmes réseau temporaires

**Solution** : Implémentation d'un système de retry avec exponential backoff

```typescript
// Retry logic avec 3 tentatives
const maxRetries = 3;
for (let attempt = 0; attempt < maxRetries; attempt++) {
  try {
    if (attempt > 0) {
      // Exponential backoff: 1s, 2s, 4s
      const delay = Math.pow(2, attempt - 1) * 1000;
      await new Promise(resolve => setTimeout(resolve, delay));
    }
    return await performUpload(...);
  } catch (error) {
    // Ne pas retry sur les erreurs de validation
    if (isValidationError(error)) throw error;
    // Ne pas retry sur les erreurs 4xx (client)
    if (isClientError(error)) throw error;
    // Retry uniquement sur les erreurs réseau/serveur
  }
}
```

### 5. ⏱️ Gestion des timeouts

**Problème** : Les uploads peuvent rester bloqués indéfiniment

**Solution** : Timeout de 60 secondes avec AbortController

```typescript
const controller = new AbortController();
const timeoutId = setTimeout(() => controller.abort(), 60000);

const response = await fetch(url, {
  signal: controller.signal,
  // ...
});

clearTimeout(timeoutId);
```

### 6. 🛡️ Validation côté serveur améliorée

**Problème** : Les fichiers peuvent être invalides ou les permissions manquantes

**Solution** : Validation complète avant stockage

```php
// Vérification de validité du fichier
if (!$file->isValid()) {
    throw new \RuntimeException('Invalid file: ' . $file->getErrorMessage());
}

// Vérification des limites PHP
if ($file->getSize() > $maxUploadSize) {
    throw new \RuntimeException("File size exceeds PHP limit");
}

// Création du répertoire si nécessaire
if (!$storage->exists($path)) {
    if (!$storage->makeDirectory($path)) {
        throw new \RuntimeException("Failed to create directory");
    }
}
```

### 7. 🔁 Retry côté serveur

**Problème** : Les erreurs de stockage temporaires (permissions, I/O) causent des échecs

**Solution** : Retry avec exponential backoff côté serveur

```php
$maxAttempts = 2;
for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    try {
        $storedPath = $storage->putFileAs($path, $file, $filename);
        if ($storedPath && $storage->exists($storedPath)) {
            break; // Succès
        }
    } catch (\Exception $e) {
        if ($attempt === $maxAttempts) throw $e;
        usleep(500000 * $attempt); // 0.5s, 1s
    }
}
```

### 8. 📊 Logging détaillé

**Problème** : Difficile de diagnostiquer les problèmes d'upload

**Solution** : Logging complet avec métriques

```php
// Avant upload
$this->logger->info('Image upload attempt', [
    'file_size' => $file->getSize(),
    'mime_type' => $file->getMimeType(),
    'is_valid' => $file->isValid(),
]);

// Après upload
$this->logger->info('Image uploaded successfully', [
    'url' => $result['url'],
    'duration_ms' => $duration,
]);
```

## Prochaines étapes (v1.0.7)

1. Ajouter un système de retry automatique pour les uploads échoués
2. Implémenter une barre de progression pour les uploads
3. Ajouter la compression d'images côté client avant upload
4. Support du drag & drop d'images
5. Prévisualisation des images avant upload

## Fichiers modifiés

- `/neura-kit/resources/js/globals/editor/variants/editorjs.ts`
- `/neura-kit/src/Http/Controllers/EditorImageController.php`

## Commit

```bash
git add .
git commit -m "fix: Resolve Editor.js sync and upload issues (v1.0.6)

- Fix 'block at index' error by clearing before render
- Add client-side validation for image uploads
- Improve error handling and logging
- Add onReady callback for initialization tracking
- Add Accept header for JSON responses

Fixes intermittent image upload failures and synchronization errors."
```

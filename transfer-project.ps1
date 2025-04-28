# Script pour transférer les fichiers d'un projet Symfony vers un autre
# Paramètres
param (
    [Parameter(Mandatory=$true)]
    [string]$SourcePath,
    
    [Parameter(Mandatory=$true)]
    [string]$DestinationPath
)

# Vérifier que les chemins existent
if (-not (Test-Path $SourcePath)) {
    Write-Error "Le chemin source n'existe pas: $SourcePath"
    exit 1
}

if (-not (Test-Path $DestinationPath)) {
    Write-Error "Le chemin de destination n'existe pas: $DestinationPath"
    exit 1
}

# Dossiers à copier
$folders = @(
    "src",
    "templates",
    "public/assets",
    "config"
)

# Fichiers spécifiques à copier
$files = @(
    ".env.local",
    "composer.json",
    "composer.lock"
)

# Fonction pour copier un dossier
function Copy-FolderWithProgress {
    param (
        [string]$Source,
        [string]$Destination
    )
    
    # Créer le dossier de destination s'il n'existe pas
    if (-not (Test-Path $Destination)) {
        New-Item -ItemType Directory -Path $Destination -Force | Out-Null
    }
    
    # Copier les fichiers
    Get-ChildItem -Path $Source -Recurse | ForEach-Object {
        $targetPath = $_.FullName.Replace($Source, $Destination)
        
        # Si c'est un dossier, le créer
        if ($_.PSIsContainer) {
            if (-not (Test-Path $targetPath)) {
                New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
            }
        }
        # Si c'est un fichier, le copier
        else {
            $targetDir = Split-Path -Path $targetPath -Parent
            if (-not (Test-Path $targetDir)) {
                New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
            }
            
            Copy-Item -Path $_.FullName -Destination $targetPath -Force
            Write-Host "Copié: $($_.FullName)"
        }
    }
}

# Copier les dossiers
foreach ($folder in $folders) {
    $sourceFolderPath = Join-Path -Path $SourcePath -ChildPath $folder
    $destFolderPath = Join-Path -Path $DestinationPath -ChildPath $folder
    
    if (Test-Path $sourceFolderPath) {
        Write-Host "Copie du dossier: $folder"
        Copy-FolderWithProgress -Source $sourceFolderPath -Destination $destFolderPath
    } else {
        Write-Host "Dossier source non trouvé: $folder" -ForegroundColor Yellow
    }
}

# Copier les fichiers spécifiques
foreach ($file in $files) {
    $sourceFilePath = Join-Path -Path $SourcePath -ChildPath $file
    $destFilePath = Join-Path -Path $DestinationPath -ChildPath $file
    
    if (Test-Path $sourceFilePath) {
        Write-Host "Copie du fichier: $file"
        Copy-Item -Path $sourceFilePath -Destination $destFilePath -Force
    } else {
        Write-Host "Fichier source non trouvé: $file" -ForegroundColor Yellow
    }
}

Write-Host "Transfert terminé!" -ForegroundColor Green
Write-Host "N'oubliez pas de vérifier les fichiers transférés et d'exécuter 'composer install' dans le projet de destination."

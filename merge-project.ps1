# Script pour fusionner le nouveau code dans un projet Symfony existant
# Paramètres
param (
    [Parameter(Mandatory=$true)]
    [string]$SourcePath,
    
    [Parameter(Mandatory=$true)]
    [string]$DestinationPath,
    
    [Parameter(Mandatory=$false)]
    [switch]$DryRun = $false
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

# Dossiers à analyser
$folders = @(
    "src",
    "templates",
    "public/assets",
    "config"
)

# Fonction pour comparer et fusionner les fichiers
function Merge-Files {
    param (
        [string]$Source,
        [string]$Destination,
        [bool]$IsDryRun
    )
    
    # Obtenir tous les fichiers source
    $sourceFiles = Get-ChildItem -Path $Source -Recurse -File
    
    foreach ($sourceFile in $sourceFiles) {
        # Calculer le chemin relatif
        $relativePath = $sourceFile.FullName.Substring($Source.Length)
        $destinationFile = Join-Path -Path $Destination -ChildPath $relativePath
        $destinationDir = Split-Path -Path $destinationFile -Parent
        
        # Vérifier si le fichier de destination existe
        $fileExists = Test-Path $destinationFile
        
        # Créer le répertoire de destination s'il n'existe pas
        if (-not (Test-Path $destinationDir)) {
            if (-not $IsDryRun) {
                New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
            }
            Write-Host "Création du répertoire: $destinationDir" -ForegroundColor Cyan
        }
        
        # Si le fichier n'existe pas dans la destination, le copier
        if (-not $fileExists) {
            if (-not $IsDryRun) {
                Copy-Item -Path $sourceFile.FullName -Destination $destinationFile -Force
            }
            Write-Host "Nouveau fichier: $relativePath" -ForegroundColor Green
        }
        # Si le fichier existe, vérifier s'il est différent
        else {
            $sourceContent = Get-Content -Path $sourceFile.FullName -Raw
            $destContent = Get-Content -Path $destinationFile -Raw
            
            if ($sourceContent -ne $destContent) {
                # Créer un fichier de sauvegarde
                $backupFile = "$destinationFile.bak"
                if (-not $IsDryRun) {
                    Copy-Item -Path $destinationFile -Destination $backupFile -Force
                }
                
                Write-Host "Fichier différent: $relativePath" -ForegroundColor Yellow
                Write-Host "  Sauvegarde créée: $backupFile" -ForegroundColor Yellow
                
                # Créer un fichier de fusion
                $mergeFile = "$destinationFile.new"
                if (-not $IsDryRun) {
                    Copy-Item -Path $sourceFile.FullName -Destination $mergeFile -Force
                }
                
                Write-Host "  Nouveau contenu sauvegardé dans: $mergeFile" -ForegroundColor Yellow
                Write-Host "  Vous devrez fusionner manuellement ce fichier" -ForegroundColor Yellow
            }
            else {
                Write-Host "Fichier identique: $relativePath" -ForegroundColor Gray
            }
        }
    }
}

# Fonction pour analyser les dossiers
function Analyze-Folders {
    param (
        [string]$Source,
        [string]$Destination,
        [array]$FolderList,
        [bool]$IsDryRun
    )
    
    foreach ($folder in $FolderList) {
        $sourceFolderPath = Join-Path -Path $Source -ChildPath $folder
        $destFolderPath = Join-Path -Path $Destination -ChildPath $folder
        
        if (Test-Path $sourceFolderPath) {
            Write-Host "Analyse du dossier: $folder" -ForegroundColor Blue
            Merge-Files -Source $sourceFolderPath -Destination $destFolderPath -IsDryRun $IsDryRun
        } else {
            Write-Host "Dossier source non trouvé: $folder" -ForegroundColor Yellow
        }
    }
}

# Mode simulation
if ($DryRun) {
    Write-Host "MODE SIMULATION: Aucune modification ne sera effectuée" -ForegroundColor Magenta
}

# Analyser les dossiers
Analyze-Folders -Source $SourcePath -Destination $DestinationPath -FolderList $folders -IsDryRun $DryRun

# Résumé
Write-Host "`nRésumé de l'opération:" -ForegroundColor Blue
if ($DryRun) {
    Write-Host "Simulation terminée. Exécutez à nouveau sans le paramètre -DryRun pour appliquer les modifications." -ForegroundColor Magenta
} else {
    Write-Host "Fusion terminée!" -ForegroundColor Green
    Write-Host "Les fichiers identiques ont été ignorés." -ForegroundColor Gray
    Write-Host "Les nouveaux fichiers ont été copiés." -ForegroundColor Green
    Write-Host "Pour les fichiers différents:" -ForegroundColor Yellow
    Write-Host "  - Une sauvegarde a été créée (.bak)" -ForegroundColor Yellow
    Write-Host "  - Le nouveau contenu a été sauvegardé (.new)" -ForegroundColor Yellow
    Write-Host "  - Vous devrez fusionner manuellement ces fichiers" -ForegroundColor Yellow
}

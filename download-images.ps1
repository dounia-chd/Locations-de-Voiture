# Créer le répertoire de sortie s'il n'existe pas
$outputDir = "public/uploads/cars"
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force
}

# Hashtable des véhicules et leurs URLs d'images
$cars = @{
    "bmw-m5" = "https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg"
    "mercedes-cla" = "https://images.pexels.com/photos/2365572/pexels-photo-2365572.jpeg"
    "lamborghini-urus" = "https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg"
    "renault-clio5" = "https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg"
    "hyundai-tucson" = "https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg"
    "audi-rs3" = "https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg"
    "mercedes-brabus-g" = "https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg"
    "mazda-mx5" = "https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg"
    "toyota-supra" = "https://images.pexels.com/photos/3802510/pexels-photo-3802510.jpeg"
}

# Télécharger chaque image
foreach ($car in $cars.GetEnumerator()) {
    $url = $car.Value
    $outputFile = Join-Path $outputDir "$($car.Key).jpeg"
    
    Write-Host "Downloading $($car.Key)..."
    try {
        Invoke-WebRequest -Uri $url -OutFile $outputFile
        Write-Host "Downloaded to $outputFile"
    }
    catch {
        Write-Host "Failed to download $($car.Key): $_"
    }
}

Write-Host "All downloads completed!" 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Puntos de Recarga Detalle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="leaflet.css" />
    <script src="leaflet.js"></script>
    <style>
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-map-grid: #e9ecef;
            --color-map-dot: #dc3545;
            --color-rating-star: #ffc107;
            --color-distance: #0b2e88;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-background-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* --- Header / Menú Superior (Común) --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            width: 100%;
            box-sizing: border-box;
        }
        .logo { font-size: 1.2em; font-weight: bold; color: var(--color-primary); }
        .nav-menu { display: flex; gap: 20px; }
        .nav-item { color: #666; text-decoration: none; padding: 5px 10px; border-radius: 5px; font-size: 0.95em; transition: background-color 0.2s, color 0.2s; }
        .nav-item.active { background-color: #f0f0f0; color: var(--color-text-dark); font-weight: 500; }
        .saldo { font-size: 1em; color: var(--color-text-dark); font-weight: 600; }

        /* --- Contenido Principal de la Vista --- */
        .page-content-wrapper {
            flex-grow: 1;
            padding: 20px 0 50px 0;
        }
        .main-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        /* Título de la Sección */
        .section-header h1 { font-size: 1.8em; color: var(--color-text-dark); margin: 0 0 5px 0; }
        .section-header p { font-size: 0.95em; color: #666; margin-bottom: 30px; }

        /* --- Barra de Búsqueda y Filtros --- */
        .search-area { background-color: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px; }
        .search-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .search-input { flex-grow: 1; position: relative; }
        .search-input input { width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #ccc; border-radius: 5px; font-size: 1em; box-sizing: border-box; }
        .search-input i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        .filter-button { padding: 10px 20px; border: 1px solid #ccc; border-radius: 5px; background-color: white; cursor: pointer; font-size: 1em; display: flex; align-items: center; gap: 5px; color: #333; }
        .results-info { font-size: 0.9em; color: #666; margin-top: 10px; display: flex; align-items: center; }
        .results-info i { margin-right: 5px; color: var(--color-secondary); }

        /* --- Mapa Interactivo (Leaflet) --- */
        .map-section { margin-bottom: 40px; }
        .map-section h2 { font-size: 1.2em; color: var(--color-text-dark); margin: 0; font-weight: 600; }
        .map-section p { font-size: 0.9em; color: #666; margin-bottom: 20px; }
        .interactive-map {
            width: 100%; height: 400px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; position: relative;
        }

        /* --- LISTA DE PUNTOS DE RECARGA --- */
        .recharge-list-item {
            display: flex;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .recharge-list-item:last-child {
            border-bottom: none;
        }
        
        .list-icon {
            font-size: 1.5em;
            color: var(--color-secondary);
            margin-right: 20px;
            padding-top: 5px;
        }
        
        .list-details {
            flex-grow: 1;
        }
        
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
        }
        
        .location-info h3 {
            font-size: 1.1em;
            color: var(--color-text-dark);
            margin: 0;
            font-weight: 600;
        }
        
        .location-info p {
            font-size: 0.9em;
            color: #666;
            margin: 2px 0;
        }
        
        .tags-and-rating {
            font-size: 0.8em;
            color: #999;
            margin-top: 5px;
        }
        
        .tag {
            background-color: #f0f0f0;
            padding: 3px 8px;
            border-radius: 3px;
            margin-right: 5px;
            font-weight: 500;
        }
        
        .tag.center { background-color: #e3f2fd; color: #1e88e5; }
        .tag.north { background-color: #e8f5e9; color: #4caf50; }
        .tag.south { background-color: #fcf4e8; color: #f90; }

        .rating {
            color: var(--color-rating-star);
            font-weight: 600;
            margin-left: 5px;
        }
        
        .distance-action {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            text-align: right;
            margin-left: 20px;
        }
        
        .distance {
            font-size: 1.4em;
            font-weight: bold;
            color: var(--color-distance);
            margin-bottom: 10px;
        }
        
        .action-button {
            background: none;
            border: 1px solid #ccc;
            color: var(--color-secondary);
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }
        
        .action-button:hover {
            background-color: #f0f0f0;
        }


        /* --- Footer (Simulación) --- */
        .footer {
            text-align: center;
            padding: 20px;
            background-color: white;
            color: #666;
            font-size: 0.85em;
            margin-top: auto;
            border-top: 1px solid #eee;
        }

        @media (max-width: 768px) {
            .search-row { flex-direction: column; }
            .filter-button { width: 100%; justify-content: center; }
            .recharge-list-item { flex-direction: column; align-items: flex-start; }
            .distance-action { margin-top: 10px; align-self: flex-end; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">
            Digital Transport
            <span style="font-size: 0.7em; display: block; font-weight: normal; color: #666;">Sistema de Boletos Digitales</span>
        </div>
        <nav class="nav-menu">
            <a href="index.php" class="nav-item">Inicio</a>
            <a href="registro-usuarios.php" class="nav-item">Registro</a>
            <a href="recarga-digital.php" class="nav-item">Recarga</a>
            <a href="puntos-recarga.php" class="nav-item">Puntos PR</a>
            <a href="mis-boletos.php" class="nav-item">Boletos</a>
            <a href="historial-viaje.php" class="nav-item active">Historial</a> 
        </nav>
        <div class="saldo">
            Saldo: **$125.50** A
        </div>
    </header>

    <div class="page-content-wrapper">
        <div class="main-content">
            
            <header class="section-header">
                <h1>Puntos de Recarga</h1>
                <p>Encuentra el punto de recarga más cercano a tu ubicación</p>
            </header>

            <div class="search-area">
                <div class="search-row">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search-input" placeholder="Buscar por nombre o dirección...">
                    </div>
                    
                    <button class="filter-button">
                        <i class="fas fa-globe-americas"></i>
                        Todas las zonas
                        <i class="fas fa-angle-down"></i>
                    </button>
                    
                    <button class="filter-button">
                        <i class="fas fa-sliders-h"></i>
                    </button>
                </div>
                
                <div class="results-info">
                    <i class="fas fa-map-marker-alt"></i>
                    Mostrando 6 puntos de recarga
                </div>
            </div>

            <div class="map-section">
                <h2>Mapa Interactivo</h2>
                <p>Visualiza todos los puntos de recarga en el mapa</p>
                
                <div id="map" class="interactive-map">
                    </div>
            </div>

            <section class="recharge-list-section">
                
                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Terminal de Buses Central</h3>
                                <p>Av. Montes #1234, Centro</p>
                            </div>
                            <span class="tag center">Centro</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 6:00 AM - 10:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-41123456</span> |
                            <span class="rating">4.8 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                            <span class="tag">Venta de tarjetas</span>
                            <span class="tag">Información</span>
                        </div>
                    </div>
                    <div class="distance-action">
                        <div class="distance">0.0 <small>km</small></div>
                        <button class="action-button"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>
                
                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Plaza Principal</h3>
                                <p>Plaza 14 de Septiembre, Centro</p>
                            </div>
                            <span class="tag center">Centro</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 7:00 AM - 9:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4234567</span> |
                            <span class="rating">4.6 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                            <span class="tag">Información</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">0.0 <small>km</small></div>
                        <button class="action-button"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>

                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Universidad Mayor de San Simón</h3>
                                <p>Av. Oquendo Km 4.5, Zona Universitaria</p>
                            </div>
                            <span class="tag north">Norte</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 7:00 AM - 8:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4345678</span> |
                            <span class="rating">4.9 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                            <span class="tag">Venta de tarjetas</span>
                            <span class="tag">Descuentos estudiantes</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">0.0 <small>km</small></div>
                        <button class="action-button"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>

                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Mercado La Cancha</h3>
                                <p>Calle Lanza, La Cancha</p>
                            </div>
                            <span class="tag south">Sur</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 6:00 AM - 6:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4556677</span> |
                            <span class="rating">4.2 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">0.0 <small>km</small></div>
                        <button class="action-button"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>

                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Parque Fidel Anze</h3>
                                <p>Av. Pando, Zona Norte</p>
                            </div>
                            <span class="tag north">Norte</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 8:00 AM - 1:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4667788</span> |
                            <span class="rating">4.7 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">0.0 <small>km</small></div>
                        <button class="action-button"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>

                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Cruce Taquiña</h3>
                                <p>Av. Blanco Galindo y C. Taquiña</p>
                            </div>
                            <span class="tag north">Norte</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 7:00 AM - 9:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4778899</span> |
                            <span class="rating">4.5 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                            <span class="tag">Venta de tarjetas</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">0.0 <small>km</small></div>
                        <button class="action-button"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>
                
            </section>
            
        </div>
    </div>

    <footer class="footer">
        © 2025 Digital Transport - Sistema de Boletos Digital
    </footer>

    <script>
        // Datos estáticos de los Puntos de Recarga con Lat/Lng (Cochabamba, Bolivia)
        const rechargePointsData = [
            { name: "Terminal de Buses Central", lat: -17.3892, lng: -66.1557, address: "Av. Montes #1234, Centro" },
            { name: "Plaza Principal", lat: -17.3941, lng: -66.1565, address: "Plaza 14 de Septiembre, Centro" },
            { name: "Universidad Mayor de San Simón", lat: -17.3871, lng: -66.1491, address: "Av. Oquendo Km 4.5, Zona Universitaria" },
            { name: "Mercado La Cancha", lat: -17.4060, lng: -66.1580, address: "Calle Lanza, La Cancha" },
            { name: "Parque Fidel Anze", lat: -17.3670, lng: -66.1420, address: "Av. Pando, Zona Norte" },
            { name: "Cruce Taquiña", lat: -17.3550, lng: -66.1650, address: "Av. Blanco Galindo y C. Taquiña" }
        ];

        let map;
        let userLocation = { lat: -17.3934, lng: -66.1569 }; // Centro de Cochabamba como default

        /**
         * Agrega un mensaje de diagnóstico al contenedor del mapa (visible si falla la carga).
         * @param {string} message - El mensaje de error a mostrar.
         */
        function addDiagnosticMessage(message) {
            const mapElement = document.getElementById('map');
            if (!mapElement) return;

            // Limpiamos el contenido actual por si las dudas
            mapElement.innerHTML = ''; 

            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: absolute; 
                top: 0; left: 0; width: 100%; height: 100%; 
                background-color: rgba(255, 0, 0, 0.1); 
                color: #dc3545; 
                display: flex; 
                flex-direction: column;
                justify-content: center; 
                align-items: center; 
                text-align: center;
                padding: 20px;
                font-size: 1.1em;
                z-index: 1000;
            `;
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i>
                <strong>¡Error de Mapa!</strong><br>
                ${message}<br><br>
                *Solución sugerida: Verifique que los archivos **'leaflet.js'** y **'leaflet.css'** estén en la **misma carpeta** que su archivo HTML.
            `;
            mapElement.appendChild(errorDiv);
        }

        /**
         * Inicializa el mapa de Leaflet y la Geolocalización.
         */
        function initMap() {
            // Usamos setTimeout para asegurar que el DOM y CSS se carguen completamente.
            setTimeout(() => { 
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            userLocation = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            renderMap(userLocation);
                        },
                        () => {
                            console.warn("Geolocalización denegada. Usando ubicación por defecto.");
                            renderMap(userLocation);
                        }
                    );
                } else {
                    console.error("Tu navegador no soporta Geolocalización.");
                    renderMap(userLocation);
                }
            }, 100); 
        }

        /**
         * Renderiza el mapa usando Leaflet.
         * @param {Object} center - Latitud y longitud del centro del mapa.
         */
        function renderMap(center) {
            const mapElement = document.getElementById('map');
            if (!mapElement) return;

            // Si ya existe un mapa, lo destruimos
            if (map) {
                map.remove(); 
            }

            try {
                // 1. Inicializar el mapa de Leaflet y centrarlo
                // El error L is not defined ocurriría aquí si el archivo local no se cargó.
                map = L.map(mapElement).setView([center.lat, center.lng], 13); 

                // 2. Añadir la capa de OpenStreetMap (Map Tiles)
                const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);

                // Atrapamos el evento de fallo en la carga del tile para dar diagnóstico
                tiles.on('tileerror', function(error) {
                    console.error("Leaflet Tile Error:", error);
                    map.remove();
                    addDiagnosticMessage("Fallo al cargar las imágenes del mapa (tiles). **(Esto sí depende de Internet)** Revisa tu conexión de internet o el acceso a los servidores de OpenStreetMap.");
                });
                
                // Forzar a Leaflet a recalcular el tamaño del contenedor
                map.invalidateSize(); 

                // 3. Marcar la ubicación del usuario con un Círculo/Marcador (Leaflet)
                L.circleMarker([center.lat, center.lng], {
                    radius: 8,
                    color: 'var(--color-primary)',
                    fillColor: 'var(--color-primary)',
                    fillOpacity: 0.8
                }).addTo(map)
                    .bindPopup("Tu Ubicación").openPopup();

                // 4. Colocar todos los marcadores de los Puntos de Recarga
                placeRechargeMarkers(rechargePointsData);
                
                // 5. Calcular y actualizar distancias en la lista
                updateDistancesInList();

            } catch (e) {
                // Si el error L is not defined persiste, se mostrará aquí
                console.error("Fallo general al inicializar Leaflet:", e);
                addDiagnosticMessage("Fallo general al inicializar el mapa. Error técnico: " + e.message + " (La carga local de Leaflet falló. Revisa el nombre y ubicación de los archivos).");
            }
        }

        /**
         * Coloca marcadores en el mapa para cada punto de recarga (Leaflet).
         * @param {Array} points - Array de objetos con datos del punto (name, lat, lng).
         */
        function placeRechargeMarkers(points) {
            points.forEach((point) => {
                L.marker([point.lat, point.lng]).addTo(map)
                    .bindPopup(`
                        <div style="font-family: sans-serif;">
                            <strong>${point.name}</strong><br>
                            ${point.address}
                        </div>
                    `);
            });
        }

        /**
         * Calcula la distancia usando la fórmula de Haversine y actualiza los elementos de la lista.
         */
        function updateDistancesInList() {
            const listItems = document.querySelectorAll('.recharge-list-item');
            const R = 6371; // Radio de la Tierra en km

            rechargePointsData.forEach((point, index) => {
                const listItem = listItems[index]; 
                
                if (listItem) {
                    // Cálculo Haversine (fórmula geométrica)
                    const dLat = (point.lat - userLocation.lat) * (Math.PI / 180);
                    const dLon = (point.lng - userLocation.lng) * (Math.PI / 180);
                    
                    const a = 
                        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                        Math.cos(userLocation.lat * (Math.PI / 180)) * Math.cos(point.lat * (Math.PI / 180)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
                        
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                    const distanceKm = R * c; 
                    
                    const distanceElement = listItem.querySelector('.distance');
                    if (distanceElement) {
                        distanceElement.innerHTML = `${distanceKm.toFixed(1)} <small>km</small>`;
                    }

                    // Agregar funcionalidad al botón "Cómo llegar" (simulación de ruta en OpenStreetMap/Google Maps)
                    const routeButton = listItem.querySelector('.action-button');
                    routeButton.onclick = () => {
                        const destination = `${point.lat},${point.lng}`;
                        const origin = `${userLocation.lat},${userLocation.lng}`;
                        // Abre un enlace de ruta utilizando la ubicación detectada y el destino.
                        window.open(`https://www.google.com/maps/dir/${origin}/${destination}`, '_blank');
                    };
                }
            });
            
            // Actualizar el número total de resultados mostrados
            document.querySelector('.results-info').innerHTML = 
                `<i class="fas fa-map-marker-alt"></i> Mostrando ${rechargePointsData.length} puntos de recarga`;
        }
        
        initMap();

    </script>

</body>
</html>
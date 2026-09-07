# Digital Transport 🚍💨

Sistema avanzado de gestión y cobro digital para transporte público.

## 🚀 Inicio Rápido

1. **Requisitos**:
   - PHP 8.0+
   - MySQL 8.0+ / Docker & Docker Compose
   - Servidor web (Apache/Nginx)

2. **Instalación con Docker (Recomendado)**:
   ```bash
   # Levantar base de datos e importar automáticamente database_reset.sql
   docker-compose up -d

   # Ejecutar suite de pruebas PHPUnit
   ./vendor/bin/phpunit
   ```

3. **Instalación Tradicional**:
   - Clona el repositorio en tu servidor local.
   - Importa el archivo `database_reset.sql` en tu base de datos MySQL.
   - Configura las variables de entorno en `.env` (guíate de `.env.example`).
   - Abre `frontend/index.php` en tu navegador.

## 🛠️ Tecnologías
- **PHP** (Core)
- **MySQL** (Database)
- **Vanilla CSS** (Styling)
- **JavaScript** (QR Logic & UI)
- **Leaflet.js** (Maps)

## 📄 Documentación
Puedes encontrar la documentación detallada en la carpeta `specs/`:
- [Plan de Mejoras](specs/plan.md)
- [Documentación Técnica](specs/documentacion.md)
- [Lista de Tareas](specs/tasks.md)

## 👤 Créditos
Desarrollado para la Competencia de Análisis de Sistemas.

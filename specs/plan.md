# Plan de Mejoras del Sistema Digital Transport

Este plan describe los pasos para modernizar y mejorar el sistema Digital Transport, centrándose en la experiencia del usuario, la excelencia visual y la arquitectura del código.

## Revisión del Usuario Requerida

> [!IMPORTANT]
> La refactorización de la interfaz de usuario (UI) cambiará significativamente el aspecto de la aplicación. Pasaremos de una mezcla básica de PHP/CSS a un enfoque más estructurado utilizando tokens de diseño modernos y glassmorphism.

## Preguntas Abiertas

- ¿Tienes alguna paleta de colores o marca específica en mente?
- ¿Deberíamos priorizar algún rol de usuario específico (Pasajero, Chofer, Administrador) para las mejoras de la interfaz?

## Cambios Propuestos

### Base del Núcleo

#### [NUEVO] [style.css](file:///opt/lampp/htdocs/Competencia-Analisis/digital-transport/frontend/css/style.css)
Crear un archivo CSS centralizado con tokens de diseño modernos (colores HSL, fuente Inter, sistema de espaciado).

#### [NUEVO] [db.php](file:///opt/lampp/htdocs/Competencia-Analisis/digital-transport/backend/includes/db.php)
Implementar un envoltorio de base de datos basado en PDO para una mejor seguridad y flexibilidad.

---

### Componentes de UI

#### [NUEVO] [header.php](file:///opt/lampp/htdocs/Competencia-Analisis/digital-transport/frontend/includes/header.php)
Extraer la lógica y la vista del encabezado en un componente reutilizable.

#### [NUEVO] [footer.php](file:///opt/lampp/htdocs/Competencia-Analisis/digital-transport/frontend/includes/footer.php)
Extraer el pie de página en un componente reutilizable.

#### [MODIFICAR] [index.php](file:///opt/lampp/htdocs/Competencia-Analisis/digital-transport/frontend/index.php)
Refactorizar la página de inicio principal para utilizar el nuevo sistema de diseño y componentes.

---

### Documentación

#### [MODIFICAR] [README.md](file:///opt/lampp/htdocs/Competencia-Analisis/digital-transport/README.md)
Añadir descripción del proyecto, instrucciones de instalación y descripción general de las características.

## Plan de Verificación

### Pruebas Automatizadas
- Ejecutar las pruebas PHPUnit existentes usando `vendor/bin/phpunit`.

### Verificación Manual
- Verificar la nueva interfaz en el navegador en diferentes tamaños de pantalla.
- Asegurarse de que el interruptor de modo oscuro funcione perfectamente con los nuevos tokens de diseño.
- Probar el modal de "Pase Digital" para verificar su claridad y adaptabilidad.

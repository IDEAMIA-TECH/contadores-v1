# Módulo para Contadores en el Sistema de Cobranza

## Descripción General
Se agregará un módulo específico para contadores dentro del sistema de cobranza de IDEAMIA Tech. Este módulo permitirá a los contadores subir varios archivos XML correspondientes a los clientes registrados y generar reportes detallados con el desglose de los diferentes tipos de IVA aplicados en México. Además, se almacenarán los datos para futuras consultas y la información podrá exportarse en formato Excel.

## Características del Módulo

### tecnologias
- frontend: html, css, javascript
- backend: php
- base de datos: mysql
- servidor: cpanel

### 1. Acceso Restringido
- Solo los usuarios con nivel de acceso "contador" podrán ingresar a este módulo.

### 2. Gestor de Clientes
- Los contadores podrán registrar y gestionar clientes para llevar un control de sus declaraciones fiscales (mensuales o bimestrales).

### 3. Carga Masiva de XML
- Se permitirá la carga de múltiples archivos XML de facturas simultáneamente.
- El sistema procesará los archivos y extraerá la información relevante.

### 4. Desglose de IVA
Se generará un informe detallado con la separación de todos los IVAs posibles en México:
- IVA 16%
- IVA 8%
- IVA Exento
- IVA 0%
- Otros (según se requiera)

### 5. Exportación de Reportes
- El desglose del IVA podrá ser exportado a un archivo Excel para su uso en declaraciones fiscales.

### 6. Consulta de Historial
- El sistema almacenará los datos procesados para futuras consultas.
- Se podrá filtrar por cliente, rango de fechas y tipo de IVA.


### 🤖 6. Implementación de Inteligencia Artificial
- **Desarrollo de un modelo de IA** para detectar coincidencias entre transacciones bancarias y registros contables.
- **Clasificación automática de transacciones** basada en patrones históricos.
- **Sugerencias inteligentes** para la conciliación de movimientos no coincidentes.
- **Autocorrección de errores comunes**, como diferencias menores en montos o fechas.
- **Generación de reportes automáticos** con el estado de la conciliación.


## Estructura de la Base de Datos

### Tabla: accountants (Contadores)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| user_id | INT | FK → users.id |
| created_at | TIMESTAMP | Fecha de creación |
| updated_at | TIMESTAMP | Fecha de actualización |

### Tabla: accountant_clients (Clientes de los contadores)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| accountant_id | INT | FK → accountants.id |
| client_id | INT | FK → clients.id |
| created_at | TIMESTAMP | Fecha de creación |
| updated_at | TIMESTAMP | Fecha de actualización |

### Tabla: tax_reports (Reportes Fiscales)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| accountant_id | INT | FK → accountants.id |
| client_id | INT | FK → clients.id |
| period | ENUM | 'Mensual', 'Bimestral' |
| start_date | DATE | Fecha inicial del período |
| end_date | DATE | Fecha final del período |
| total_iva_16 | DECIMAL(10,2) | Total IVA 16% |
| total_iva_8 | DECIMAL(10,2) | Total IVA 8% |
| total_iva_exento | DECIMAL(10,2) | Total IVA Exento |
| total_iva_0 | DECIMAL(10,2) | Total IVA 0% |
| xml_count | INT | Cantidad de XML procesados |
| report_path | VARCHAR(255) | Ruta del reporte generado |
| created_at | TIMESTAMP | Fecha de creación |

### Tabla: uploaded_xmls (XML Subidos)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | PK, AUTO_INCREMENT |
| accountant_id | INT | FK → accountants.id |
| client_id | INT | FK → clients.id |
| invoice_uuid | VARCHAR(36) | UUID único de factura |
| xml_path | VARCHAR(255) | Ruta del archivo XML |
| processed | BOOLEAN | Estado de procesamiento |
| created_at | TIMESTAMP | Fecha de creación |

## Flujo del Módulo

### 1. Acceso al Módulo
1. El usuario contador inicia sesión
2. Accede a su panel de control específico

### 2. Registro de Clientes
1. El contador agrega los clientes que manejará
2. Configura las preferencias de declaraciones fiscales

### 3. Carga de XML
1. Selección múltiple de archivos XML
2. Validación y procesamiento de archivos
3. Extracción de información relevante

### 4. Generación del Reporte
1. Cálculo automático del desglose de IVA
2. Almacenamiento en base de datos
3. Visualización preliminar del reporte

### 5. Exportación de Reporte
1. Generación del archivo Excel
2. Descarga del reporte formateado

### 6. Consulta de Historial
1. Búsqueda por filtros
2. Visualización de reportes anteriores
3. Opción de regenerar reportes

## Consideraciones Técnicas

### Seguridad
- Implementación de control de acceso basado en roles (RBAC)
- Validación de archivos XML
- Encriptación de datos sensibles

### Escalabilidad
- Diseño modular para futuras ampliaciones
- Optimización de consultas a la base de datos
- Procesamiento asíncrono de archivos XML

### Usabilidad
- Interfaz intuitiva y responsive
- Feedback inmediato durante el procesamiento
- Guías y tooltips integrados
- Validaciones en tiempo real

## Conclusión
Este módulo optimizará significativamente la gestión fiscal de los clientes y facilitará la labor de los contadores dentro del sistema de cobranza de IDEAMIA Tech, proporcionando una solución integral para el manejo de declaraciones fiscales.

## Plan de Implementación

### Fase 1: Configuración Inicial

1. Estructura de directorios
2. Archivo de configuración de base de datos
3. Script de creación de tablas
4. Configuración básica de seguridad

### Fase 2: Sistema de Autenticación y Panel de Control

1. **Sistema de Login**
   - Página de inicio de sesión
   - Validación de credenciales
   - Manejo de sesiones

2. **Panel de Control del Contador**
   - Dashboard principal
   - Menú de navegación
   - Resumen de actividades
   - Lista de clientes activos

3. **Gestión de Clientes**
   - Formulario de registro de clientes
   - Lista de clientes
   - Edición de información
   - Eliminación de clientes

4. **Interfaz de Usuario**
   - Diseño responsive
   - Tema consistente
   - Componentes reutilizables

### Fase 3: Gestión y Procesamiento de XML

1. **Sistema de Carga de XML**
   - Interfaz de carga múltiple
   - Validación de archivos
   - Almacenamiento seguro
   - Barra de progreso

2. **Procesamiento de XML**
   - Parser de archivos XML
   - Extracción de datos fiscales
   - Cálculo de diferentes tipos de IVA
   - Manejo de errores

3. **Almacenamiento de Datos**
   - Registro de facturas procesadas
   - Actualización de totales
   - Control de duplicados
   - Registro de errores

4. **Monitor de Procesamiento**
   - Estado del proceso
   - Resumen de archivos procesados
   - Listado de errores encontrados
   - Opción de reprocesamiento

### Fase 4: Generación y Exportación de Reportes

1. **Generación de Reportes**
   - Selección de período
   - Filtrado por cliente
   - Cálculo de totales por tipo de IVA
   - Vista previa del reporte

2. **Exportación a Excel**
   - Formato estandarizado
   - Hojas de cálculo organizadas
   - Fórmulas automáticas
   - Diseño profesional

3. **Historial de Reportes**
   - Listado de reportes generados
   - Filtros de búsqueda
   - Opción de regeneración
   - Descarga de reportes anteriores

4. **Resumen Ejecutivo**
   - Dashboard de reportes
   - Gráficas comparativas
   - Tendencias mensuales
   - Indicadores clave

### Fase 5: Seguridad y Optimización

1. **Implementación de Seguridad**
   - Validación de sesiones
   - Protección contra CSRF
   - Sanitización de datos
   - Control de acceso por roles

2. **Optimización de Base de Datos**
   - Índices optimizados
   - Consultas eficientes
   - Manejo de caché
   - Mantenimiento automático

3. **Manejo de Errores**
   - Sistema de logs
   - Notificaciones de error
   - Recuperación de fallos
   - Monitoreo del sistema

4. **Respaldos y Recuperación**
   - Backup automático
   - Versionado de archivos
   - Plan de recuperación
   - Historial de cambios

### Fase 6: Documentación y Despliegue

1. **Documentación del Sistema**
   - Manual de usuario
   - Documentación técnica
   - Guías de instalación
   - Procedimientos operativos

2. **Pruebas y Control de Calidad**
   - Pruebas unitarias
   - Pruebas de integración
   - Pruebas de seguridad
   - Validación de funcionalidades

3. **Despliegue en Producción**
   - Configuración del servidor
   - Migración de datos
   - Control de versiones
   - Monitoreo inicial

4. **Capacitación y Soporte**
   - Entrenamiento a usuarios
   - Material de capacitación
   - Plan de soporte
   - Gestión de incidencias

## Plan de Ejecución y Próximos Pasos

### Cronograma de Implementación

1. **Semana 1-2: Configuración y Autenticación**
   - Configuración del entorno de desarrollo
   - Implementación del sistema de login
   - Desarrollo del panel de control básico

2. **Semana 3-4: Gestión de XML**
   - Sistema de carga de archivos
   - Procesamiento de XML
   - Validaciones y almacenamiento

3. **Semana 5-6: Reportes y Exportación**
   - Generación de reportes
   - Sistema de exportación
   - Interfaz de consulta

4. **Semana 7-8: Seguridad y Optimización**
   - Implementación de medidas de seguridad
   - Optimización de base de datos
   - Pruebas de rendimiento

5. **Semana 9: Documentación y Pruebas**
   - Documentación del sistema
   - Pruebas integrales
   - Correcciones finales

6. **Semana 10: Despliegue y Capacitación**
   - Despliegue en producción
   - Capacitación a usuarios
   - Soporte inicial

### Métricas de Éxito

1. **Rendimiento**
   - Tiempo de procesamiento de XML < 5 segundos
   - Generación de reportes < 3 segundos
   - Carga de interfaz < 2 segundos

2. **Usabilidad**
   - Tasa de error en carga < 1%
   - Satisfacción del usuario > 90%
   - Tiempo de aprendizaje < 2 horas

3. **Seguridad**
   - 0 vulnerabilidades críticas
   - 100% de datos sensibles encriptados
   - Backups diarios exitosos

### Mantenimiento Continuo

1. **Monitoreo**
   - Revisión diaria de logs
   - Monitoreo de rendimiento
   - Alertas automáticas

2. **Actualizaciones**
   - Parches de seguridad mensuales
   - Mejoras trimestrales
   - Backups semanales

3. **Soporte**
   - Tiempo de respuesta < 24 horas
   - Base de conocimientos actualizada
   - Capacitación continua
